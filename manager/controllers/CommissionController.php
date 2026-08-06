<?php

/**
 * Commission Controller is used for Commission handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class CommissionController extends AdminBaseController
{

    /**
     * Initialize Commission
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCommissionSettings();
    }

    /**
     * Render Commission Search Form
     */
    public function index()
    {
        $this->sets([
            "frmSearch" => $this->getSearchForm(),
            "canEdit" => $this->objPrivilege->canEditCommissionSettings(true),
        ]);
        $this->_template->render();
    }

    /**
     * Search & List Commission
     */
    public function search()
    {
        $form = $this->getSearchForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new SearchBase(Commission::DB_TBL, 'comm');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'comm.comm_user_id = user.user_id', 'user');
        $srch->addMultipleFields(['comm_lessons', 'comm_id', 'comm_classes', 'comm_courses', 'user.user_id', 'user.user_first_name', 'user.user_last_name']);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $srch->addCondition('mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)', 'LIKE', '%' . $keyword . '%', 'AND', true);
        }
        $srch->setPageNumber($post['page']);
        $srch->setPageSize($post['pagesize']);
        $this->sets([
            'arrListing' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'page' => $post['page'],
            'postedData' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditCommissionSettings(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Commission Form
     * 
     * @param int $commissionId
     */
    public function form($commissionId)
    {
        $this->objPrivilege->canEditCommissionSettings();
        $commissionId = FatUtility::int($commissionId);
        $data = ['comm_id' => 0];
        if ($commissionId > 0) {
            $data = Commission::getAttributesById($commissionId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $data['user_name'] = Label::getLabel('LBL_GLOBAL_COMMISSION');
            if (!empty($data['comm_user_id'])) {
                $user = User::getAttributesById($data['comm_user_id'], ['user_first_name', 'user_last_name']);
                $data['user_name'] = $user['user_first_name'] . ' ' . $user['user_last_name'];
            }
        }
        $frm = $this->getForm();
        $frm->getField('comm_user_id')->requirements()->setRequired(false);
        $frm->fill($data);
        $this->sets([
            'frm' => $frm,
            'data' => $data,
            'commissionId' => $commissionId,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Commission
     */
    public function setup()
    {
        $this->objPrivilege->canEditCommissionSettings();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $commissionId = $post['comm_id'];
        unset($post['comm_id']);
        $data = $this->getCommission($post['comm_user_id']);
        if (!empty($data)) {
            $commissionId = $data['comm_id'];
        }

        $post['comm_user_id'] = empty($post['comm_user_id']) ? NULL : $post['comm_user_id'];
        $commission = new Commission($commissionId);

        if (!$commission->addUpdateData($post)) {
            FatUtility::dieJsonError($commission->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * View Commission History
     * 
     * @param int $userId
     */
    public function searchHistory()
    {
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $frmSrch = $this->getHistorySrchFrm();
        if (!$post = $frmSrch->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frmSrch->getValidationErrors()));
        }
        $page = ($post['page'] > 1) ? $post['page'] : 1;
        $userId = FatUtility::int($post['user_id']);
        $srch = new SearchBase(Commission::DB_TBL_HISTORY, 'comhis');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'comhis.comhis_user_id = user.user_id', 'user');
        $srch->addMultipleFields([
            'comhis_lessons', 'comhis_classes', 'comhis_courses', 'comhis.comhis_created',
            'user.user_id', 'user.user_first_name', 'user.user_last_name'
        ]);
        if (empty($userId)) {
            $srch->addDirectCondition('comhis.comhis_user_id IS NULL');
        } else {
            $srch->addCondition('comhis.comhis_user_id', '=', $userId);
        }
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        if ($records) {
            foreach ($records as $key => $row) {
                $records[$key]['comhis_created'] = MyDate::formatDate($row['comhis_created']);
            }
        }
        $this->sets([
            'arrListing' => $records,
            'postedData' => $post,
            'page' => $page,
            'pageSize' => $pagesize,
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {

        $frm = new Form('frmCommission');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'comm_id');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $fld = $frm->addHiddenField(Label::getLabel('LBL_USER'), 'comm_user_id', 0);
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $frm->addTextBox(Label::getLabel('LBL_USER_NAME'), 'user_name');
        $fld = $frm->addFloatField(Label::getLabel('LBL_LESSON_COMMISSION_FEES_[%]'), 'comm_lessons');
        $fld->requirements()->setRange(1, 100);
        if (GroupClass::isEnabled()) {
            $fld = $frm->addFloatField(Label::getLabel('LBL_CLASS_COMMISSION_FEES_[%]'), 'comm_classes');
            $fld->requirements()->setRange(1, 100);
        }
        if (Course::isEnabled()) {
            $fld = $frm->addFloatField(Label::getLabel('LBL_COURSE_COMMISSION_FEES_[%]'), 'comm_courses');
            $fld->requirements()->setRange(1, 100);
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('commSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '');
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Auto Complete Json
     */
    public function autoCompleteJson()
    {
        $keyword = trim(FatApp::getPostedData('keyword', FatUtility::VAR_STRING, ''));
        if (empty($keyword)) {
            FatUtility::dieJsonSuccess(['data' => []]);
        }
        $srch = new SearchBase(User::DB_TBL);
        $srch->joinTable(Commission::DB_TBL, 'LEFT JOIN', 'user_id = comm.comm_user_id', 'comm');
        $srch->addMultipleFields(['user_id', 'user_email', 'user_username', 'CONCAT(user_first_name," ", user_last_name) as full_name']);
        $srch->addCondition('user_is_teacher', '=', AppConstant::YES);
        $srch->addDirectCondition('comm_user_id IS NULL');
        $srch->addDirectCondition('user_deleted IS NULL');
        $cond = $srch->addCondition('user_username', 'LIKE', '%' . $keyword . '%');
        $cond->attachCondition('user_email', 'LIKE', '%' . $keyword . '%', 'OR');
        $cond->attachCondition('mysql_func_CONCAT(user_first_name," ", user_last_name)', 'LIKE', '%' . $keyword . '%', 'OR', true);
        $srch->addOrder('full_name', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(30);
        $users = FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
        FatUtility::dieJsonSuccess(['data' => $users]);
    }

    /**
     * Get Commission
     * 
     * @param int $teacherId
     * @return type
     */
    private function getCommission(int $teacherId)
    {
        $srch = new SearchBase(Commission::DB_TBL);
        $srch->addMultipleFields(['comm_lessons', 'comm_classes', 'comm_courses', 'comm_id']);
        if (!empty($teacherId)) {
            $srch->addCondition('comm_user_id', '=', $teacherId);
        } else {
            $srch->addDirectCondition('comm_user_id IS NULL');
        }
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * View Commission History
     * 
     * @param int $userId
     */
    public function commissionHistory($id)
    {
        $frm = $this->getHistorySrchFrm();
        $frm->fill(['user_id' => $id]);
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Get Commission History search form
     *
     * @return Form
     */
    private function getHistorySrchFrm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'user_id');
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        return $frm;
    }
}
