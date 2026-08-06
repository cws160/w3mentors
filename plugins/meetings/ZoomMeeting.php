<?php

use Firebase\JWT\JWT;

class ZoomMeeting extends AbstractMeeting
{

    private $meeting;
    private $curlResult;

    public const KEY = 'ZoomMeeting';
    public const ROLE_TEACHER = 1;
    public const ROLE_LEARNER = 0;
    public const DB_TBL_USERS = 'tbl_zoom_users';
    public const BASE_URL = 'https://api.zoom.us/v2';
    public const OAUTH_URL = 'https://zoom.us/oauth/token';
    public const USER_TYPE_BASIC = 1;
    public const USER_TYPE_LICENSED = 2;

    public const ACC_NOT_SYNCED = 1;
    public const ACC_SYNCED_NOT_VERIFIED = 2;
    public const ACC_SYNCED_AND_VERIFIED = 3;


    public function __construct()
    {
        $this->meeting = [];
        parent::__construct();
    }

    /**
     * Initialize Meeting Tool
     * 1. Load Meeting Tool
     * 2. Format Meeting Tool Settings
     * 3. Validate Meeting Tool Settings
     *
     * @return bool
     */
    public function initMeetingTool(): bool
    {
        /* Load Zoom Meeting Tool */
        $this->tool = MeetingTool::getByCode(static::KEY);
        if (empty($this->tool)) {
            $this->error = Label::getLabel('LBL_ZOOM_MEETING_NOT_FOUND');
            return false;
        }
        /* Format meeting tool settings */
        $settings = json_decode($this->tool['metool_settings'], true) ?? [];
        foreach ($settings as $row) {
            foreach ($row as $name => $field) {
                $this->settings[$name] = $field['value'];
            }
        }
        /* Validate Meeting Tool Settings */
        if (
            empty($this->settings['oauth_account_id']) ||
            empty($this->settings['oauth_client_id']) ||
            empty($this->settings['oauth_client_secret']) ||
            empty($this->settings['sdk_client_id']) ||
            empty($this->settings['sdk_client_secret'])
        ) {
            $this->error = Label::getLabel("MSG_ZOOM_MEETING_NOT_CONFIGURED");
            return false;
        }
        $this->settings['license_count'] = $this->settings['license_count'] ?? 0;
        $this->settings['ISV_ENABLED'] = $this->settings['ISV_ENABLED'] ?? 0;
        return true;
    }

    /**
     * Create Meeting on Zoom
     *
     * @param array $meet = [id, title, duration, starttime, endtime, timezone, recordId, recordType]
     * @param array $users = [ $userType => [user_id, user_type, user_first_name, user_last_name, user_email]]
     * @param int $userType User::LEARNER|User::TEACHER
     * @return bool|array Meeting detail
     */
    public function createMeeting(array $meet, array $users, int $userType)
    {
        $user = (array) $users[$userType];
        $userId = FatUtility::int($user['user_id']);
        if (!$zoomUser = $this->getUser($userId)) {
            $user['meeting_duration'] = $meet['duration'];
            $user['meeting_type'] = $meet['recordType'];
            if (!$zoomUser = $this->createUser($user)) {
                return false;
            }
        }
        $zoomUser['first_name'] = $user['user_first_name'];
        $zoomUser['last_name'] = $user['user_last_name'];
        if ($user['user_type'] == User::TEACHER) {
            if (
                $meet['duration'] > $this->getFreeMinutes() &&
                $zoomUser['zmusr_zoom_type'] != static::USER_TYPE_LICENSED &&
                $this->settings['license_count'] > static::getLicensedUserCount()
            ) {
                $zoomUser['zmusr_zoom_type'] = static::USER_TYPE_LICENSED;
                if (!$this->updateUser($zoomUser)) {
                    return false;
                }
            }
            $meeting = $this->getTeacherMeetingDetails($zoomUser, $meet);
        } else {
            $meet['teacher_id'] = $users[User::TEACHER]['user_id'];
            $meeting = $this->getLearnerMeetingDetails($zoomUser, $meet);
        }
        if (empty($meeting)) {
            return false;
        }
        $meeting['appUrl'] = $this->getAppUrl();
        $meeting['joinUrl'] = $this->getJoinUrl($meet['recordId'], $meet['recordType'], $userId);
        return $meeting;
    }

    /**
     * Format Meeting Data
     *
     * @param array $meet
     * @return array $meet
     */
    public static function formatMeeting(array $meet): array
    {
        return $meet;
    }

    /**
     * get teacher meeting details function
     *
     * @param array $user
     * @param array $meeting
     * @return bool|array
     */
    public function getTeacherMeetingDetails(array $user, array $meeting)
    {
        $zoomMeeting = [
            'topic' => $meeting['title'],
            'agenda' => $meeting['title'],
            'duration' => $meeting['duration'],
            'timezone' => $meeting['timezone'],
            'recordId' => $meeting['recordId'],
            'recordType' => $meeting['recordType'],
            'start_time' => date('c', strtotime($meeting['starttime'])),
            'type' => 2
        ];
        $url = static::BASE_URL . '/users/' . $user['id'] . '/meetings';
        if (!$response = $this->exeCurlRequest('POST', $url, $zoomMeeting)) {
            return false;
        }
        if (empty($response['id'])) {
            $this->error = Label::getLabel('LBL_ERROR_TO_CREATE_MEETING');
            return false;
        }
        return $this->meeting = array_merge($response, [
            'user_first_name' => $user['first_name'],
            'user_last_name' => $user['last_name'],
            'user_email' => $user['email'],
            'user_role' => static::ROLE_TEACHER,
            'user_signature' => $this->generateSignature($response['id'], static::ROLE_TEACHER),
        ]);
    }

    /**
     * get learner meeting details function
     *
     * @param array $user
     * @param array $meet
     * @return bool|array
     */
    public function getLearnerMeetingDetails(array $user, array $meet)
    {
        $meeting = Meeting::getMeeting($meet['teacher_id'], $meet['recordId'], $meet['recordType']);
        $meetDetails = json_decode($meeting['meet_details'], true);
        if (empty($meeting)) {
            $this->error = Label::getLabel('LBL_ERROR_TO_CREATE_MEETING');
            return false;
        }
        return $this->meeting = array_merge($meetDetails, [
            'user_first_name' => $user['first_name'],
            'user_last_name' => $user['last_name'],
            'user_email' => $user['email'],
            'user_role' => static::ROLE_LEARNER,
            'user_signature' => $this->generateSignature($meetDetails['id'], static::ROLE_LEARNER),
            'teacher_id' => $meet['teacher_id']
        ]);
    }

    /**
     * Get join URL
     *
     * @param int $recordId
     * @param int $recordType
     * @return string
     */
    public function getJoinUrl(int $recordId, int $recordType, int $userId): string
    {
        $apptoken = (new AppToken())->getToken($userId);
        $token = $apptoken['apptkn_token'] ?? '';
        $rootUrl = API_CALL ? '/api' . CONF_WEBROOT_DASHBOARD : CONF_WEBROOT_DASHBOARD;
        $meetingConfig = [
            "mn" => $this->meeting['id'], "name" => $this->meeting['user_first_name'] . ' ' . $this->meeting['user_last_name'],
            "pwd" => '', "role" => $this->meeting['user_role'],
            "email" => $this->meeting['user_email'],
            "lang" => "en-US",
            "signature" => $this->meeting['user_signature'],
            "leaveUrl" => MyUtility::makeUrl('Meeting', 'leave', [$recordId, $recordType], $rootUrl),
            "china" => 0,
            'sdkKey' => $this->settings['sdk_client_id'],
            'token' => $token
        ];
        $configs = [];
        foreach ($meetingConfig as $key => $value) {
            $tokenString = '';
            if ($key == 'leaveUrl') {
                $tokenString = '?token=' . $token;
            }
            $string = $this->encodeURIComponent($key) . '=' . $this->encodeURIComponent($value) . $tokenString;
            array_push($configs, $string);
        }
        return MyUtility::makeUrl('Meeting', 'start', [$recordId, $recordType], $rootUrl) . '?' . implode("&", $configs);
    }

    public function getAppUrl()
    {
        if ($this->meeting['user_role'] == static::ROLE_LEARNER) {
            return $this->meeting['join_url'];
        } elseif ($this->meeting['user_role'] == static::ROLE_TEACHER) {
            return $this->meeting['start_url'];
        }
    }

    /**
     * Get Zoom User
     *
     * @param string $email
     * @return bool|array
     */
    private function getZoomUser(string $email)
    {
        /* Execute Curl Request */
        $url = self::BASE_URL . "/users/" . $email . "?encrypted_email=false";
        if (!$response = $this->exeCurlRequest('GET', $url, [])) {
            return false;
        }
        if (empty($response['id'])) {
            $this->error = Label::getLabel('LBL_CONTACT_WITH_ADMIN_ISSUE_WITH_MEETING_TOOL');
            return false;
        }
        return $response;
    }

    /**
     * Get User
     *
     * @param int $userId
     * @return bool|array
     */
    private function getUser(int $userId)
    {
        $srch = new SearchBase(static::DB_TBL_USERS, 'zmusr');
        $srch->addCondition('zmusr_user_id', '=', $userId);
        $srch->addCondition('zmusr_verified', '=', AppConstant::ACTIVE);
        $srch->addFld('zmusr.*');
        $srch->doNotCalculateRecords();
        $user = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($user)) {
            $this->error = Label::getLabel('LBL_ZOOM_USER_NOT_FOUND');
            return false;
        }
        $userDetails = json_decode($user['zmusr_details'], true);
        $userDetails['zmusr_user_id'] = $user['zmusr_user_id'];
        $userDetails['zmusr_zoom_id'] = $user['zmusr_zoom_id'];
        $userDetails['zmusr_zoom_type'] = $user['zmusr_zoom_type'];
        return $userDetails;
    }

    /**
     * Create Zoom User
     *
     * @param array $user
     * @return bool|array
     */
    private function createUser(array $user)
    {
        $type = $this->getUserType($user);
        $action = 'create';
        if (AppConstant::ACTIVE == FatApp::getConfig('CONF_ZOOM_ISV_ENABLED', 0)) {
            $action = 'custCreate';
        }

        $request = [
            'action' => $action,
            'user_info' => [
                'type' => $type,
                'email' => $user['user_email'],
                'first_name' => $user['user_first_name'],
                'last_name' => $user['user_last_name'],
            ]
        ];
        /* Execute Curl Request */
        $response = $this->exeCurlRequest('POST', self::BASE_URL . "/users", $request);
        if (!$response) {
            return false;
        }
        /**
         * 1005 is user already exists in the account
         */
        if (!empty($response['code']) && ($response['code'] == 1005 || $response['code'] == 1009)) {
            if (!$response = $this->getZoomUser($user['user_email'])) {
                return false;
            }
            $type = $response['type'];
        } elseif (empty($response['id'])) {
            $this->error = Label::getLabel('LBL_CONTACT_WITH_ADMIN_ISSUE_WITH_MEETING_TOOL');
            return false;
        }
        /* Map Zoom User with Users */
        $record = new TableRecord(static::DB_TBL_USERS);

        /* $userVerified = $response['verified'] ?? 0; */
        $userStatus = $response['status'] ?? 'pending';
        $zmusrVerified = 0;
        /* if ($userVerified == 1 && $userStatus == 'active') { */
        if ($userStatus == 'active') {
            $zmusrVerified = 1;
        }

        $record->assignValues([
            'zmusr_user_id' => $user['user_id'],
            'zmusr_zoom_type' => $type,
            'zmusr_zoom_id' => $response['id'],
            'zmusr_details' => json_encode($response),
            'zmusr_verified' => $zmusrVerified
        ]);
        if (!$record->addNew([], $record->getFlds())) {
            $this->error = $record->getError();
            return false;
        }

        $userSettings = new UserSetting($user['user_id']);
        $userSettings->saveData([
            'user_zoom_status' => $zmusrVerified == 1 ? static::ACC_SYNCED_AND_VERIFIED : static::ACC_SYNCED_NOT_VERIFIED
        ]);
        if (isset($user['request_type']) && $user['request_type'] == 'verify' && 0 == $zmusrVerified) {
            $this->error = Label::getLabel('LBL_PLEASE_CHECK_YOUR_EMAIL_TO_VERIFY_YOUR_ZOOM_ACCOUNT');
            return false;
        }
        $response = array_merge($response, [
            'zmusr_user_id' => $user['user_id'],
            'zmusr_zoom_type' => $type,
            'zmusr_zoom_id' => $response['id']
        ]);
        return $response;
    }

    public function updateUser(array $user)
    {
        $request = ['type' => $user['zmusr_zoom_type']];
        $url = self::BASE_URL . "/users/" . $user['zmusr_zoom_id'];
        $this->exeCurlRequest("PATCH", $url, $request);
        if ($this->curlResult['httpcode'] != 204) {
            $this->error = Label::getLabel('LBL_CONTACT_WITH_ADMIN_ISSUE_WITH_MEETING_TOOL');
            return false;
        }
        $record = new TableRecord(static::DB_TBL_USERS);
        $record->assignValues([
            'zmusr_user_id' => $user['zmusr_user_id'],
            'zmusr_zoom_type' => $user['zmusr_zoom_type']
        ]);
        if (!$record->update(['smt' => 'zmusr_user_id = ?', 'vals' => [$user['zmusr_user_id']]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Generate signature
     *
     * @param integer $meetingId
     * @param integer $role
     * @return string
     */
    private function generateSignature(int $meetingId, int $role): string
    {

        $iat = time();
        $exp = $iat + 1800;
        $token = array(
            "sdkKey" => $this->settings['sdk_client_id'],
            "mn" => $meetingId,
            "role" => $role,
            "iat" => $iat,
            "exp" => $exp,
            "appKey" => $this->settings['sdk_client_id'],
            "tokenExp" => $exp
        );
        return JWT::encode($token, $this->settings['sdk_client_secret'], "HS256");
    }

    /**
     * Encode URI Component
     *
     * @param string $str
     * @return string
     */
    private function encodeURIComponent(string $str): string
    {
        $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
        return strtr(rawurlencode($str), $revert);
    }

    /**
     * Execute Curl Request
     *
     * @param string $url
     * @param array $params
     * @return boolean
     */
    public function exeCurlRequest(string $method, string $url, array $params)
    {
        $postfields = json_encode($params);
        $headers = [
            'Content-type: application/json',
            'Content-length: ' . strlen($postfields),
            'Authorization: Bearer ' . $this->getAccessToken()
        ];
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
        $curlResult = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->error = 'Error:' . curl_error($curl);
            return false;
        }
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $this->curlResult['body'] = $curlResult;
        $this->curlResult['httpcode'] = $httpcode;
        $response = json_decode($curlResult, true) ?? [];
        if (empty($response)) {
            $this->error = 'Zoom: ' . ($response['message'] ?? Label::getLabel('LBL_CONTACT_WITH_ADMIN'));
            return false;
        }

        if (!empty($response['code'])) {
            return $response;
        }

        return $response;
    }

    public static function getLicensedUserCount()
    {
        $srch = new SearchBase(static::DB_TBL_USERS);
        $srch->addCondition('zmusr_zoom_type', '=', static::USER_TYPE_LICENSED);
        $srch->addMultipleFields(['count(zmusr_user_id) as totalCount']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row['totalCount'] ?? 0;
    }

    private function getUserType(array $user): int
    {
        $type = static::USER_TYPE_BASIC;
        if (
            $user['user_type'] == User::TEACHER &&
            $user['meeting_duration'] > $this->getFreeMinutes() &&
            $this->getLicences() > static::getLicensedUserCount()
        ) {
            $type = static::USER_TYPE_LICENSED;
        }
        return $type;
    }

    /**
     * Get the meeting tool details
     *
     * @return array
     */
    public function getToolDetails(): array
    {
        return $this->tool;
    }

    /**
     * Close Meeting
     *
     * @param array $meet
     * @return bool
     */
    public function closeMeeting(array $meet): bool
    {
        $meetDetail = json_decode($meet['meet_details'], true);
        $role = $meetDetail['user_role'] ?? 0;
        if (empty($meetDetail['id']) || ($role == static::ROLE_LEARNER &&
            $meet['meet_record_type'] == AppConstant::GCLASS)) {
            return true;
        }
        $url = self::BASE_URL . '/meetings/' . $meetDetail['id'] . '/status';
        $this->exeCurlRequest('PUT', $url, ["action" => "end"]);
        if ($meetDetail['duration'] > $this->getFreeMinutes()) {
            $teacherId = ($role == static::ROLE_LEARNER) ? $meetDetail['teacher_id'] : $meet['meet_user_id'];
            $user = [
                'zmusr_user_id' => $teacherId,
                'zmusr_zoom_id' => $meetDetail['host_id'],
                'zmusr_zoom_type' => static::USER_TYPE_BASIC
            ];
            $this->updateUser($user);
        }
        return true;
    }

    /**
     * Get Licenses
     *
     * @return int
     */
    public function getLicences(): int
    {
        return FatUtility::int($this->settings['license_count']);
    }

    /**
     * Get Free Minutes
     *
     * @return int
     */
    public function getFreeMinutes(): int
    {
        return FatUtility::int($this->settings['free_meeting_duration']);
    }

    /**
     * Remove Licenses
     *
     * @return bool
     */
    public function removeLicenses(): bool
    {
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->joinTable(static::DB_TBL_USERS, 'INNER JOIN', 'grpcls_teacher_id = zmusr_user_id', 'zmusr');
        $srch->addMultipleFields(['zmusr_user_id', 'zmusr_zoom_id', 'zmusr_zoom_type']);
        $srch->addCondition('zmusr.zmusr_zoom_type', '=', static::USER_TYPE_LICENSED);
        $srch->addCondition('grpcls.grpcls_metool_id', '=', $this->tool['metool_id']);
        $srch->addCondition('grpcls.grpcls_duration', '>', $this->getFreeMinutes());
        $srch->addCondition('grpcls.grpcls_status', '=', GroupClass::SCHEDULED);
        $srch->addDirectCondition('grpcls.grpcls_teacher_starttime IS NOT NULL');
        $srch->addCondition('grpcls.grpcls_booked_seats', '>', 0);
        $srch->addDirectCondition("DATE_SUB(grpcls.grpcls_end_datetime, INTERVAL 5 MINUTE) < '" . date('Y-m-d H:i:s') . "'");
        $srch->addGroupBy('grpcls.grpcls_teacher_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(20);
        $resultSet = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            $record = new TableRecord(static::DB_TBL_USERS);
            $record->setFldValue('zmusr_zoom_type', static::USER_TYPE_BASIC);
            if (!$record->update(['smt' => 'zmusr_zoom_id = ?', 'vals' => [$row['zmusr_zoom_id']]])) {
                $this->error = Label::getLabel('LBL_CANNOT_REMOVE_LICENSE');
                return false;
            }
        }
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(static::DB_TBL_USERS, 'INNER JOIN', 'ordles_teacher_id = zmusr_user_id', 'zmusr');
        $srch->addMultipleFields(['zmusr_user_id', 'zmusr_zoom_id', 'zmusr_zoom_type']);
        $srch->addCondition('zmusr.zmusr_zoom_type', '=', static::USER_TYPE_LICENSED);
        $srch->addCondition('ordles.ordles_metool_id', '=', $this->tool['metool_id']);
        $srch->addCondition('ordles.ordles_duration', '>', $this->getFreeMinutes());
        $srch->addDirectCondition('ordles.ordles_teacher_starttime IS NOT NULL');
        $srch->addCondition('ordles.ordles_status', '=', Lesson::SCHEDULED);
        $srch->addDirectCondition("DATE_SUB(ordles.ordles_lesson_endtime, INTERVAL 5 MINUTE) < '" . date('Y-m-d H:i:s') . "'");
        $srch->addGroupBy('ordles.ordles_teacher_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(20);
        $resultSet = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            $record = new TableRecord(static::DB_TBL_USERS);
            $record->setFldValue('zmusr_zoom_type', static::USER_TYPE_BASIC);
            if (!$record->update(['smt' => 'zmusr_zoom_id = ?', 'vals' => [$row['zmusr_zoom_id']]])) {
                $this->error = Label::getLabel('LBL_CANNOT_REMOVE_LICENSE');
                return false;
            }
        }
        return true;
    }

    /**
     * Fetch Playback URL
     * 
     * @return string
     */
    public function fetchPlaybackUrl(): string
    {
        return '';
    }

    public function getAccessToken()
    {
        try {
            $data = ['grant_type' => 'account_credentials', 'account_id' => $this->settings['oauth_account_id']];
            $clientIdSecret = $this->settings['oauth_client_id'] . ':' . $this->settings['oauth_client_secret'];
            $headers = ['Authorization: Basic ' . base64_encode($clientIdSecret)];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, static::OAUTH_URL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);
            return $result['access_token'] ?? '';
        } catch (Exception $error) {
            return $error->getMessage();
        }
    }

    public function handleCreateUser(array $user)
    {
        if (!array_key_exists('meeting_duration', $user)) {
            $user['meeting_duration'] = $this->getFreeMinutes();
        }
        $user['request_type'] = 'create';
        return $this->createUser($user);
    }

    // need to more optimized
    public function verifyAccount(array $user)
    {
        $user['meeting_duration'] = $this->getFreeMinutes();
        $user['request_type'] = 'verify';
        $result = $this->createUser($user);
        if (!$result) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }
}
