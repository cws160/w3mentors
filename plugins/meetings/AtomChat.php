<?php

/**
 * Atom Chat
 */
class AtomChat extends AbstractMeeting
{

    const KEY = 'AtomChat';
    const ROLE_TEACHER = 'TEACHER';
    const ROLE_LEARNER = 'LEARNER';
    const GROUP_TYPE_PRIVATE = 4;
    const BASE_URL = 'https://api.cometondemand.net/api/v2/';

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
            $this->error = Label::getLabel('LBL_ATOM_CHAT_NOT_FOUND');
            return false;
        }
        /* Format Meeting Tool Settings */
        $settings = json_decode($this->tool['metool_settings'], true) ?? [];
        foreach ($settings as $row) {
            foreach ($row as $name => $field) {
                $this->settings[$name] = $field['value'];
            }
        }
        /* Validate Meeting Tool Settings */
        if (
                empty($this->settings['api_key']) ||
                empty($this->settings['api_id']) ||
                empty($this->settings['chat_auth'])
        ) {
            $this->error = Label::getLabel("MSG_ATOM_CHAT_NOT_CONFIGURED");
            return false;
        }
        return true;
    }

    /**
     * Create Meeting on Atom Chat
     * 
     * @param array $meet = [id, title, duration, starttime, endtime, timezone, recordId, recordType]
     * @param array $users = [ $userType => [user_id, user_type, user_first_name, user_last_name, user_email]]
     * @param int $userType User::LEARNER|User::TEACHER
     * @return bool|array Meeting detail
     */
    public function createMeeting(array $meet, array $users, int $userType)
    {
        if (
                $meet['recordType'] == AppConstant::GCLASS &&
                $userType == User::TEACHER &&
                !$this->createChatGroup($meet)
        ) {
            return false;
        }
        return $this->createChat($meet, $users, $userType);
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
     * Create Chat
     * 
     * @param array $meet
     * @param array $users
     * @param array $userType
     * @return array
     */
    private function createChat(array $meet, array $users, int $userType): array
    {
        $user = $users[$userType];
        $otherUserType = User::LEARNER;
        $chatFriend = '';
        if ($userType != User::TEACHER) {
            $otherUserType = User::TEACHER;
        }
        if (!empty($users[$otherUserType])) {
            $chatFriend = FatUtility::int($users[$otherUserType]['user_id']);
        }
        $apptoken = (new AppToken())->getToken($user['user_id']);
        $token = $apptoken['apptkn_token'] ?? '';
        $rootUrl = API_CALL ? '/api' . CONF_WEBROOT_DASHBOARD : CONF_WEBROOT_DASHBOARD;
        return [
            "chat_appid" => $this->settings['api_id'],
            "chat_auth" => $this->settings['chat_auth'],
            "chat_id" => $user['user_id'],
            "chat_avatar" => $user['user_image'],
            "chat_name" => $user['user_first_name'] . ' ' . $user['user_last_name'],
            "chat_role" => ($userType == User::TEACHER) ? static::ROLE_TEACHER : static::ROLE_LEARNER,
            "chat_friends" => $chatFriend,
            'joinUrl' => MyUtility::makeUrl('Meeting', 'start', [$meet['recordId'], $meet['recordType']], $rootUrl) . '?token=' . $token,
            'chat_url' => "https://" . $this->settings['api_id'] . ".cometondemand.net/cometchat_embedded.php",
            'chat_js' => "https://fast.cometondemand.net/" . $this->settings['api_id'] . "x_xchatx_xcorex_xembedcode.js",
            "chat_signature" => $this->generateSignature($user['user_id'], $user['user_first_name'] . ' ' . $user['user_last_name'])
        ];
    }

    /**
     * Create Chat Group
     *
     * @param array $meet
     * @return bool|array
     */
    private function createChatGroup(array $meet)
    {
        $params = [
            'type' => static::GROUP_TYPE_PRIVATE,
            'GUID' => $meet['id'], 'name' => $meet['title']
        ];
        if (!$res = $this->exeCurlRequest('POST', 'createGroup', $params)) {
            return false;
        }
        if (FatUtility::int($res['success']['status'] ?? '') != 2000) {
            $this->error = Label::getLabel('LBL_MEETING_TOOL_CONFIGURATION_ERROR');
            return false;
        }
        $data = [
            'GUID' => $res['success']['guid'],
            'UIDs' => implode(",", $meet['groupUserIds'])
        ];
        if (!$this->exeCurlRequest('POST', 'addUsersToGroup', $data)) {
            return false;
        }
        return $res;
    }

    /**
     * Generate signature
     *
     * @param integer $chatId
     * @param string $chatName
     * @return string
     */
    private function generateSignature($chatId, $chatName): string
    {
        return md5($chatId . $chatName . $this->settings['api_key']);
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
        $headers = ['Accept: application/json',
            'api-key: ' . $this->settings['api_key'],
            'Content-type: application/x-www-form-urlencoded'];
        $curl = curl_init(static::BASE_URL . $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        $curlResult = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->error = 'Error:' . curl_error($curl);
            return false;
        }
        curl_close($curl);
        $response = json_decode($curlResult, true) ?? [];
        if (empty($response)) {
            $this->error = Label::getLabel('LBL_MEETING_TOOL_CONFIGURATION_ERROR');
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

    public function getLicences(): int
    {
        return $this->maxLicenses;
    }

    public function getFreeMinutes(): int
    {
        return $this->maxDuration;
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
