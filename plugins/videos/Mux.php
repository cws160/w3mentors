<?php

/**
 * MUX Videos
 * 
 * @author Fatbit Technologies
 */
class Mux extends VideoStreamer
{
    private $config = [];
    private $fileHandle;
    public  $uploadId;
    public $videoId;
    const HOST_URL = 'https://api.mux.com';
    const ASSET_ENDPOINT = '/video/v1/assets';
    const UPLOAD_ENDPOINT = '/video/v1/uploads';

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
        $this->config = [
            'access_token_id' => FatApp::getConfig('CONF_MUX_ACCESS_TOKEN_ID', FatUtility::VAR_STRING, ''),
            'secret_key' => FatApp::getConfig('CONF_MUX_SECRET_KEY', FatUtility::VAR_STRING, ''),
            'encoding_tier' => FatApp::getConfig('CONF_MUX_ENCODING_TIER', FatUtility::VAR_STRING, ''),
            'resolution' =>  FatApp::getConfig('CONF_MUX_RESOLUTION', FatUtility::VAR_STRING, '')
        ];
        if (empty($this->config['access_token_id']) || empty($this->config['secret_key']) || empty($this->config['encoding_tier']) || empty($this->config['resolution'])) {
            $this->error = Label::getLabel("LBL_MUX_CONFIGURATION_MISSING");
            return false;
        }
        return true;
    }

    /**
     * Generate the Video Upload URL
     * 
     * @return string
     */
    public function getUploadUrl()
    {
        if(!$this->init()) {
            return false;
        }
        $endPoint = static::HOST_URL . static::UPLOAD_ENDPOINT;
        if (!$response = $this->execCurlRequest($endPoint)) {
            return false;
        }
        $this->uploadId = $response['data']['id'];
        if (empty($response['data']['url']) || $response['data']['url'] == "") {
            $this->error = Label::getLabel('LBL_COULD_NOT_GET_UPLOAD_URL');
            return false;
        }
        return $response['data']['url'];
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
        if(!$this->init()) {
            return false;
        }
        if (empty($id)) {
            return true;
        }
        if (!$assetDetails = $this->get($id)) {
            return false;
        }
        return current($assetDetails['data']['playback_ids'])['id'];
    }

    private function execCurlRequest(string $url, string $method = 'POST', $file = false)
    {
        $curl = curl_init($url);
        $headers = [];
        if ($file) {
            $headers = [
                'Authorization: Basic ' . base64_encode($this->config['access_token_id'] . ':' . $this->config['secret_key']),
                'Content-type: application/json'
            ];
            $param = [
                'method' => 'PUT',
                'cors_origin' => "*",
                'headers' => [
                    'Access-Control-Allow-Origin' => '*'
                ]
            ];
            $this->fileHandle = fopen($file['tmp_name'], 'r');
            curl_setopt($curl, CURLOPT_INFILE, $this->fileHandle);

            // Set the file to upload
            $file_size = filesize($file['tmp_name']);
            curl_setopt($curl, CURLOPT_INFILESIZE, $file_size);

            // Set the read function for cURL
            curl_setopt($curl, CURLOPT_READFUNCTION, array($this, 'readCallback'));

            curl_setopt_array($curl, [
                CURLOPT_POSTFIELDS => json_encode($param),
                CURLOPT_UPLOAD => true,
                CURLOPT_VERBOSE => true,
            ]);
            
        } else {
            $headers = [
                'Accept: application/json',
                'Content-type: application/json'
            ];
            $param = [
                'cors_origin' => "*",
                'new_asset_settings' => [
                    'playback_policy' => ["public"],
                    "max_resolution_tier" => $this->config['resolution'],
                    "encoding_tier" => $this->config['encoding_tier']
                ],
            ];
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($param));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->config['access_token_id'] . ':' . $this->config['secret_key']);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        $curlResult = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->error = 'Error:' . curl_error($curl);
            return false;
        }
        curl_close($curl);
        $response = json_decode($curlResult, true) ?? [];
        if($method != "DELETE" && $method != "PUT") {
            if (empty($response)) {
                $this->error = Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED');
                return false;
            }
        }
        if (isset($response['error'])) {
            $errorMsg = current($response['error']['messages']);
            if($errorMsg == 'Not Found') {
                return true;
            }
            $this->error = Label::getLabel('LBL_MUX_ERROR:_') . $errorMsg;
            return false;
        }
        return $response;
    }

    /**
     * Upload file
     *
     * @param array $file
     * @return bool
     */
    public function uploadVideo($file)
    {
        if(!$this->init()) {
            return false;
        }
        /* Get Upload URL */
        if(!$uploadUrl = static::getUploadUrl()) {
            return false;
        }
        
        /* Upload Video File to Mux */
        $this->execCurlRequest($uploadUrl, 'PUT', $file);
        $uploadEndpoint = static::HOST_URL . static::UPLOAD_ENDPOINT . '/' . $this->uploadId;

        /* Fetch Upload Data for Asset Id/Video Id */
        if (!$uploadData = $this->execCurlRequest($uploadEndpoint, 'GET')) {
            return false;
        }
        $this->videoId = $uploadData['data']['asset_id'];
        return true;
    }

    /**
     * Get Asset details
     *
     * @param string $assetId
     * @return array|bool
     */
    public function get($assetId = '')
    {
        if(!$this->init()) {
            return false;
        }
        $assetUrl = static::HOST_URL . static::ASSET_ENDPOINT . '/' . $assetId;
        if (!$assetData = $this->execCurlRequest($assetUrl, 'GET')) {
            return false;
        }
        return $assetData;
    }

    /**
     * Remove video from Mux
     *
     * @param string $assetId
     * @return bool
     */
    public function remove($assetId) 
    {
        if(!$this->init()) {
            return false;
        }
        $assetUrl = static::HOST_URL . static::ASSET_ENDPOINT . '/' . $assetId;
        $this->execCurlRequest($assetUrl, "DELETE");
        return true;
    }

    /**
     * Get Video Status
     *
     * @param string $assetId
     * @return int
     */
    public function getStatus($assetId)
    {
        if(!$this->init()) {
            return false;
        }
        $assetDetails = $this->get($assetId);
        return $assetDetails['data']['status'] == 'ready' ? 1 : 0;
    }

    public static function getEncodingArr()
    {
        return [
            'baseline' => Label::getLabel('LBL_BASELINE_ENCODING'),
            'smart' => Label::getLabel('LBL_SMART_ENCODING')
        ];
    }

    public static function getResolutionsArr(string $encoding = 'baseline')
    {
        $resolution = [
            'baseline' => [
                '1080p' => Label::getLabel('LBL_1080p'),
            ],
            'smart' => [
                '1080p' => Label::getLabel('LBL_1080p'),
                '1440p' => Label::getLabel('LBL_1440p'),
                '2160p' => Label::getLabel('LBL_2160p'),
            ]
        ];
        return $resolution[$encoding];
    }

    /**
     * Function to read and upload file in chunks
     */
    public function readCallback($resource, $fd, $length) {
        return fread($fd, $length);
    }
}