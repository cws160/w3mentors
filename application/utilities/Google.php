<?php

use Google\Client;

/**
 * A Common Google Utility Class 
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Google extends FatModel
{

    protected $client;
    protected $userId;

    /**
     * Initialize Google
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get Google Client
     * 
     * @return bool|Client
     */
    public function getClient()
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }
        if (!$authConfig = static::getClientJson()) {
            $this->error = Label::getLabel('LBL_GOOGLE_LOGIN_IS_NOT_AVAILABLE');
            return false;
        }
        $authConfig = !is_array($authConfig) ? (string)$authConfig : $authConfig;
        try {
            $this->client = new Client();
            $this->client->setAuthConfig($authConfig);
        } catch (Exception $exc) {
            $this->error = $exc->getMessage();
            return false;
        }
        return $this->client;
    }

    /**
     * Get User Token
     * 
     * @param string $accessToken
     * @return bool|string
     */
    public function getUserToken(string $accessToken)
    {
        $accessToken = json_decode($accessToken, true);
        if (empty($accessToken)) {
            $this->error = Label::getLabel('LBL_INVALID_TOKEN');
            return false;
        }
        if (!$this->getClient()) {
            return false;
        }
        if (empty($accessToken['refresh_token'])) {
            $token = (new UserSetting($this->userId))->getGoogleRefreshToken();
        }
        if (!empty($token)) {
            $accessToken['refresh_token'] = $token;
        }
        $this->client->setAccessToken($accessToken);
        if (!$this->client->isAccessTokenExpired()) {
            return $this->client->getAccessToken()['access_token'];
        } elseif (!$refreshToken = $this->client->getRefreshToken()) {
            $this->error = Label::getLabel('LBL_TOKEN_EXPIRE');
            return false;
        }
        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $accessToken = $this->client->getAccessToken();
        if (!empty($accessToken)) {
            $userSetting = new UserSetting($this->userId);
            $userSetting->saveData(['user_google_token' => json_encode($accessToken)]);
            return $accessToken['access_token'];
        }
        $this->error = Label::getLabel('LBL_INVALID_TOKEN');
        return false;
    }

    /**
     * Get Analytic Token
     * 
     * @return string
     */
    public function getGoogleAuthToken(): string
    {
        $token = FatApp::getConfig('GOOGLE_AUTH_TOKEN', FatUtility::VAR_STRING, '');
        $accessToken = (!empty($token)) ? json_decode($token, true) : [];
        if (empty($accessToken)) {
            return '';
        }
        if (!$this->getClient()) {
            return false;
        }
        $this->client->setAccessToken($accessToken);
        if (!$this->client->isAccessTokenExpired()) {
            return $this->client->getAccessToken()['access_token'];
        } elseif (!$refreshToken = $this->client->getRefreshToken()) {
            return '';
        }
        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $accessToken = $this->client->getAccessToken();
        if (!empty($accessToken)) {
            (new Configurations())->updateConf('GOOGLE_AUTH_TOKEN', json_encode($accessToken));
            return $accessToken['access_token'];
        }
        return '';
    }

    /**
     * Get Client JSON
     * 
     * @return bool|string
     */
    public static function getClientJson()
    {
        $authConfig = FatApp::getConfig('CONF_GOOGLE_CLIENT_JSON', FatUtility::VAR_STRING, '');
        $authConfig = json_decode($authConfig, true);
        if (!$authConfig || empty($authConfig)) {
            return false;
        }
        return $authConfig;
    }

}
