<?php

/**
 * Commission Controller is used for Affiliate Commission handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AffiliateCommissionController extends AdminBaseController
{

    /**
     * Initialize Commission
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAffiliateCommission();
    }

    /**
     * Render Commission Search Form
     */
    public function index()
    {
        $this->sets([
            "frmSearch" => $this->getSearchForm(),
            "canEdit" => $this->objPrivilege->canEditAffiliateCommission(true),
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
        $srch = new SearchBase(AffiliateCommission::DB_TBL, 'afcomm');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'afcomm.afcomm_user_id = user.user_id', 'user');
        $srch->addMultipleFields(['afcomm_commission', 'afcomm_id',  'user.user_id', 'user.user_first_name', 'user.user_last_name']);
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
            'canEdit' => $this->objPrivilege->canEditAffiliateCommission(true),
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
        $this->objPrivilege->canEditAffiliateCommission();
        $commissionId = FatUtility::int($commissionId);
        $data = ['afcomm_id' => 0];
        if ($commissionId > 0) {
            $data = AffiliateCommission::getAttributesById($commissionId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $data['user_name'] = Label::getLabel('LBL_GLOBAL_COMMISSION');
            if (!empty($data['afcomm_user_id'])) {
                $user = User::getAttributesById($data['afcomm_user_id'], ['user_first_name', 'user_last_name']);
                $data['user_name'] = $user['user_first_name'] . ' ' . $user['user_last_name'];
            }
        }
        $frm = $this->getForm();
        $frm->getField('afcomm_user_id')->requirements()->setRequired(false);
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
        $this->objPrivilege->canEditAffiliateCommission();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $commissionId = $post['afcomm_id'];
        unset($post['afcomm_id']);
        $data = $this->getCommission($post['afcomm_user_id']);
        if (!empty($data)) {
            $commissionId = $data['afcomm_id'];
        }
        $post['afcomm_user_id'] = empty($post['afcomm_user_id']) ? NULL : $post['afcomm_user_id'];
        $commObj = new AffiliateCommission($commissionId);
        if (!$commObj->addUpdateData($post)) {
            FatUtility::dieJsonError($commObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * View Commission History
     * 
     * @param int $userId
     */
    public function viewHistory($userId = 0)
    {
        $userId = FatUtility::int($userId);
        $srch = new SearchBase(AffiliateCommission::DB_TBL_HISTORY, 'afcomhis');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'afcomhis.afcomhis_user_id = user.user_id', 'user');
        $srch->addMultipleFields(['afcomhis_commission', 'afcomhis.afcomhis_created', 
            'user.user_id', 'user.user_first_name', 'user.user_last_name']);
        if (empty($userId)) {
            $srch->addDirectCondition('afcomhis.afcomhis_user_id IS NULL');
        } else {
            $srch->addCondition('afcomhis.afcomhis_user_id', '=', $userId);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        if ($records) {
            foreach ($records as $key => $row) {
                $records[$key]['afcomhis_created'] = MyDate::formatDate($row['afcomhis_created']);
            }
        }
        $this->sets(['arrListing' => $records]);
        $this->_template->render(false, false);
    }

     /**
     * Delete commission
     *
     * @return json
     */
    public function remove()
    {
        $this->objPrivilege->canEditAffiliateCommission();
        $commissionId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($commissionId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $affiliate = new AffiliateCommission($commissionId);
        if (!$affiliate->remove()) {
            FatUtility::dieJsonError($affiliate->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_COMMISSION_DELETED_SUCCESSFULLY'));
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
        $fld = $frm->addHiddenField('', 'afcomm_id');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $fld = $frm->addHiddenField(Label::getLabel('LBL_USER'), 'afcomm_user_id', 0);
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $frm->addTextBox(Label::getLabel('LBL_USER_NAME'), 'user_name');
        $fld = $frm->addFloatField(Label::getLabel('LBL_COMMISSION_[%]'), 'afcomm_commission');
        $fld->requirements()->setRange(1, 100);
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
        $srch->joinTable(AffiliateCommission::DB_TBL, 'LEFT JOIN', 'user_id = afcomm.afcomm_user_id', 'afcomm');
        $srch->addMultipleFields(['user_id', 'user_email', 'user_username', 'CONCAT(user_first_name," ", user_last_name) as full_name']);
        $srch->addCondition('user_is_affiliate', '=', AppConstant::YES);
        $srch->addDirectCondition('afcomm_user_id IS NULL');
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
     * Get Affiliate Commission
     * 
     * @param int $userId
     * @return type
     */
    private function getCommission(int $userId)
    {
        $srch = new SearchBase(AffiliateCommission::DB_TBL);
        $srch->addMultipleFields(['afcomm_commission', 'afcomm_id']);
        if (!empty($userId)) {
            $srch->addCondition('afcomm_user_id', '=', $userId);
        } else {
            $srch->addDirectCondition('afcomm_user_id IS NULL');
        }
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }


}
