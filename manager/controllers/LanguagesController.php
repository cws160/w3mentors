<?php

/**
 * Languages Controller is used for Languages handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class LanguagesController extends AdminBaseController
{

    /**
     * Initialize Languages
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewLanguage();
        $this->set("canEdit", $this->objPrivilege->canEditLanguage(true));
    }

    /**
     * Render Language Search Form
     */
    public function index()
    {
        $this->set("search", $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear_Search'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Search & List Languages
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $srch = Language::getSearchObject(false, $this->siteLangId);
        $srch->addFld('l.* ');
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $condition = $srch->addCondition('l.language_code', 'like', '%' . $keyword . '%');
            $condition->attachCondition('l.language_name', 'like', '%' . $keyword . '%', 'OR');
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('activeInactiveArr', AppConstant::getActiveArr());
        $this->set('postedData', $post);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Render Language Form
     * 
     * @param int $languageId
     */
    public function form(int $languageId)
    {
        $this->objPrivilege->canEditLanguage();
        $frm = $this->getForm($languageId);
        $data = Language::getAttributesById($languageId);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->set('language_id', $languageId);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Setup Language
     */
    public function setup()
    {
        if (MyUtility::isDemoUrl()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_NOT_ALLOWED_ON_DEMO'));
        }
        $this->objPrivilege->canEditLanguage();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $languageId = FatApp::getPostedData('language_id', FatUtility::VAR_INT, 0);
        if (empty($languageId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $record = new Language($languageId);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_This_language_code_is_not_available'));
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Keyword'));
    }

    /**
     * Get Language Form
     * 
     * @param int $languageId
     * @return Form
     */
    private function getForm($languageId = 0): Form
    {
        $frm = new Form('frmLanguage');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'language_id', FatUtility::int($languageId));
        $frm->addRequiredField(Label::getLabel('LBL_Language_code'), 'language_code');
        $frm->addRequiredField(Label::getLabel('LBL_Language_name'), 'language_name');
        $frm->addRadioButtons(Label::getLabel("LBL_Layout_Direction"), 'language_direction',
                AppConstant::getLayoutDirections(), '', ['class' => 'list-inline']);
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'language_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        if (MyUtility::isDemoUrl()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_NOT_ALLOWED_ON_DEMO'));
        }
        $this->objPrivilege->canEditLanguage();
        $languageId = FatApp::getPostedData('languageId', FatUtility::VAR_INT, 0);
        if (0 >= $languageId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = Language::getAttributesById($languageId, ['language_active']);
        if ($data == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $status = ($data['language_active'] == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        $countryObj = new Language($languageId);
        if (!$countryObj->changeStatus($status)) {
            FatUtility::dieJsonError($countryObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

}
