<?php

/**
 * Package Classes is used for Package Classes handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PackageClassesController extends AdminBaseController
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
        $this->objPrivilege->canViewPackageClasses();
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
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id AND gclang.gclang_lang_id = ' . $this->siteLangId, 'gclang');
        $srch->addMultipleFields([
            'teacher.user_first_name',
            'teacher.user_last_name',
            'teacher.user_email',
            'grpcls.grpcls_id',
            'grpcls.grpcls_slug',
            'grpcls.grpcls_teacher_id',
            'grpcls.grpcls_tlang_id',
            'grpcls.grpcls_start_datetime',
            'grpcls.grpcls_end_datetime',
            'grpcls.grpcls_parent',
            'grpcls.grpcls_teacher_starttime',
            'grpcls.grpcls_teacher_endtime',
            'grpcls.grpcls_total_seats',
            'grpcls.grpcls_entry_fee',
            'grpcls.grpcls_added_on',
            'grpcls.grpcls_status',
            'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title',
            'grpcls.grpcls_offline'
        ]);
        $keyword = trim(FatApp::getPostedData('keyword', null, ''));
        if (!empty($keyword)) {
            $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $cnd = $srch->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
            $cnd->attachCondition('teacher.user_username', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('teacher.user_email', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('gclang.grpcls_title', 'like', '%' . $keyword . '%');
            $cnd->attachCondition('grpcls.grpcls_title', 'like', '%' . $keyword . '%');
        }
        if (!empty($post['teacher_id'])) {
            $srch->addCondition('teacher.user_id', '=', $post['teacher_id']);
        } elseif (!empty($post['teacher'])) {
            $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . trim($post['teacher']) . '%', 'AND', true);
        }
        if (!empty($post['grpcls_start_datetime'])) {
            $srch->addCondition('grpcls.grpcls_start_datetime', ">=", MyDate::formatToSystemTimezone($post['grpcls_start_datetime'] . ' 00:00:00'), 'AND', true);
        }
        if (!empty($post['grpcls_end_datetime'])) {
            $srch->addCondition('grpcls.grpcls_end_datetime', "<=", MyDate::formatToSystemTimezone($post['grpcls_end_datetime'] . ' 23:59:59'), 'AND', true);
        }
        if (!empty($post['grpcls_status'])) {
            $srch->addCondition('grpcls.grpcls_status', '=', $post['grpcls_status']);
        }
        if (!empty($post['grpcls_parent'])) {
            $srch->addCondition('grpcls.grpcls_parent', '=', $post['grpcls_parent']);
        }
        if (isset($post['grpcls_offline'])  && ($post['grpcls_offline'] || $post['grpcls_offline'] == 0)) {
            $srch->addCondition('grpcls.grpcls_offline', '=', $post['grpcls_offline']);
        }
        $page = $post['page'];
        $srch->addCondition('grpcls.grpcls_type', '=', GroupClass::TYPE_PACKAGE);
        $srch->addCondition('grpcls.grpcls_parent', '=', 0);
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
     * Get Search Form
     * 
     * @return Form
     */
    protected function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'teacher_id');
        $frm->addHiddenField('', 'grpcls_parent');
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        $frm->addTextBox(Label::getLabel('LBL_SEARCH_BY_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addTextBox(Label::getLabel('LBl_Teacher'), 'teacher');
        $frm->addDateField(Label::getLabel('LBl_Start_Time'), 'grpcls_start_datetime', '', ['readonly' => 'readonly', 'autocomplete' => 'off', 'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBl_End_Time'), 'grpcls_end_datetime', '', ['readonly' => 'readonly', 'autocomplete' => 'off', 'class' => 'small dateTimeFld field--calender']);
        $frm->addSelectBox(Label::getLabel('LBL_SERVICE_TYPE'), 'grpcls_offline', AppConstant::getServiceType(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBl_Status'), 'grpcls_status', GroupClass::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnReset = $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear'));
        $btnSubmit->attachField($btnReset);
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
