<?php

/**
 * This class is used to handle Rating Session Logs
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SessionLog extends FatModel
{

    const DB_TBL = 'tbl_session_logs';
    const LESSON_RESCHEDULED_LOG = 1;
    const LESSON_CANCELLED_LOG = 2;

    private $recordId;
    private $recordType;

    /**
     * Initialize Session Log
     * @param int $recordId
     * @param int $recordType
     */
    public function __construct(int $recordId, int $recordType)
    {
        parent::__construct();
        $this->recordId = $recordId;
        $this->recordType = $recordType;
    }

    /**
     * Setup Logs
     * 
     * @param array $log
     * @return bool
     */
    public function setup(array $log): bool
    {
        $data = [
            'sesslog_record_id' => $this->recordId,
            'sesslog_record_type' => $this->recordType,
            'sesslog_created' => date('Y-m-d H:i:s')
        ];
        $log = array_merge($log, $data);
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues($log);
        if (!$record->addNew()) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Add Scheduled Lesson Log
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return bool
     */
    public function addScheduledLessonLog(int $userId, string $startTime, string $endTime): bool
    {
        $log = [
            'sesslog_user_id' => $userId,
            'sesslog_user_type' => User::LEARNER,
            'sesslog_prev_status' => Lesson::UNSCHEDULED,
            'sesslog_changed_status' => Lesson::SCHEDULED,
            'sesslog_changed_starttime' => $startTime,
            'sesslog_changed_endtime' => $endTime
        ];
        return $this->setup($log);
    }

    /**
     * Add Rescheduled Lesson Log
     * 
     * @param int $userId
     * @param array $lesson
     * @param array $post
     * @return bool
     */
    public function addRescheduledLessonLog(int $userId, array $lesson, array $post): bool
    {
        $log = [
            'sesslog_user_id' => $userId,
            'sesslog_user_type' => User::LEARNER,
            'sesslog_prev_status' => Lesson::SCHEDULED,
            'sesslog_changed_status' => Lesson::SCHEDULED,
            'sesslog_prev_starttime' => $lesson['ordles_lesson_starttime'],
            'sesslog_prev_endtime' => $lesson['ordles_lesson_endtime'],
            'sesslog_changed_starttime' => $post['ordles_lesson_starttime'],
            'sesslog_changed_endtime' => $post['ordles_lesson_endtime'],
            'sesslog_comment' => $post['comment']
        ];
        return $this->setup($log);
    }

    /**
     * Add Unscheduled Lesson Log
     * 
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function addUnscheduledLessonLog(int $userId, array $data): bool
    {
        $log = [
            'sesslog_record_id' => $this->recordId,
            'sesslog_record_type' => $this->recordType,
            'sesslog_user_id' => $userId,
            'sesslog_user_type' => User::TEACHER,
            'sesslog_prev_status' => $data['ordles_status'],
            'sesslog_changed_status' => Lesson::UNSCHEDULED,
            'sesslog_prev_starttime' => $data['ordles_lesson_starttime'],
            'sesslog_prev_endtime' => $data['ordles_lesson_endtime'],
            'sesslog_comment' => $data['comment']
        ];
        return $this->setup($log);
    }

    /**
     * Add Completed Lesson Log
     * 
     * @param int $userId
     * @param int $userType
     * @return bool
     */
    public function addCompletedLessonLog(int $userId, int $userType): bool
    {
        $log = [
            'sesslog_user_id' => $userId,
            'sesslog_user_type' => $userType,
            'sesslog_prev_status' => Lesson::SCHEDULED,
            'sesslog_changed_status' => Lesson::COMPLETED
        ];
        return $this->setup($log);
    }

    /**
     * Add Canceled Lesson Log
     * 
     * @param int $userId
     * @param int $userType
     * @param int $status
     * @param string $comment
     * @return bool
     */
    public function addCanceledLessonLog(int $userId, int $userType, int $status, string $comment): bool
    {
        $log = [
            'sesslog_user_id' => $userId,
            'sesslog_user_type' => $userType,
            'sesslog_prev_status' => $status,
            'sesslog_changed_status' => Lesson::CANCELLED,
            'sesslog_comment' => $comment
        ];
        return $this->setup($log);
    }

    /**
     * Add Canceled Class Log
     * 
     * @param int $userId
     * @param int $userType
     * @param string $comment
     * @return bool
     */
    public function addCanceledClassLog(int $userId, int $userType, string $comment): bool
    {
        $log = [
            'sesslog_user_id' => $userId,
            'sesslog_user_type' => $userType,
            'sesslog_prev_status' => GroupClass::SCHEDULED,
            'sesslog_changed_status' => GroupClass::CANCELLED,
            'sesslog_comment' => $comment
        ];
        return $this->setup($log);
    }

    /**
     * Add Completed Class Log
     * 
     * @param int $userId
     * @param int $userType
     * @return bool
     */
    public function addCompletedClassLog(int $userId, int $userType): bool
    {
        $log = [
            'sesslog_user_id' => $userId,
            'sesslog_user_type' => $userType,
            'sesslog_prev_status' => GroupClass::SCHEDULED,
            'sesslog_changed_status' => GroupClass::COMPLETED,
        ];
        return $this->setup($log);
    }

    /**
     * Get Log Types
     * 
     * @return array
     */
    public static function getLogType(): array
    {
        return [
            static::LESSON_RESCHEDULED_LOG => Label::getLabel('LBL_RESCHEDULED_LOG'),
            static::LESSON_CANCELLED_LOG => Label::getLabel('LBL_CANCELLED_LOG')
        ];
    }

}
