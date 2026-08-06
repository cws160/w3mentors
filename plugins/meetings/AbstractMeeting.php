<?php

/**
 * Abstract Meeting
 */
abstract class AbstractMeeting extends FatModel
{

    protected $tool;
    protected $settings;
    protected $maxDuration = 120;
    protected $maxLicenses = 9999;

    public function __construct()
    {
        $this->tool = [];
        $this->settings = [];
        parent::__construct();
    }

    /**
     * Initialize Meeting Tool
     * 
     * Validate meeting tool settings
     */
    abstract public function initMeetingTool();

    /**
     * Create New Meeting
     * 
     * @param array $meet
     * @param array $users
     * @param int $userType
     */
    abstract public function createMeeting(array $meet, array $users, int $userType);

    /**
     * Format Meeting Data
     * 
     * @param array $meet
     * @return array $meet
     */
    abstract static function formatMeeting(array $meet): array;

    /**
     * Close Meeting
     * 
     * @param array $meet 
     * @return bool
     */
    abstract public function closeMeeting(array $meet): bool;

    /**
     * Execute Curl Request
     * 
     * @param string $method
     * @param string $url
     * @param array $params
     */
    abstract public function exeCurlRequest(string $method, string $url, array $params);

    /**
     * Remove Licenses
     * 
     * @return bool
     */
    abstract public function removeLicenses(): bool;
}
