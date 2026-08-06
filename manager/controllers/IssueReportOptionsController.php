<?php

/**
 * Issue Report Options Controller is used for Issue Options handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class IssueReportOptionsController extends AdminBaseController
{

    /**
     * Initialize Issue Report
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewIssueReportOptions();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->sets([
            'frmSearch' => $this->getSearchForm(),
            'canEdit' => $this->objPrivilege->canEditIssueReportOptions(true)
        ]);
        $this->_template->render();
    }

    /**
     * Search & List Option
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        if (!$post = $searchForm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($searchForm->getValidationErrors()));
        }
        $srch = IssueReportOptions::getSearchObj($this->siteLangId, false);
        $srch->addMultipleFields([
            'tissueopt_id',
            'tissueopt_active',
            'tissueopt_order',
            'tissueoptlang_title',
            'tissueopt_identifier',
        ]);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('tissueopt_identifier', 'like', '%' . $keyword . '%');
            $cond->attachCondition('tissueoptlang_title', 'like', '%' . $keyword . '%');
        }
        $srch->addOrder('tissueopt_active', 'DESC');
        $srch->addOrder('tissueopt_order', 'asc');
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        $this->sets([
            "arrListing" => FatApp::getDb()->fetchAll($srch->getResultSet()),
            "recordCount" => $srch->recordCount(),
            "canEdit" => $this->objPrivilege->canEditIssueReportOptions(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Issue Report Option Form
     * 
     * @param int $optId
     */
    public function form($optId)
    {
        $this->objPrivilege->canEditIssueReportOptions();
        $optId = FatUtility::int($optId);
        $frm = $this->getForm();
        $frm->getField('tissueopt_id')->value = $optId;
        if ($optId > 0) {
            $data = IssueReportOptions::getAttributesById($optId, ['tissueopt_id', 'tissueopt_identifier', 'tissueopt_order', 'tissueopt_active']);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('optId', $optId);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Issue Report Option
     */
    public function setup()
    {
        $this->objPrivilege->canEditIssueReportOptions();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $optId = $post['tissueopt_id'];
        if ($optId > 0 && empty(IssueReportOptions::getAttributesById($post['tissueopt_id'], 'tissueopt_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        unset($post['tissueopt_id']);
        $record = new IssueReportOptions($optId);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'optId' => $record->getMainTableRecordId()
        ]);
    }

    /**
     * Render Report Issue Option  Language Form
     * 
     * @param int $optId
     * @param int $langId
     */
    public function langForm($optId = 0, $langId = 0)
    {
        $this->objPrivilege->canEditIssueReportOptions();
        $optId = FatUtility::int($optId);
        $langId = FatUtility::int($langId);
        if (empty(IssueReportOptions::getAttributesById($optId, 'tissueopt_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($langId, $optId);
        $languages = $langFrm->getField('tissueoptlang_lang_id')->options;
        if (!array_key_exists($langId, $languages)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langData = IssueReportOptions::getAttributesByLangId($langId, $optId);
        if (empty($langData)) {
            $langData = [
                'tissueoptlang_tissueopt_id' => $optId,
                'tissueoptlang_lang_id' => $langId
            ];
        }
        $langFrm->fill($langData);
        $this->sets([
            'languages' => $languages,
            'optId' => $optId,
            'lang_id' => $langId,
            'langFrm' => $langFrm,
            'formLayout' => Language::getLayoutDirection($langId),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Report Issue Option Language Data Setup
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditIssueReportOptions();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['tissueoptlang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (empty(IssueReportOptions::getAttributesById($post['tissueoptlang_tissueopt_id'], 'tissueopt_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = ['tissueoptlang_lang_id' => $post['tissueoptlang_lang_id'], 'tissueoptlang_title' => $post['tissueoptlang_title']];
        $obj = new IssueReportOptions($post['tissueoptlang_tissueopt_id']);
        if (!$obj->updateLangData($post['tissueoptlang_lang_id'], $data)) {
            FatUtility::dieJsonError($obj->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(IssueReportOptions::DB_TBL_LANG, $post['tissueoptlang_tissueopt_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'optId' => $post['tissueoptlang_tissueopt_id']
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditIssueReportOptions();
        $optId = FatApp::getPostedData('optId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (empty(IssueReportOptions::getAttributesById($optId, 'tissueopt_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $issueReportOptions = new IssueReportOptions($optId);
        if (!$issueReportOptions->changeStatus($status)) {
            FatUtility::dieJsonError($issueReportOptions->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditIssueReportOptions();
        $optId = FatApp::getPostedData('optId', FatUtility::VAR_INT, 0);
        if (empty(IssueReportOptions::getAttributesById($optId, 'tissueopt_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $issueReportOptions = new IssueReportOptions($optId);
        if (!$issueReportOptions->deleteOption($optId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Update Sort Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditIssueReportOptions();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $issueReportOptions = new IssueReportOptions();
            if (!$issueReportOptions->updateOrder($post['IssueReportOptions'])) {
                FatUtility::dieJsonError($issueReportOptions->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
        }
    }

    /**
     * Get Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmIssueReoprtOption');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'tissueopt_id');
        $fld = $frm->addRequiredField(Label::getLabel('LBL_OPTION_IDENTIFIER'), 'tissueopt_identifier');
        $fld->setUnique(IssueReportOptions::DB_TBL, 'tissueopt_identifier', 'tissueopt_id', 'tissueopt_id', 'tissueopt_id');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'tissueopt_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Language Form
     * 
     * @param int $langId
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $recordId = 0): Form
    {
        $frm = new Form('frmIssueReoprtOption');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'tissueoptlang_tissueopt_id');
        $frm->addSelectBox('', 'tissueoptlang_lang_id', Language::getAllNames(), '', [], '');
        $frm->addRequiredField(Label::getLabel('LBL_TITLE', $langId), 'tissueoptlang_title');
        Translator::addTranslatorActions($frm, $langId, $recordId, IssueReportOptions::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmIssueReoprtOptions');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_OPTION_IDENTIFIER'), 'keyword', '');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fldCancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fldCancel);
        return $frm;
    }
}
