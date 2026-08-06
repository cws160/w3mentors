<?php

/**
 * Group Class is used for Group Classes handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GroupClassesController extends AdminBaseController
{

    /**
     * Initialize Group Class
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        if (!GroupClass::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
        parent::__construct($action);
        $this->objPrivilege->canViewGroupClasses();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getQueryStringData());
        $this->set('frmSrch', $frm);
        $this->_template->addJs('js/jquery.datetimepicker.js');
        $this->_template->addCss('css/jquery.datetimepicker.css');
        $this->_template->render();
    }

    /**
     * Search & List Group Classes
     */
    public function search()
    {
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $frmSrch = $this->getSearchForm();
        if (!$post = $frmSrch->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frmSrch->getValidationErrors()));
        }
        $srch = new SearchBased(GroupClass::DB_TBL, 'grpcls');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id AND '
            . '  gclang.gclang_lang_id = ' . $this->siteLangId, 'gclang');
        $srch->addMultipleFields([
            'teacher.user_first_name',
            'teacher.user_last_name',
            'teacher.user_email',
            'grpcls_type',
            'grpcls_id',
            'grpcls_slug',
            'grpcls_teacher_id',
            'grpcls_tlang_id',
            'grpcls_start_datetime',
            'grpcls_end_datetime',
            'grpcls_teacher_starttime',
            'grpcls_teacher_endtime',
            'grpcls_total_seats',
            'grpcls_entry_fee',
            'grpcls_added_on',
            'grpcls_status',
            'grpcls_parent',
            'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title',
            'IFNULL(gclang.grpcls_description, grpcls.grpcls_description) as grpcls_description',
            'grpcls.grpcls_offline'
        ]);
        $keyword = trim(FatApp::getPostedData('keyword', null, ''));
        if (!empty($keyword)) {
            $fullname = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $cnd = $srch->addCondition($fullname, 'like', '%' . $keyword . '%', 'AND', true);
            $cnd->attachCondition('teacher.user_username', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('teacher.user_email', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('gclang.grpcls_title', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('grpcls.grpcls_title', 'like', '%' . $keyword . '%');
        }
        if (!empty($post['teacher_id'])) {
            $srch->addCondition('teacher.user_id', '=', $post['teacher_id']);
        } elseif (!empty($post['teacher'])) {
            $fullname = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $cnd = $srch->addCondition($fullname, 'like', '%' . trim($post['teacher']) . '%', 'AND', true);
            $cnd->attachCondition('teacher.user_username', 'like', '%' . trim($post['teacher']) . '%');
            $cnd->attachCondition('teacher.user_email', 'like', '%' . trim($post['teacher']) . '%');
        }
        if (!empty($post['grpcls_start_datetime'])) {
            $srch->addCondition('grpcls.grpcls_start_datetime', ">=", MyDate::formatToSystemTimezone($post['grpcls_start_datetime'] . ' 00:00:00'), 'AND', true);
        }
        if (!empty($post['grpcls_parent'])) {
            $srch->addCondition('grpcls.grpcls_parent', "=", $post['grpcls_parent']);
        }
        if (!empty($post['grpcls_end_datetime'])) {
            $srch->addCondition('grpcls.grpcls_end_datetime', "<=", MyDate::formatToSystemTimezone($post['grpcls_end_datetime'] . ' 23:59:59'), 'AND', true);
        }
        if (!empty($post['grpcls_type'])) {
            if ($post['grpcls_type'] == GroupClass::TYPE_PACKAGE) {
                $srch->addCondition('grpcls.grpcls_parent', '>', 0);
            } else {
                $srch->addCondition('grpcls.grpcls_type', '=', $post['grpcls_type']);
                $srch->addCondition('grpcls.grpcls_parent', '=', 0);
            }
        }
        if (!empty($post['grpcls_status'])) {
            $srch->addCondition('grpcls.grpcls_status', '=', $post['grpcls_status']);
        }
        if (isset($post['grpcls_offline'])  && ($post['grpcls_offline'] || $post['grpcls_offline'] == 0)) {
            $srch->addCondition('grpcls.grpcls_offline', '=', $post['grpcls_offline']);
        }
        $page = $post['page'];
        $srch->addCondition('grpcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->addOrder('grpcls.grpcls_start_datetime', 'DESC');
        $srch->addOrder('grpcls.grpcls_id', 'DESC');
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $classes = FatApp::getDb()->fetchAll($srch->getResultSet());
        $classes = $this->fetchAndFormat($classes);
        $this->set('classes', $classes);
        $this->set('postedData', $post);
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('canEdit', $this->objPrivilege->canEditGroupClasses(true));
        $this->_template->render(false, false);
    }

    /**
     * View Class learners
     * 
     */
    public function searchLearners()
    {
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $frmSrch = $this->getLearnerSearchForm();
        if (!$post = $frmSrch->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frmSrch->getValidationErrors()));
        }
        $page = ($post['page'] > 1) ? $post['page'] : 1;
        $classId = $post['grpcls_id'];
        $classType = GroupClass::getAttributesById($classId, 'grpcls_type');
        if ($classType == GroupClass::TYPE_PACKAGE) {
            $srch = new SearchBase(OrderPackage::DB_TBL, 'ordpkg');
            $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordpkg.ordpkg_order_id', 'orders');
            $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
            $srch->addMultipleFields(['user_first_name', 'user_last_name', 'user_email', 'user_gender', 'learner.user_id']);
            $srch->addCondition('ordpkg.ordpkg_package_id', '=', $classId);
            $srch->addCondition('ordpkg.ordpkg_status', '!=', OrderPackage::CANCELLED);
            $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
            $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $srch->addGroupBy('learner.user_id');
            $srch->setPageSize($pagesize);
            $srch->setPageNumber($page);
            $learners =  FatApp::getDb()->fetchAll($srch->getResultSet());
        } else {
            $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');
            $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
            $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
            $srch->addMultipleFields(['user_first_name', 'user_last_name', 'user_email', 'user_gender', 'learner.user_id']);
            $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
            $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $srch->addCondition('ordcls.ordcls_status', '!=', OrderClass::CANCELLED);
            $srch->addCondition('ordcls.ordcls_grpcls_id', '=', $classId);
            $srch->addGroupBy('learner.user_id');
            $srch->setPageSize($pagesize);
            $srch->setPageNumber($page);
            $learners = FatApp::getDb()->fetchAll($srch->getResultSet());
        }
        $this->set('postedData', $post);
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('learners', $learners);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    protected function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'teacher_id');
        $frm->addHiddenField('', 'grpcls_parent', 0);
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        $frm->addTextBox(Label::getLabel('LBL_SEARCH_BY_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addTextBox(Label::getLabel('LBL_TEACHER'), 'teacher');
        $frm->addDateField(Label::getLabel('LBL_START_TIME'), 'grpcls_start_datetime', '', ['readonly' => 'readonly', 'autocomplete' => 'off',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_END_TIME'), 'grpcls_end_datetime', '', ['readonly' => 'readonly', 'autocomplete' => 'off',  'class' => 'small dateTimeFld field--calender']);
        $frm->addSelectBox(Label::getLabel('LBl_CLASS_TYPE'), 'grpcls_type', GroupClass::getClassTypes(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_SERVICE_TYPE'), 'grpcls_offline', AppConstant::getServiceType(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'grpcls_status', GroupClass::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnReset = $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear'));
        $btnSubmit->attachField($btnReset);
        return $frm;
    }

    /**
     * Render Search Form for learner
     * @param int $classId
     */
    public function learners($id = 0)
    {
        $frm = $this->getLearnerSearchForm();
        $frm->fill(['grpcls_id' => $id]);
        $this->set('frmSrch', $frm);
        $this->_template->render();
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private  function getLearnerSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'grpcls_id');
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        return $frm;
    }

    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['grpcls_start_datetime'] = MyDate::formatDate($row['grpcls_start_datetime']);
            $row['grpcls_end_datetime'] = MyDate::formatDate($row['grpcls_end_datetime']);
            $row['grpcls_added_on'] = MyDate::formatDate($row['grpcls_added_on']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
