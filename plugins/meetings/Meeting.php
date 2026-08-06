<?php

/**
 * This class is used to handle Meeting
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Meeting extends FatModel
{

    private $tool;
    private $userId;
    private $userType;

    public const DB_TBL = 'tbl_meetings';

    /**
     * Initialize Meeting Model
     * 
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $userId, int $userType)
    {
        $this->userId = $userId;
        $this->userType = $userType;
        parent::__construct();
    }

    /**
     * Initialize Meeting
     * 
     * @return bool
     */
    public function initMeeting(int $toolId = 0): bool
    {
        $this->tool = MeetingTool::getDetail($toolId);
        if (empty($this->tool) || !class_exists($this->tool['metool_code'])) {
            $this->error = Label::getLabel('LBL_MEETING_TOOL_NOT_FOUND');
            return false;
        }
        return true;
    }

    /**
     * Join Lesson Meeting
     * 
     * 1. Prepare lesson meeting data 
     * 2. Create lesson meeting for teacher
     * 3. Create lesson meeting for learner
     * 
     * @param array $lesson
     * @return bool|array
     */
    public function joinLesson(array $lesson)
    {
        $recordId = $lesson['ordles_id'];
        $recordType = AppConstant::LESSON;
        /* Prepare lesson meeting data */
        $meetData = [
            'id' => $recordId . '_' . $recordType,
            'title' => static::lessonTitle($lesson),
            'default_title' => static::lessonDefaultTitle($lesson),
            'duration' => $lesson['ordles_duration'],
            'starttime' => $lesson['ordles_lesson_starttime'],
            'endtime' => $lesson['ordles_lesson_endtime'],
            'timezone' => MyUtility::getSystemTimezone(),
            'recordId' => $recordId,
            'recordType' => $recordType
        ];
        /* Create lesson meeting for teacher */
        $users = $this->getUsersDetail($lesson);
        if (!$meet = $this->createMeeting($meetData, $users, User::TEACHER)) {
            return false;
        }
        if ($this->userType == User::TEACHER) {
            return $meet;
        }
        /* Create lesson meeting for learner */
        if (!$meet = $this->createMeeting($meetData, $users, User::LEARNER)) {
            return false;
        }
        return $meet;
    }

    /**
     * Join Class Meeting
     * 
     * 1. Prepare group class meeting data
     * 2. Create class meeting for teacher
     * 3. Create class meeting for learner
     * 
     * @param array $class
     * @return bool|array
     */
    public function joinClass(array $class)
    {
        $recordId = $class['grpcls_id'];
        $recordType = AppConstant::GCLASS;
        /* Prepare group class meeting data */
        $meetData = [
            'id' => $recordId . '_' . $recordType,
            'title' => $class['grpcls_title'],
            'default_title' => $class['grpcls_title_default'],
            'duration' => $class['grpcls_duration'],
            'starttime' => $class['grpcls_start_datetime'],
            'endtime' => $class['grpcls_end_datetime'],
            'timezone' => MyUtility::getSystemTimezone(),
            'recordId' => $recordId, 'recordType' => $recordType,
            'groupUserIds' => $class['groupUserIds']
        ];
        /* Create class meeting for teacher */
        $users = $this->getUsersDetail($class);
        if (!$meet = $this->createMeeting($meetData, $users, User::TEACHER)) {
            return false;
        }
        if ($this->userType == User::TEACHER) {
            return $meet;
        }
        /* Create class meeting for learner */
        $users = $this->getUsersDetail($class);
        if (!$meet = $this->createMeeting($meetData, $users, User::LEARNER)) {
            return false;
        }
        return $meet;
    }

    /**
     * Create Meeting
     * 
     * 1. Get already created meeting
     * 2. Initialize meeting tool
     * 3. Create new meeting on Provider
     * 4. Create new meeting on W3mentors
     * 
     * @param array $meet
     * @param array $users
     * @param int $userType
     * @param bool|array $meet
     */
    public function createMeeting(array $meet, array $users, int $userType)
    {
        /* Initialize meeting tool */
        $tool = new $this->tool['metool_code']();
        if (!$tool->initMeetingTool()) {
            $this->error = $tool->getError();
            return false;
        }
        /* Get already created meeting */
        $user = $users[$userType];
        $recordId = FatUtility::int($meet['recordId']);
        $recordType = FatUtility::int($meet['recordType']);
        $meeting = static::getMeeting($user['user_id'], $recordId, $recordType);
        if (!empty($meeting)) {
            $details = json_decode($meeting['meet_details'], true);
            $meeting['meet_details'] = $tool::formatMeeting($details);
            return $meeting;
        }
        /* Create new meet on meeting provider */
        if (!$res = $tool->createMeeting($meet, $users, $userType)) {
            $this->error = $tool->getError();
            return false;
        }
        /* Create new meeting on W3mentors */
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues([
            'meet_user_id' => $user['user_id'],
            'meet_record_id' => $meet['recordId'],
            'meet_record_type' => $meet['recordType'],
            'meet_metool_id' => $this->tool['metool_id'],
            'meet_app_url' => $res['appUrl'] ?? null,
            'meet_join_url' => $res['joinUrl'],
            'meet_details' => json_encode($res),
            'meet_created' => date('Y-m-d H:i:s'),
        ]);
        if (!$record->addNew()) {
            $this->error = $record->getError();
            return false;
        }
        return [
            'metool_code' => $this->tool['metool_code'],
            'metool_iframe' => $this->tool['metool_iframe'],
            'meet_metool_id' => $this->tool['metool_id'],
            'meet_app_url' => $res['appUrl'] ?? null,
            'meet_join_url' => $res['joinUrl'],
            'meet_details' => $tool::formatMeeting($res)
        ];
    }

    /**
     * Get Meeting Detail
     * 
     * @param int $userId
     * @param int $recordId
     * @param int $recordType
     * @return bool|array
     */
    public static function getMeeting(int $userId, int $recordId, int $recordType)
    {
        $srch = new SearchBase(static::DB_TBL, 'meet');
        $srch->joinTable(MeetingTool::DB_TBL, 'INNER JOIN', 'metool.metool_id=meet.meet_metool_id', 'metool');
        $srch->addMultipleFields(['metool_code', 'metool_iframe', 'metool_settings', 'meet_id', 'meet_metool_id',
            'meet_app_url', 'meet_join_url', 'meet_details', 'meet_record_type', 'meet_record_id', 'meet_user_id', 'meet_playback_url']);
        $srch->addCondition('meet.meet_record_type', '=', $recordType);
        $srch->addCondition('meet.meet_record_id', '=', $recordId);
        $srch->addCondition('meet.meet_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet()) ?? false;
    }

    /**
     * End Meeting (class|lesson)
     * 
     * @param int $recordId
     * @param int $recordType
     * @return bool
     */
    public function endMeeting(int $recordId, int $recordType): bool
    {
        $meeting = static::getMeeting($this->userId, $recordId, $recordType);
        if (empty($meeting)) {
            $this->error = Label::getLabel('LBL_SESSION_NOT_FOUND');
            return false;
        }
        $meetClass = $this->tool['metool_code'];
        $meet = new $meetClass($this->userId, $this->userType);
        if (!$meet->initMeetingTool()) {
            $this->error = $meet->getError();
            return false;
        }
        if (!$meet->closeMeeting($meeting)) {
            $this->error = $meet->getError();
            return false;
        }
        if ($this->userType == User::TEACHER || $recordType == AppConstant::LESSON) {
            $stmt = ['smt' => 'meet_record_id = ? AND meet_record_type = ?', 'vals' => [$recordId, $recordType]];
        } else {
            $stmt = ['smt' => 'meet_id = ? AND meet_user_id = ?', 'vals' => [$meeting['meet_id'], $this->userId]];
        }
        $record = new TableRecord(Meeting::DB_TBL);
        $record->setFldValue('meet_updated', date('Y-m-d H:i:s'));
        $record->setFldValue('meet_playback_url', $meet->fetchPlaybackUrl($meeting));
        if (!$record->update($stmt)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Users Details
     * 
     * @param array $row
     * @return array $users
     */
    private function getUsersDetail(array $row): array
    {
        $users = [
            User::TEACHER => [
                'user_type' => User::TEACHER,
                'user_id' => $row['teacher_id'],
                'user_email' => $row['teacher_email'],
                'user_full_name' => $row['teacher_full_name'],
                'user_first_name' => $row['teacher_first_name'],
                'user_last_name' => $row['teacher_last_name'],
                'user_timezone' => $row['teacher_timezone'],
                'user_image' => static::userPhoto($row['teacher_id']),
            ]
        ];
        if (!empty($row['learner_id'])) {
            $users[User::LEARNER] = [
                'user_type' => User::LEARNER,
                'user_id' => $row['learner_id'],
                'user_email' => $row['learner_email'],
                'user_full_name' => $row['learner_full_name'],
                'user_first_name' => $row['learner_first_name'],
                'user_last_name' => $row['learner_last_name'],
                'user_timezone' => $row['learner_timezone'],
                'user_image' => static::userPhoto($row['learner_id'])
            ];
        }
        return $users;
    }

    /**
     * Get Profile Photo
     * 
     * @param int $userId
     * @return string
     */
    private static function userPhoto(int $userId): string
    {
        $params = [Afile::TYPE_USER_PROFILE_IMAGE, $userId, Afile::SIZE_MEDIUM];
        return MyUtility::makeFullUrl('Image', 'show', $params, CONF_WEBROOT_FRONT_URL);
    }

    /**
     * Lesson Title
     * 
     * @param array $lesson
     * @return string
     */
    private static function lessonTitle(array $lesson): string
    {
        return str_replace(
                ['{teachlang}', '{duration}'],
                [$lesson['ordles_tlang_name'], $lesson['ordles_duration']],
                Label::getLabel('LBL_{teachlang},_{duration}_MINUTES_OF_LESSON')
        );
    }

    /**
     * Lesson Default Title
     * 
     * @param array $lesson
     * @return string
     */
    private static function lessonDefaultTitle(array $lesson): string
    {
        return str_replace(
                ['{teachlang}', '{duration}'],
                [$lesson['ordles_tlang_name_default'], $lesson['ordles_duration']],
                Label::getLabel('LBL_{teachlang},_{duration}_MINUTES_OF_LESSON', FatApp::getConfig('CONF_DEFAULT_LANG'))
        );
    }

    /**
     * Check Meeting License 
     *
     * 1. Initialize Meeting Tool
     * 2. Compare Free Meeting Duration
     * 3. Compare Assigned Licenses
     * 4. Send Email To Admin 
     * 
     * @param string $startTime
     * @param string $endTime
     * @return boolean
     */
    public static function checkLicense(string $startTime, string $endTime): bool
    {
        $tool = MeetingTool::getDetail(0);
        if (empty($tool)) {
            return true;
        }
        /* Initialize Meeting Tool */
        $meet = new $tool['metool_code']();
        if (!$meet->initMeetingTool()) {
            return false;
        }
        /* Compare Free Meeting Duration */
        $freeDuration = $meet->getFreeMinutes();
        if (((strtotime($startTime) - strtotime($endTime)) / 60) < $freeDuration) {
            return true;
        }
        /* Compare Assigned Licenses */
        $lessons = Lesson::getScheduledCount($startTime, $endTime, $freeDuration);
        $classes = GroupClass::getScheduledCount($startTime, $endTime, $freeDuration);
        $totalSessions = $lessons['totalCount'] + $classes['totalCount'];
        if ($meet->getLicences() > $totalSessions) {
            return true;
        }
        /* Send Email To Admin */
        $language = MyUtility::getSystemLanguage();
        $mail = new FatMailer($language['language_id'], 'license_alert');
        $timezone = CONF_SERVER_TIMEZONE . " " . MyDate::getOffset(CONF_SERVER_TIMEZONE);
        $mail->setVariables([
            '{session_count}' => $totalSessions, '{meeting_tool}' => $tool['metool_code'],
            '{start_time}' => min($lessons['startTime'], $classes['startTime']) . ' ' . $timezone,
            '{end_time}' => max($lessons['endTime'], $classes['endTime']) . ' ' . $timezone
        ]);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            return false;
        }
        return true;
    }

    /**
     * Remove Licenses
     * 
     * @return bool
     */
    public function removeLicenses(): bool
    {
        $tool = new $this->tool['metool_code']();
        if (!$tool->initMeetingTool()) {
            $this->error = $tool->getError();
            return false;
        }
        if (!$tool->removeLicenses()) {
            $this->error = $tool->getError();
            return false;
        }
        return true;
    }

    public static function getPlaybacks(int $userId, array $recordIds, int $recordType): array
    {
        $recordIds = FatUtility::int($recordIds);
        $srch = new SearchBase(static::DB_TBL, 'meet');
        $srch->addMultipleFields(['meet_record_id', 'meet_playback_url']);
        $srch->addCondition('meet.meet_record_type', '=', $recordType);
        $srch->addCondition('meet.meet_record_id', 'IN', $recordIds);
        $srch->addCondition('meet.meet_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }
	
    /**
     * Handle zoom user accounts
     *
     * @param array $user
     * @param string $action
     * @return void
     */
    public function handleUserAccountRequest(array $user, $action = '')
    {
        if ($this->tool['metool_code'] != ZoomMeeting::KEY) {
            return true;
        }
        /* Initialize meeting tool */
        $tool = new $this->tool['metool_code']();
        if (!$tool->initMeetingTool()) {
            $this->error = $tool->getError();
            return false;
        }

        if ('verify' == $action) {
            if (!$tool->verifyAccount($user)) {
                $this->error = $tool->getError();
                return false;
            }
            return true;
        }

        if (!$tool->handleCreateUser($user)) {
            $this->error = $tool->getError();
            return false;
        }

        return true;
    }

    public static function zoomVerificationCheck(int $teacherId)
    {
        /* NOTE: need to handle API calls if required */

        if (1 == FatApp::getConfig('CONF_ZOOM_ISV_ENABLED', 0)) {
            return true;
        }
        $meetTool = MeetingTool::getDetail(0);
        if ($meetTool['metool_code'] != ZoomMeeting::KEY) {
            return true;
        }

        /* teacher check */
        $verified = false;
        $settingData = UserSetting::getSettings($teacherId, ['user_zoom_status']);

        $msg = Label::getLabel('MSG_EITHER_TEACHER_ZOOM_ACCOUNT_NOT_CREATED_OR_UN_VERIFIED');
        if (is_array($settingData) && count($settingData) > 0) {
            if (ZoomMeeting::ACC_SYNCED_AND_VERIFIED == $settingData['user_zoom_status']) {
                $verified = true;
            } elseif (AppConstant::INACTIVE == $settingData['user_zoom_status']) {
                $verified = true;
            }
        }
        if (true !== $verified) {
            if (FatUtility::isAjaxCall() || API_CALL) {
                FatUtility::dieJsonError($msg);
            } else {
                Message::addErrorMessage($msg);
                FatApp::redirectUser(MyUtility::makeUrl(''));
            }
        }
        return true;
    }
	

}
