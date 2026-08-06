<?php

/**
 * Lesson Space
 */
class LessonSpace extends AbstractMeeting
{

    const KEY = 'LessonSpace';
    const BASE_URL = "https://api.thelessonspace.com/v2/";

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
            $this->error = Label::getLabel('LBL_LESSON_SPACE_NOT_FOUND');
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
        if (empty($this->settings['api_key']) || empty($this->settings['organisation'])) {
            $this->error = Label::getLabel("MSG_LESSON_SPACE_NOT_CONFIGURED");
            return false;
        }
        return true;
    }

    /**
     * Create Meeting on LessonSpace
     * 
     * @param array $meet = [id, title, duration, starttime, endtime, timezone, recordId, recordType]
     * @param array $users = [ $userType => [user_id, user_type, user_first_name, user_last_name, user_email]]
     * @param int $userType User::LEARNER|User::TEACHER
     * @return bool|array Meeting detail
     */
    public function createMeeting(array $meet, array $users, int $userType)
    {
        $user = (array) $users[$userType];
        $params = [
            "id" => $meet['id'],
            "user" => [
                'name' => $user['user_first_name'] . ' ' . $user['user_last_name'],
                'leader' => ($userType == User::TEACHER),
                'profile_picture' => $user['user_image'],
            ],
            'timeouts' => [
                "not_before" => date('c', strtotime($meet['starttime'])),
                "not_after" => date('c', strtotime($meet['endtime']))
            ],
            "features" => [
                'invite' => false,
                'fullscreen' => true,
                'endSession' => false,
                'whiteboard.equations' => true,
                'whiteboard.infiniteToggle' => true
            ],
        ];
        $res = $this->exeCurlRequest('POST', 'spaces/launch/', $params);
        if (empty($res['client_url'] ?? '')) {
            return false;
        }
        $res['joinUrl'] = $res['client_url'];
        unset($res['client_url']);
        return $res;
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
            'Authorization: Organisation ' . $this->settings['api_key']
        ];
        if (strtoupper($method) != 'GET') {
            $headers[] = 'Content-length: ' . strlen($postfields);
        }
        $curl = curl_init(static::BASE_URL . $url);
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
        curl_close($curl);
        $response = json_decode($curlResult, true) ?? [];
        if (!empty($response['detail'])) {
            $this->error = static::KEY . ': ' . $response['detail'];
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
     * @return string Meeting detail
     */
    public function fetchPlaybackUrl(array $meet): string
    {
        $recordingUrl = '';
        if (!empty($meet['meet_details']) && !empty($this->settings['enable_recording'] ?? '') && !empty($this->settings['organisation'])) {
            $meet = json_decode($meet['meet_details'], true);
            $url = 'organisations/' . $this->settings['organisation'] . '/sessions/' . $meet['session_id'] . '/playback/';
            $res = $this->exeCurlRequest('GET', $url, []);
            if(!empty($res['recording_url'])) {
                $recordingUrl = $res['recording_url'];
            }
        }
        return $recordingUrl;
    }

}
