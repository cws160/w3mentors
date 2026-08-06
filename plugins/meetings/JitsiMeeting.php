<?php

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Serializer\CompactSerializer;

function warning_handler()
{
    throw new Exception(Label::getLabel("MSG_JITSI_CONFIGURATION_ERROR"), 1);
}

/**
 * Jitsi Meeting
 */
class JitsiMeeting extends AbstractMeeting
{

    const KEY = 'JitsiMeeting';

    private $privateKey;

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
            $this->error = Label::getLabel('LBL_JITSI_MEETING_NOT_FOUND');
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
            empty($this->settings['app_id']) ||
            empty($this->settings['api_key']) ||
            empty($this->settings['private_key'])
        ) {
            $this->error = Label::getLabel("MSG_JITSI_MEETING_NOT_CONFIGURED");
            return false;
        }
        $this->privateKey = CONF_UPLOADS_PATH . static::KEY . '.pk';
        if (!file_put_contents($this->privateKey, $this->settings['private_key'])) {
            $this->error = Label::getLabel("MSG_CANNOT_CREATE_PRIVATE_KEY");
            return false;
        }
        return true;
    }

    private function getJwtToken(array $meet, array $user)
    {
        $payload = json_encode([
            'iss' => 'chat',
            'aud' => 'jitsi',
            'exp' => time() + 7200,
            'nbf' => time() - 10,
            'room' => '*',
            'sub' => $this->settings['app_id'],
            'context' => [
                'user' => [
                    'id' => $user['user_id'],
                    'email' => $user['user_email'],
                    'name' => $user['user_first_name'] . ' ' . $user['user_last_name'],
                    'moderator' => ($user['user_type'] == User::TEACHER) ? "true" : "false",
                    'avatar' => $user['user_image'],
                ],
                'features' => [
                    'recording' => "false",
                    'livestreaming' => "false",
                    'transcription' => "false",
                    'outbound-call' => "false"
                ]
            ]
        ]);

        try {
            $algorithm = new AlgorithmManager([new RS256()]);
            $jwsBuilder = new JWSBuilder($algorithm);
            set_error_handler('warning_handler', E_WARNING);
            $jwk = JWKFactory::createFromKeyFile($this->privateKey);
            restore_error_handler();
            $protectedHeader = ['alg' => 'RS256', 'kid' => $this->settings['api_key'], 'typ' => 'JWT'];
            $jws = $jwsBuilder->create()->withPayload($payload)->addSignature($jwk, $protectedHeader)->build();
            return (new CompactSerializer())->serialize($jws, 0);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Create Meeting on Jitsi Meeting
     * 
     * @param array $meet = [id, title, duration, starttime, endtime, timezone, recordId, recordType]
     * @param array $users = [ $userType => [user_id, user_type, user_first_name, user_last_name, user_email]]
     * @param int $userType User::LEARNER|User::TEACHER
     * @return bool|array Meeting detail
     */
    public function createMeeting(array $meet, array $users, int $userType)
    {
        $user = (array) $users[$userType];
        if (!$jwt = $this->getJwtToken($meet, $user)) {
            return false;
        }
        $apptoken = (new AppToken())->getToken($user['user_id']);
        $token = $apptoken['apptkn_token'] ?? '';
        $rootUrl = API_CALL ? '/api' . CONF_WEBROOT_DASHBOARD : CONF_WEBROOT_DASHBOARD;
        $roomName = $this->settings['app_id'] . '/' . CommonHelper::removeSpecialChars($meet['default_title']);
        return [
            'jwt' => $jwt,
            'appID' => $this->settings['app_id'],
            'roomName' => $roomName,
            'id' => $meet['id'],
            'title' => MyUtility::createSlug($meet['title']),
            'joinUrl' => MyUtility::makeUrl('Meeting', 'start', [$meet['recordId'], $meet['recordType']], $rootUrl) . '?token=' . $token,
            'appUrl' => null
        ];
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
        return '';
    }

    public function exeCurlRequest(string $method, string $url, array $params)
    {
    }
}
