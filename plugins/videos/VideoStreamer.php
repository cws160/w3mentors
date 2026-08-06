<?php

class VideoStreamer extends FatModel
{
    private $streamer;
    const TYPE_VIDEO_CIPHER = 'VideoCipher';
    const TYPE_MUX = 'Mux';

    public function __construct()
    {
        parent::__construct();
        $streamer = FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL');
        $this->streamer = new $streamer();
    }

    /**
     * Get uploaded video id
     *
     * @return string
     */
    public function getVideoId()
    {
        return $this->streamer->videoId;
    }

    /**
     * Get video url
     *
     * @param string $id
     * @param boolean $autoPlay
     * @return bool|string
     */
    public function getUrl(string $id, bool $autoPlay = false)
    {
        if (empty($id)) {
            return false;
        }
        if (!$url = $this->streamer->generateUrl($id, $autoPlay)) {
            $this->error = $this->streamer->getError();
            return false;
        }
        return $url;
    }

    /**
     * Validate video file
     *
     * @param array $file
     * @param int $type
     * @return bool
     */
    private function validate(array $file, int $type)
    {
        if ($file['error'] > 0) {
            $this->error = Label::getLabel('LBL_CANNOT_UPLOAD_FILE._PLEASE_CONTACT_YOUR_ADMIN.');
            return false;
        }
        $allowedSize = Afile::getAllowedUploadSize($type);
        if ($file["size"] > $allowedSize) {
            $label = Label::getLabel('LBL_FILE_SIZE_SHOULD_BE_LESS_THEN_{size}_MB');
            $this->error = str_replace('{size}', MyUtility::convertBitesToMb($allowedSize), $label);
            return false;
        }

        $VideoExtentions = Afile::getAllowedExts($type);
        $fileExtention = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (!in_array($fileExtention, $VideoExtentions)) {
            $this->error = Label::getLabel('LBL_VIDEO_EXTENSION_IS_NOT_ALLOWED');
            return false;
        }
        return true;
    }

    /**
     * Upload video 
     *
     * @param array $file
     * @param int $type
     * @return bool
     */
    public function upload(array $file, int $type)
    {
        if (!$this->validate($file, $type)) {
            return false;
        }
        if (!$this->streamer->uploadVideo($file)) {
            $this->error = $this->streamer->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove video
     *
     * @param string $id
     * @return bool
     */
    public function remove(string $id)
    {
        if (empty($id)) {
            return false;
        }
        if (!$this->streamer->remove($id)) {
            $this->error = $this->streamer->getError();
            return false;
        }
        return true;
    }

    /**
     * Removing videos in batches
     *
     * @param array $data
     * @return bool
     */
    public function bulkRemove(array $data)
    {
        if (empty($data)) {
            return true;
        }
        $data = array_chunk($data, 10);
        foreach ($data as $ids) {
            if (!$this->streamer->remove(implode(',', $ids))) {
                $this->error = $this->streamer->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Get video details
     *
     * @param string $id
     * @return bool|array
     */
    public function getDetails(string $id)
    {
        if (empty($id)) {
            return false;
        }
        if (!$response = $this->streamer->get($id)) {
            $this->error = $this->streamer->getError();
            return false;
        }
        return $response;
    }

    /**
     * Check if Video is Ready for Preview
     * 
     * @param string $videoId
     * @return int
     */
    public function getReadyStatus($videoId)
    {
        if (empty($videoId)) {
            return false;
        }
        if(!$status = $this->streamer->getStatus($videoId)) {
            $this->error = $this->streamer->getError();
            return false;
        }
        return $status;
    }

}
