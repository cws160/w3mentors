<?php

/**
 * This class is used to handle Google Calendar Event
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class GoogleCalendarEvent extends MyAppModel
{

    const DB_TBL = 'tbl_google_calendar_events';
    const DB_TBL_PREFIX = 'gocaev_';

    /* Entity Type */
    const GOOGLE_EVENTS = 4;

    private $userId;
    private $recordId;
    private $recordType;

    /**
     * Initialize Google Calendar
     * 
     * @param int $userId
     * @param int $recordId
     * @param int $recordType
     */
    public function __construct(int $userId, int $recordId, int $recordType, $id = 0)
    {
        parent::__construct(static::DB_TBL, 'gocaev_id', $id);
        $this->userId = $userId;
        $this->recordId = $recordId;
        $this->recordType = $recordType;
    }

    /**
     * Add Google Events List
     * 
     * @param string $token
     * @param string $startDate
     * @return bool
     */
    public function addEventsList(string $token, string $startDate = NULL)
    {
        $googleCalendar = new GoogleCalendar($this->userId);
        if (!$googleCalendar->addGoogleCalendarEvents($token, $startDate)) {
            $this->error = $googleCalendar->getError();
            return false;
        }
        return true;
    }

    /**
     * Add Lesson Event
     * 
     * @param array $lesson
     * @return bool
     */
    public function addLessonEvent(array $lesson): bool
    {
        $title = str_replace(['{teachlang}', '{duration}'], [$lesson['tlang_name'], $lesson['ordles_duration']],
                Label::getLabel('LBL_{teachlang},_{duration}_MINUTES_OF_LESSON', $lesson['lang_id']));
        $data = [
            'google_token' => $lesson['google_token'],
            'summary' => $title,
            'title' => $title,
            'description' => '',
            'startDateTime' => $lesson['ordles_lesson_starttime'],
            'endDateTime' => $lesson['ordles_lesson_endtime'],
            'timeZone' => MyUtility::getSystemTimezone(),
            'url' => MyUtility::makeFullUrl('Lessons', 'view', [$lesson['ordles_id']], CONF_WEBROOT_DASHBOARD)
        ];
        return $this->addEvent($data);
    }

    /**
     * Add Class Event
     * 
     * @param array $class
     * @param int $userType
     * @return bool
     */
    public function addClassEvent(array $class, int $userType): bool
    {
        $classId = ($userType == User::TEACHER) ? $class['grpcls_id'] : $class['ordcls_id'];
        $data = [
            'google_token' => $class['google_token'],
            'summary' => $class['grpcls_title'],
            'title' => $class['grpcls_title'],
            'description' => $class['grpcls_description'],
            'startDateTime' => $class['grpcls_start_datetime'],
            'endDateTime' => $class['grpcls_end_datetime'],
            'timeZone' => MyUtility::getSystemTimezone(),
            'url' => MyUtility::makeFullUrl('Classes', 'view', [$classId], CONF_WEBROOT_DASHBOARD)
        ];
        return $this->addEvent($data);
    }

    /**
     * Add Event
     * 
     * @param array $data
     * @param int $langId
     * @return bool
     */
    public function addEvent(array $data, int $langId = 0): bool
    {
        $data = $this->eventData($data, $langId);
        $googleCalendar = new GoogleCalendar($this->userId);
        if (!$eventId = $googleCalendar->addEvent($data)) {
            $this->error = $googleCalendar->getError();
            return false;
        }
        $this->assignValues([
            'gocaev_event_id' => $eventId,
            'gocaev_user_id' => $this->userId,
            'gocaev_record_id' => $this->recordId,
            'gocaev_record_type' => $this->recordType
        ]);
        if (!$this->addNew()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Add Google Calendar Events
     * 
     * @param string $eventId
     * @param string $startTime
     * @param string $endTime
     * @return bool
     */
    public function addGoogleCalEvent(string $eventId, string $startTime, string $endTime): bool
    {
        $this->assignValues([
            'gocaev_event_id' => $eventId,
            'gocaev_user_id' => $this->userId,
            'gocaev_record_id' => 0,
            'gocaev_record_type' => self::GOOGLE_EVENTS,
            'gocaev_starttime' => $startTime,
            'gocaev_endtime' => $endTime
        ]);
        if (!$this->addNew([],['gocaev_starttime' => $startTime, 'gocaev_endtime' => $endTime])) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }
    
        /**
     * Delete Google Calendar Events
     * 
     * @param string $eventId
     * @param string $startTime
     * @param string $endTime
     * @return bool
     */
    public function deletGoogleCalEvent(string $eventId): bool
    {
        $where = ['vals' => [$this->userId, '%' . $eventId . '%'], 'smt' => "gocaev_user_id = ? and gocaev_event_id LIKE ?"];
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL, $where)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Delete Event
     * 
     * @param string $googleToken
     * @param string $eventId
     * @return bool
     */
    public function deletEvent(string $googleToken, string $eventId): bool
    {
        $googleCalendar = new GoogleCalendar($this->userId);
        if (!$googleCalendar->deleteEvent($eventId, $googleToken)) {
            $this->error = $googleCalendar->getError();
            return false;
        }
        $where = [
            'vals' => [$this->userId, $eventId],
            'smt' => 'gocaev_user_id = ? AND gocaev_event_id = ?'
        ];
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL, $where)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove Class Events
     * 
     * @return bool
     */
    public function removeClassEvents(): bool
    {
        $gocaevIds = [];
        $events = $this->getEventsByRecordId(true);
        foreach ($events as $event) {
            $googleCalendar = new GoogleCalendar($event['gocaev_user_id']);
            if (!$googleCalendar->deleteEvent($event['gocaev_event_id'], $event['google_token'])) {
                $this->error = $googleCalendar->getError();
                return false;
            }
            $gocaevIds[] = $event['gocaev_id'];
        }
        if (!empty($gocaevIds)) {
            $query = FatApp::getDb()->query("DELETE FROM " . static::DB_TBL . " WHERE gocaev_id IN(" . implode(',', $gocaevIds) . ")");
            if (!$query) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Get Events By Record Id
     * 
     * @param bool $joinSetting
     * @return array
     */
    public function getEventsByRecordId(bool $joinSetting = false): array
    {
        $srch = new SearchBase(static::DB_TBL, 'gocaev');
        if ($joinSetting) {
            $srch->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'us.user_id = gocaev.gocaev_user_id', 'us');
            $srch->addDirectCondition('us.user_google_token IS NOT NULL');
            $srch->addFld('IFNULL(us.user_google_token,"") as google_token');
        }
        $srch->addMultipleFields(['gocaev.*']);
        $srch->addCondition('gocaev.gocaev_record_id', '=', $this->recordId);
        $srch->addCondition('gocaev.gocaev_record_type', '=', $this->recordType);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'gocaev_user_id');
    }

    /**
     * Get Group Class Event
     * 
     * @return null|array
     */
    public function getGroupClassEvent()
    {
        $srch = new SearchBase(static::DB_TBL, 'gocaev');
        $srch->addMultipleFields(['gocaev.*']);
        $srch->addCondition('gocaev.gocaev_record_id', '=', $this->recordId);
        $srch->addCondition('gocaev.gocaev_record_type', '=', $this->recordType);
        $srch->addCondition('gocaev.gocaev_user_id', '=', $this->userId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Remove Class Events
     * 
     * @return bool
     */
    public function removeTeacherPackEvents(): bool
    {
        $gocaevIds = [];
        $events = $this->getPackageEvents(true);
        foreach ($events as $event) {
            $googleCalendar = new GoogleCalendar($event['gocaev_user_id']);
            if (!$googleCalendar->deleteEvent($event['gocaev_event_id'], $event['google_token'])) {
                $this->error = $googleCalendar->getError();
                return false;
            }
            $gocaevIds[] = $event['gocaev_id'];
        }
        if (!empty($gocaevIds)) {
            $query = FatApp::getDb()->query("DELETE FROM " . static::DB_TBL . " WHERE gocaev_id IN(" . implode(',', $gocaevIds) . ")");
            if (!$query) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Remove Class Events
     * 
     * @return bool
     */
    public function removeLearnerPackEvents(): bool
    {
        $gocaevIds = [];
        $events = $this->getPackageEvents(true);
        foreach ($events as $event) {
            $googleCalendar = new GoogleCalendar($event['gocaev_user_id']);
            if (!$googleCalendar->deleteEvent($event['gocaev_event_id'], $event['google_token'])) {
                $this->error = $googleCalendar->getError();
                return false;
            }
            $gocaevIds[] = $event['gocaev_id'];
        }
        if (!empty($gocaevIds)) {
            $query = FatApp::getDb()->query("DELETE FROM " . static::DB_TBL . " WHERE gocaev_id IN(" . implode(',', $gocaevIds) . ")");
            if (!$query) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Get Events By Record Id
     * 
     * @param bool $joinSetting
     * @return array
     */
    public function getPackageEvents(): array
    {
        $srch = new SearchBase(static::DB_TBL, 'gocaev');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = gocaev.gocaev_record_id', 'grpcls');
        $srch->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'us.user_id = gocaev.gocaev_user_id', 'us');
        $srch->addDirectCondition('us.user_google_token IS NOT NULL');
        $srch->addFld('IFNULL(us.user_google_token,"") as google_token');
        $srch->addMultipleFields(['gocaev.*']);
        $srch->addCondition('us.user_id', '=', $this->userId);
        $srch->addCondition('grpcls.grpcls_parent', '=', $this->recordId);
        $srch->addCondition('gocaev.gocaev_record_type', '=', $this->recordType);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public function getEventData(array $userIds, string $startTime, string $endTime): array
    {
        $srch = new SearchBase(static::DB_TBL, 'gocaev');
        $srch->addCondition('gocaev_user_id', 'IN', $userIds);
        $srch->addCondition('gocaev_starttime', '<', $endTime);
        $srch->addCondition('gocaev_endtime', '>', $startTime);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Event Data
     * 
     * @param array $data
     * @return array
     */
    private function eventData(array $data)
    {
        $eventData = [
            'google_token' => $data['google_token'],
            'summary' => $data['summary'],
            'description' => $data['description'],
            'start' => [
                'dateTime' => date('c', strtotime($data['startDateTime'])),
                'timeZone' => $data['timeZone']
            ],
            'end' => [
                'dateTime' => date('c', strtotime($data['endDateTime'])),
                'timeZone' => MyUtility::getSystemTimezone()
            ],
            'sendUpdates' => 'all',
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 1440],
                    ['method' => 'email', 'minutes' => 30],
                    ['method' => 'email', 'minutes' => 10]
                ],
            ],
            'source' => ['title' => $data['title'], 'url' => $data['url']],
        ];
        return $eventData;
    }

}
