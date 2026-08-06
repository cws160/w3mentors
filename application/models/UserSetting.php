<?php

/**
 * This class is used to handle User Setting
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserSetting extends FatModel
{

    const DB_TBL = 'tbl_user_settings';
    const DB_TBL_PREFIX = 'user_';

    private $userId = 0;

    public function __construct(int $userId = 0)
    {
        $this->userId = $userId;
    }

    /**
     * Save Settings Data
     * 
     * @param array $data
     * @return bool
     */
    public function saveData(array $data = []): bool
    {
        if ($this->userId < 1) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $data['user_id'] = $this->userId;
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues($data);
        if (!$record->addNew([], $record->getFlds())) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Settings
     * 
     * @param int $userId
     * @param array $flds
     * @return null|array
     */
    public static function getSettings(int $userId, array $flds = [])
    {
        $srch = new SearchBase(UserSetting::DB_TBL);
        $srch->addCondition('user_id', '=', $userId);
        $srch->addMultipleFields($flds);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Google Token
     * 
     * @return string
     */
    public function getGoogleToken(): string
    {
        $srch = new SearchBase(UserSetting::DB_TBL);
        $srch->addCondition('user_id', '=', $this->userId);
        $srch->addFld('user_google_token');
        $srch->doNotCalculateRecords();
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return $record['user_google_token'] ?? '';
    }

    /**
     * Get Google Refresh Token
     * 
     * @return string
     */
    public function getGoogleRefreshToken(): string
    {
        $srch = new SearchBase(UserSetting::DB_TBL);
        $srch->addCondition('user_id', '=', $this->userId);
        $srch->addFld('user_google_refresh_token');
        $srch->doNotCalculateRecords();
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return $record['user_google_refresh_token'] ?? '';
    }

    /**
     * validate Booking Before function
     *
     * @param string $startTime
     * @param int $bookingBefore
     * @return bool
     */
    public static function validateBookingBefore(string $startTime, int $bookingBefore): bool
    {
        $bookingBefore = FatUtility::int($bookingBefore ?? 0);
        $validDate = strtotime('+' . $bookingBefore . 'hours');
        if (strtotime($startTime) < $validDate) {
            return false;
        }
        return true;
    }

    /**
     * Validate Free Trial
     * 
     * @param int $learnerId
     * @param string $startTime
     * @param string $endTime
     * @return bool
     */
    public function validateFreeTrial(int $learnerId, string $startTime, string $endTime): bool
    {
        if (!FatApp::getConfig('CONF_ENABLE_FREE_TRIAL')) {
            $this->error = Label::getLabel('LBL_FREE_TRIAL_IS_DISABLED_BY_ADMIN');
            return false;
        }
        $startUnix = strtotime($startTime);
        $endUnix = strtotime($endTime);
        $trialDuration = FatApp::getConfig('CONF_TRIAL_LESSON_DURATION');
        if (($trialDuration * 60) != ($endUnix - $startUnix)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $user = new User($this->userId);
        if (!$teacher = $user->validateTeacher(MyUtility::getSiteLangId(), $learnerId)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if (empty($teacher['user_trial_enabled'])) {
            $this->error = Label::getLabel('LBL_FREE_TRIAL_IS_DISABLED_BY_TEACHER');
            return false;
        }
        $bookingBefore = FatUtility::int($teacher['user_book_before'] ?? 0);
        if (!userSetting::validateBookingBefore($startTime, $bookingBefore)) {
            $this->error = Label::getLabel('LBL_TEACHER_DISABLE_THE_BOOKING');
            return false;
        }
        if (Lesson::isTrailAvailed($this->userId, $teacher['user_id'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_ALLREADY_AVAILED_FREE_TRIAL_LESSON'));
        }
        $availability = new Availability($this->userId);
        /** check teacher availability */
        if (!$availability->isAvailable($startTime, $endTime)) {
            $this->error = $availability->getError();
            return false;
        }

        /** check teacher slot availability */
        if (!$availability->isUserAvailable($startTime, $endTime)) {
            $this->error = $availability->getError();
            return false;
        }
        /** check Learner slot availability */
        $availability = new Availability($learnerId);
        if (!$availability->isUserAvailable($startTime, $endTime)) {
            $this->error = $availability->getError();
            return false;
        }
        return true;
    }

    /**
     * Find in slots
     * 
     * @param array $slots
     * @return bool
     */
    public static function findInSlots(array $slots)
    {
        $srch = new SearchBase(static::DB_TBL);
        foreach ($slots as $slot) {
            $srch->addDirectCondition('JSON_CONTAINS(`user_slots`, \'"' . $slot . '"\')', 'OR');
        }
        $srch->doNotCalculateRecords();
        $srch->addFld('count(*) as count');
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return $record['count'] > 0 ? true : false;
    }
    
    public function removeGoogleCalendarData()
    {
        $settingData = [
            'user_google_token' => NULL,
            'user_google_refresh_token' => NULL,
            'user_google_event_sync_token' => NULL,
            'user_google_event_sync_date' => NULL,
            'user_google_event_watch_id' => NULL,
            'user_google_event_watch_expiration' => NULL,
            'user_google_event_watch_resource_id' => NULL,
        ];
        $usrStngObj = new UserSetting($this->userId);
        if (!$usrStngObj->saveData($settingData)) {
            $this->error = $usrStngObj->getError();
            return false;
        }
        return true;
    }
}
