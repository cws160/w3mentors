<?php

/**
 * PencilSpaces 
 */
class PencilSpaces extends AbstractMeeting
{

    const KEY = 'PencilSpaces';
    const STAGING_URL = "https://staging-apis.pencilapp.com/public/api/";
    const LIVE_URL = "https://apis.pencilapp.com/public/api/";
    const REDIRECT_STAGING_URL = "https://staging.pencilapp.com/";
    const REDIRECT_LIVE_URL = "https://prod-api.pencilapp.com/";
    const DB_TBL_USERS = 'tbl_pencil_space_users';

    private $baseURl;
    private $redirectURl;
    public function __construct()
    {
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
        /* Load Meeting Tool */
        $this->tool = MeetingTool::getByCode(static::KEY);
        if (empty($this->tool)) {
            $this->error = Label::getLabel('LBL_PENCIL_SPACE_NOT_FOUND');
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
        if (empty($this->settings['developer_key'])) {
            $this->error = Label::getLabel("MSG_PENCIL_SPACE_NOT_CONFIGURED");
            return false;
        }
        $this->baseURl = static::STAGING_URL;
        $this->redirectURl = static::REDIRECT_STAGING_URL;
        if ($this->settings['enable_live_payment'] > 0) {
            $this->baseURl = static::LIVE_URL;
            $this->redirectURl = static::REDIRECT_LIVE_URL;
        }
        return true;
    }

    /**
     * Create Meeting on  Pencil Space
     * 
     * @param array $meet = [id, title, duration, starttime, endtime, timezone, recordId, recordType]
     * @param array $users = [ $userType => [user_id, user_type, user_first_name, user_last_name, user_email]]
     * @param int $userType User::LEARNER|User::TEACHER
     * @return bool|array Meeting detail
     */
    public function createMeeting(array $meet, array $users, int $userType)
    {
        $user = (array) $users[$userType];
        if (!$user = $this->createMeetingUser($user, $userType)) {
            return false;
        }
        if ($userType == User::TEACHER) {
            $res = $this->getTeacherMeetingDetails($meet, $user);
        } else {
            $meet['teacher_id'] = $users[User::TEACHER]['user_id'];
            $res = $this->getLearnerMeetingDetails($meet, $user);
        }
        if (empty($res)) {
            return false;
        }
        return $res;
    }

    private function createMeetingUser($user, $userType)
    {
        $meetingUser = $this->getUser($user['user_id']);
        if (!empty($meetingUser)) {
            $user['userId'] = $meetingUser['penspausr_penuser_id'];
            return $user;
        }
        $params = [
            "name" =>  $user['user_first_name'] . ' ' . $user['user_last_name'],
            "userRole" =>  $userType == User::TEACHER ? 'Teacher' : 'Student',
            "externalId" => $user['user_email']
        ];
        $url = 'users/createAPIUser';
        if (!$res = $this->exeCurlRequest('POST', $url, $params)) {
            return false;
        }
        /* Map Pencil User with Users */
        $record = new TableRecord(static::DB_TBL_USERS);
        $record->assignValues([
            'penspausr_user_id' => $user['user_id'],
            'penspausr_penuser_id' => $res['userId'],
            'penspausr_details' => json_encode($res),
        ]);
        if (!$record->addNew([], $record->getFlds())) {
            $this->error = $record->getError();
            return false;
        }
        $user['userId'] = $res['userId'];
        return $user;
    }


    /**
     * Get User
     *
     * @param int $userId
     * @return bool|array
     */
    private function getUser(int $userId)
    {
        $srch = new SearchBase(static::DB_TBL_USERS, 'penspausr');
        $srch->addCondition('penspausr_user_id', '=', $userId);
        $srch->addFld('penspausr.*');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return  FatApp::getDb()->fetch($srch->getResultSet());
    }

    private function getTeacherMeetingDetails($meet, $user)
    {
        $meet['default_title'] =  CommonHelper::removeSpecialChars($meet['default_title']);
        $params = [
            "title" => $meet['default_title'],
            "hosts" => [
                [
                    "email" => $user['user_email'],
                    "userId" => $user['userId']
                ]
            ],
            "participants" => [],
            "visibility" => "private",
        ];
        $res = $this->exeCurlRequest('POST', 'spaces/create', $params);
        if (empty($res['link'] ?? '')) {
            return false;
        }
        if (!$link = $this->authorizeUser($res['link'], $user['userId'])) {
            return false;
        }
        $res['joinUrl'] = $link;
        unset($res['link']);
        return $res;
    }

    private function getLearnerMeetingDetails($meet, $user)
    {
        $meeting = Meeting::getMeeting($meet['teacher_id'], $meet['recordId'], $meet['recordType']);
        $meetDetails = json_decode($meeting['meet_details'], true);
        if (empty($meeting)) {
            $this->error = Label::getLabel('LBL_ERROR_TO_CREATE_MEETING');
            return false;
        }
        $url = 'spaces/' . $meetDetails['spaceId'] . '/updateUsers';
        if (!$this->updateSpaceUsers($url, $user)) {
            return false;
        }
        $url = $this->redirectURl . 'spaces/' . $meetDetails['spaceId'];
        if (!$link = $this->authorizeUser($url, $user['userId'])) {
            return false;
        }
        $meetDetails['joinUrl'] = $link;
        return $meetDetails;
    }

    private function authorizeUser($link, $userID)
    {
        $url = 'users/' . $userID . '/authorize?redirectUrl=' . $link;
        if (!$res = $this->exeCurlRequest('GET', $url, [])) {
            return false;
        }
        if (empty($res['url'] ?? '')) {
            return false;
        }
        return $res['url'];
    }

    private function updateSpaceUsers($url, $user)
    {
        $params = [
            "addUsers" => [
                [
                    "role" => 'student',
                    "email" => $user['user_email'],
                    "userId" => $user['userId']
                ]
            ]
        ];
        if (!$res = $this->exeCurlRequest('PATCH', $url, $params)) {
            return false;
        }
        return true;
    }


    /**
     * Format Meeting Data
     * 
     * @param array $meet
     * @return array $meet
     */
    public static function formatMeeting(array $meet): array
    {
        return [];
    }

    /**
     * Execute Curl Request
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @return boolean
     */
    public function exeCurlRequest(string $method, string $url, array $params)
    {
        $postfields = json_encode($params);
        $headers = [
            'Accept', 'application/json',
            'Content-type: application/json',
            'Authorization: Bearer ' . $this->settings['developer_key']
        ];
        if (strtoupper($method) != 'GET') {
            $headers[] = 'Content-length: ' . strlen($postfields);
        }
        $curl = curl_init($this->baseURl . $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if (strtoupper($method) != 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
        }
        $curlResult = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->error = 'Error:' . curl_error($curl);
            return false;
        }
        $http_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($method == 'PATCH' && $http_status_code == 200) {
            return true;
        }
        $response = json_decode($curlResult, true) ?? [];
        if (!empty($response['err'])) {
            $this->error = static::KEY . ': ' . $response['err'];
            return false;
        }
        if (empty($response)) {
            $this->error = Label::getLabel('LBL_CONTACT_WITH_ADMIN_ISSUE_WITH_MEETING_TOOL');
            return false;
        }
        return $response;
    }


    /**
     * Close Meeting
     * 
     * @param array $meet 
     * @return bool
     */
    public function closeMeeting(array $meet): bool
    {
        return true;
    }

    /**
     * Remove Licenses
     * 
     * @return bool
     */
    public function removeLicenses(): bool
    {
        return true;
    }

    public function getLicences(): int
    {
        return $this->maxLicenses;
    }

    public function getFreeMinutes(): int
    {
        return $this->maxDuration;
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
}
