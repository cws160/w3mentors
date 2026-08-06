<?php

/**
 * Video Cipher
 * 
 * @author Fatbit Technologies
 */
class VideoCipher extends VideoStreamer
{
    private $apiKey;
    public  $params = [];
    private $hostUrl = 'https://dev.vdocipher.com/api/';
    public  $videoId;

    const VIDEO_URL = 'https://player.vdocipher.com/v2/';

    public function __construct()
    {
    }

    /**
     * Initialize Method
     * 
     * @return bool
     */
    public function init(): bool
    {
        $this->apiKey = FatApp::getConfig('CONF_VIDEO_CIPHER_API_KEY');
        if (empty($this->apiKey)) {
            $this->error = Label::getLabel("LBL_CIPHER_CONFIGURATION_NOT_SET");
            return false;
        }
        return true;
    }

    /**
     * Generate video url
     *
     * @param string $id
     * @param boolean $autoPlay
     * @return string
     */
    public function generateUrl(string $id, bool $autoPlay = false)
    {
        if (!$this->init()) {
            return false;
        }

        /* get Otp */
        if (!$result = $this->exeCurlRequest($this->hostUrl . "videos/" . $id . "/otp")) {
            return false;
        }
        /* returned this key in case of error */
        if (isset($result['message'])) {
            $this->error = $result['message'];
            return false;
        }
        $params = ['otp' => $result['otp'], 'playbackInfo' => $result['playbackInfo'], 'loop' => false];
        if ($autoPlay) {
            $params['autoplay'] = true;
        }
        return static::VIDEO_URL . '?' . http_build_query($params);
    }

    /**
     * Get video details
     *
     * @param string $id
     * @return array|bool
     */
    public function get(string $id)
    {
        if (!$this->init()) {
            return false;
        }
        $this->params = [];
        if (!$response = $this->exeCurlRequest($this->hostUrl . 'videos/' . $id, 'GET')) {
            return false;
        }
        return $response;
    }

    /**
     * Remove video
     *
     * @param string $id
     * @return bool
     */
    public function remove(string $id)
    {
        if (!$this->init()) {
            return false;
        }
        if (!$this->exeCurlRequest($this->hostUrl . 'videos?videos=' . $id, 'DELETE')) {
            return false;
        }
        return true;
    }

    /**
     * Upload file
     *
     * @param array $file
     * @return bool
     */
    public function uploadVideo(array $file)
    {
        if (!$this->init()) {
            return false;
        }
        /* get credentials */
        $url = $this->hostUrl . 'videos?title=CourseVideo';
        $folderId = FatApp::getConfig('CONF_VIDEO_CIPHER_FOLDER_ID', FatUtility::VAR_STRING, '');
        $url .= !empty($folderId) ? '&folderId=' . $folderId : '';
        if (!$credentials = $this->exeCurlRequest($url, 'PUT')) {
            return false;
        }
        if (empty($credentials['videoId'])) {
            $this->error = Label::getLabel('LBL_UNABLE_TO_UPLOAD_THE_FILE');
            return false;
        }

        /* get video id */
        $this->videoId = $credentials['videoId'];
        $credentials = (array)$credentials['clientPayload'];

        $this->params = [
            'policy' => $credentials['policy'],
            'key' => $credentials['key'],
            'x-amz-signature' => $credentials['x-amz-signature'],
            'x-amz-credential' => $credentials['x-amz-credential'],
            'x-amz-algorithm' => $credentials['x-amz-algorithm'],
            'x-amz-date' => $credentials['x-amz-date'],
            'success_action_status' => 201,
            'success_action_redirect' => "",
        ];
        if (!$this->exeCurlRequest($credentials['uploadLink'], 'POST', $file)) {
            return false;
        }
        return true;
    }

    /**
     * Set common headers for curl
     *
     * @return array
     */
    private function getCommonHeaders()
    {
        return [
            "Accept: application/json",
            "Authorization: Apisecret " . $this->apiKey,
            "Content-Type: application/json"
        ];
    }
    
    /**
     * Set common options for curl
     *
     */
    private function setCommonOptions($curl)
    {
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ]);
    }

    /**
     * Set options for file upload
     *
     */
    private function setUpFileUploadOptions($curl, $file)
    {
        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array_merge($this->params, ['file' => new \CurlFile($file['tmp_name'], $file['type'], $file['name'])]),
            CURLOPT_FOLLOWLOCATION => 1
        ]);
    }

    /**
     * Set options for other curl requests
     *
     */
    private function setUpRegularRequestOptions($curl)
    {
        $params = !empty($this->params) ? json_encode($this->params) : [];

        curl_setopt_array($curl, [
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => $this->getCommonHeaders()
        ]);
    }

    /**
     * Execute curl request
     *
     * @param string $url
     * @param string $method
     * @param boolean $file
     * @return bool|array
     */
    private function exeCurlRequest(string $url, string $method = 'POST', $file = false)
    {
        $curl = curl_init($url);

        if ($file) {
            $this->setUpFileUploadOptions($curl, $file);
        } else {
            $this->setUpRegularRequestOptions($curl);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        $this->setCommonOptions($curl);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($file) {
            return $this->handleFileResponse($response, $httpcode, $error);
        } else {
            return $this->handleRegularResponse($response, $error);
        }
    }

    private function handleFileResponse($response, $httpCode, $error)
    {
        if (!$error && $httpCode === 201) {
            return true;
        } else {
            $this->error = "cURL Error #:" . (($error) ? $error : $response);
            return false;
        }
        return true;
    }

    private function handleRegularResponse($response, $error)
    {
        if ($error) {
            $this->error = "cURL Error #:" . $error;
            return false;
        } elseif (!empty($response['message'])) {
            $this->error = "Error :" . $response['message'];
            return false;
        }
        return (array) json_decode($response, true);
    }

    /**
     * Get Video Status
     *
     * @param string $videoId
     * @return int
     */
    public function getStatus($videoId = '')
    {
        return 0;
    }
}
