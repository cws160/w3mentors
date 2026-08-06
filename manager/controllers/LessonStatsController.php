<?php

/**
 * Lesson Stats Controller is used for Lesson Stats handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class LessonStatsController extends AdminBaseController
{

    /**
     * Initialize Lesson Stats
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewLessonStatsReport();
    }

    /**
     * Render Lesson Stats Search Form
     */
    public function index()
    {
        $this->set('frmSearch', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Lesson Stats
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = $this->getSearchObj($post);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = sesslog_user_id', 'user');
        $srch->addCondition('sesslog_changed_status', 'IN', [Lesson::SCHEDULED, Lesson::CANCELLED]);
        $srch->addMultipleFields([
            'user_first_name', 'user_last_name', 'user_is_teacher', 'user_id', 'user_email',
            'SUM(IF(sesslog_changed_status = ' . Lesson::SCHEDULED . ' and sesslog_prev_status = ' . Lesson::SCHEDULED . ',1, 0)) as rescheduledCount',
            'SUM(IF(sesslog_changed_status = ' . Lesson::CANCELLED . ',1, 0)) as cancelledCount',
        ]);
        if (!empty($post['user'])) {
            $fullName = 'mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . trim($post['user']) . '%', 'AND', true);
        }
        $srch->addGroupBy('sesslog_user_id');
        $srch->addHaving('rescheduledCount', '>', 0);
        $srch->addHaving('cancelledCount', '>', 0, 'OR');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $this->sets([
            'logs' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'post' => $post,
            'recordCount' => $srch->recordCount(),
            'page' => $post['pageno'],
            'pageCount' => $srch->pages()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * View Logs
     */
    public function viewLogs($userId, $lsnStatus)
    {
        $frm = $this->getLogSearchForm();
        $frm->fill(['user_id' => $userId, 'reportType' => $lsnStatus]);
        $this->sets([
            'frm' => $frm,
            'user_id' => $userId, 'reportType' => $lsnStatus
        ]);
        $this->_template->render();
    }

    /**
     * Export Lesson Stats
     */
    public function exportReport()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = $this->getSearchObj($post);
        $srch->joinTable(Lesson::DB_TBL, 'INNER JOIN', 'ordles.ordles_id = sesslog_record_id', 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordles.ordles_teacher_id', 'teacher');
        $srch->addMultipleFields([
            'learner.user_first_name as learner_first_name', 'learner.user_last_name as learner_last_name',
            'teacher.user_first_name as teacher_first_name', 'teacher.user_last_name as teacher_last_name',
            'sesslog.*', 'ordles_order_id as order_id', 'ordles_id',
        ]);
        $srch->doNotLimitRecords();
        $srch->doNotLimitRecords();
        $srch->addOrder('sesslog_created', 'DESC');
        $resultSet = $srch->getResultSet();
        $sheetData = [];
        $heading = [
            Label::getLabel('LBL_TEACHER_NAME'), Label::getLabel('LBL_LEARNER_NAME'),
            Label::getLabel('LBL_ORDER_ID'), Label::getLabel('LBL_LESSON_ID')
        ];
        if (SessionLog::LESSON_RESCHEDULED_LOG == $post['reportType']) {
            $heading = array_merge($heading, [Label::getLabel('LBL_PREV_START_TIMINGS'), Label::getLabel('LBL_PREV_END_TIMINGS')]);
        }
        $heading = array_merge($heading, [
            Label::getLabel('LBL_PREV_STATUS'), Label::getLabel('LBL_ACTION_PERFORMED'),
            Label::getLabel('LBL_ADDED_ON'), Label::getLabel('LBL_REASON')
        ]);
        array_push($sheetData, $heading);
        $statusArr = Lesson::getStatuses();
        while ($row = Fatapp::getDb()->fetch($resultSet)) {
            $data = [
                $row['teacher_first_name'] . ' ' . $row['teacher_last_name'],
                $row['learner_first_name'] . ' ' . $row['learner_last_name'],
                Order::formatOrderId($row['order_id']), $row['ordles_id']
            ];
            if (SessionLog::LESSON_RESCHEDULED_LOG == $post['reportType']) {
                $data = array_merge($data, [MyDate::formatDate($row['sesslog_prev_starttime']), MyDate::formatDate($row['sesslog_prev_endtime'])]);
            }
            $data = array_merge($data, [
                $statusArr[$row['sesslog_prev_status']], $statusArr[$row['sesslog_changed_status']],
                MyDate::formatDate($row['sesslog_created']), $row['sesslog_comment']
            ]);
            array_push($sheetData, $data);
        }
        CommonHelper::convertToCsv($sheetData, 'STATS_' . date("Y-m-d") . '.csv', ',');
        exit;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_USER'), 'user', '', ['id' => 'user', 'autocomplete' => 'off']);
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'fromDate', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'toDate', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'user_id')->requirements()->setIntPositive();
        $frm->addHiddenField('', 'reportType')->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Get Search Object
     * 
     * @param array $post
     * @return SearchBase
     */
    private function getSearchObj(array $post): SearchBase
    {
        $srch = new SearchBased(SessionLog::DB_TBL, 'sesslog');
        $srch->addCondition('sesslog_user_type', 'IN', [User::LEARNER, User::TEACHER]);
        $srch->addCondition('sesslog_record_type', '=', AppConstant::LESSON);
        if (!empty($post['fromDate'])) {
            $srch->addCondition('sesslog_created', '>=', MyDate::formatToSystemTimezone($post['fromDate'] . ' 00:00:00'));
        }
        if (!empty($post['toDate'])) {
            $srch->addCondition('sesslog_created', '<=', MyDate::formatToSystemTimezone($post['toDate'] . ' 23:59:59'));
        }
        if (!empty($post['user_id'])) {
            $srch->addCondition('sesslog_user_id', '=', $post['user_id']);
        }
        if (!empty($post['reportType'])) {
            if ($post['reportType'] == SessionLog::LESSON_RESCHEDULED_LOG) {
                $srch->addCondition('sesslog_prev_status', '=', Lesson::SCHEDULED);
                $srch->addCondition('sesslog_changed_status', '=', Lesson::SCHEDULED);
            } else {
                $srch->addCondition('sesslog_changed_status', '=', Lesson::CANCELLED);
            }
        }
        return $srch;
    }

    public function searchLog()  {
        $frm = $this->getLogSearchForm();
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $logTypeArr = SessionLog::getLogType();
        if (!array_key_exists($post['reportType'], $logTypeArr)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $user = User::getAttributesById($post['user_id'], ['user_first_name', 'user_last_name', 'user_id']);
        if (empty($user)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = $this->getSearchObj($post);
        $srch->joinTable(Lesson::DB_TBL, 'INNER JOIN', 'ordles.ordles_id = sesslog_record_id', 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordles.ordles_teacher_id', 'teacher');
        $srch->addMultipleFields([
            'learner.user_first_name as learner_first_name', 'learner.user_last_name as learner_last_name',
            'teacher.user_first_name as teacher_first_name', 'teacher.user_last_name as teacher_last_name',
            'sesslog.*', 'ordles_order_id as order_id', 'ordles_id',
        ]);
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($post['page']);
        $srch->addOrder('sesslog_created', 'DESC');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        if ($records) {
            foreach ($records as $key => $row) {
                $records[$key]['sesslog_created'] = MyDate::formatDate($row['sesslog_created']);
                $records[$key]['sesslog_prev_endtime'] = MyDate::formatDate($row['sesslog_prev_endtime']);
                $records[$key]['sesslog_prev_starttime'] = MyDate::formatDate($row['sesslog_prev_starttime']);
            }
        }
            
        $this->sets([
            'logs' => $records,
            'logTypeLabel' => $logTypeArr[$post['reportType']],
            'user' => $user,
            'post' => $post,
            'page' => $post['page'],
            'recordCount' => $srch->recordCount(),
            'pageCount' => $srch->pages(),
            'pageSize' => $pagesize
        ]);
        $this->_template->render(false, false);
    }

    private function getLogSearchForm() : Form {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'user_id');
        $frm->addHiddenField('', 'reportType');
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        return $frm;
    }
}
