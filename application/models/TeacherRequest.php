<?php

/**
 * This class is used to handle Teach Languages
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeacherRequest extends MyAppModel
{

    const DB_TBL = 'tbl_teacher_requests';
    const DB_TBL_PREFIX = 'tereq_';
    const SESSION_ELEMENT = 'TEACHER_REQUEST';
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_CANCELLED = 2;

    /**
     * Initialize Teacher Request
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'tereq_id', $id);
    }

    /**
     * Start Session
     * 
     * @param array $user
     * @return void
     */
    public static function startSession(array $user): void
    {
        $_SESSION[static::SESSION_ELEMENT] = $user;
    }

    /**
     * Get Session
     * 
     * @param string $key
     * @return int|string|array
     */
    public static function getSession(string $key = null)
    {
        if ($key === null) {
            return $_SESSION[static::SESSION_ELEMENT] ?? [];
        }
        return $_SESSION[static::SESSION_ELEMENT][$key] ?? false;
    }

    /**
     * Close Session
     */
    public static function closeSession()
    {
        unset($_SESSION[static::SESSION_ELEMENT]);
    }

    /**
     * Get Statuses
     * 
     * @param int $key
     * @param int $langId
     * @return string|array
     */
    public static function getStatuses(int $key = null, int $langId = 0)
    {
        $arr = [
            static::STATUS_PENDING => Label::getLabel('LBL_Pending', $langId),
            static::STATUS_APPROVED => Label::getLabel('LBL_Approved', $langId),
            static::STATUS_CANCELLED => Label::getLabel('LBL_Cancelled_Teacher_Req', $langId)
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Data
     * 
     * @param int $userId
     * @return type
     */
    public static function getData(int $userId)
    {
        $srch = new SearchBase(TeacherRequest::DB_TBL, 'tr');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'u.user_id = tr.tereq_user_id', 'u');
        $srch->addMultiplefields(['tereq_attempts', 'tereq_id', 'tereq_status']);
        $srch->addCondition('tereq_user_id', '=', FatUtility::int($userId));
        $srch->addOrder('tereq_id', 'desc');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Save Data
     * 
     * @param array $post
     * @param int $userId
     * @return bool
     */
    public function saveData(array $post, int $userId): bool
    {
        if ($userId < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $data = [
            'tereq_user_id' => $userId,
            'tereq_date' => date('Y-m-d H:i:s'),
            'tereq_status' => static::STATUS_PENDING,
            'tereq_language_id' => $post['tereq_language_id'],
            'utrequest_language_code' => $post['utrequest_language_code'],
            'tereq_reference' => $post['tereq_reference'] ?? $userId . '-' . time(),
        ];
        $this->assignValues($data);
        $this->setFldValue('tereq_attempts', 1, true);
        if (!$this->addNew([], ['tereq_attempts' => 'mysql_func_utrequest_attempts+1'])) {
            return false;
        }
        $tereq_id = $this->getMainTableRecordId();
        $requestValues = $post;
        $requestValues['utrvalue_utrequest_id'] = $tereq_id;
        $requestValues['utrvalue_user_teach_slanguage_id'] = json_encode($post['utrvalue_user_teach_slanguage_id']);
        $requestValues['utrvalue_user_language_speak'] = json_encode($post['utrvalue_user_language_speak']);
        $requestValues['utrvalue_user_language_speak_proficiency'] = json_encode($post['utrvalue_user_language_speak_proficiency']);
        $teacherRequestValue = new TeacherRequest();
        $teacherRequestValue->assignValues($requestValues);
        if (!$teacherRequestValue->save()) {
            $this->error = $teacherRequestValue->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Request Count
     * 
     * @param int $userId
     * @return int
     */
    public static function getRequestCount(int $userId): int
    {
        $srch = new SearchBase(TeacherRequest::DB_TBL);
        $srch->addCondition('tereq_user_id', '=', $userId);
        $srch->addCondition('tereq_step', '=', 5);
        $srch->getResultSet();
        return $srch->recordCount();
    }

    /**
     * Get Request By User Id
     * 
     * @param int $userId
     * @return null|array
     */
    public static function getRequestByUserId(int $userId, int $status = null)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('tereq_user_id', '=', $userId);
        if (!is_null($status)) {
            $srch->addCondition('tereq_status', '=', $status);
        }
        $srch->addOrder('tereq_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }
    
}
