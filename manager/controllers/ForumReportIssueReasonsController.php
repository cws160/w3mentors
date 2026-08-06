<?php

/**
 * Forum Report Issue Reasons Controller is used for managing Issue Reasons
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumReportIssueReasonsController extends AdminBaseController
{

    /**
     * Initialize Issue Report
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewDiscussionForum();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set('canEdit', $this->objPrivilege->canEditDiscussionForum(true));
        $this->_template->render();
    }

    /**
     * Search & List Option
     */
    public function search()
    {
        $srch = new ForumReportIssueReasonSearch($this->siteLangId, false);
        $srch->addMultipleFields(['frireason_id', 'frireason_active', 'frireason_order',
            'frireason_name as reasonLabel', 'frireason_identifier']);
        $srch->addOrder('frireason_order', 'asc');
        $keyword = trim(FatApp::getPostedData('keyword', FatUtility::VAR_INT, ''));
        if (!empty($keyword)) {
            $cond = $srch->addCondition('frireason_identifier', 'like', '%' . $keyword . '%');
            $cond->attachCondition('frireason_name', 'like', '%' . $keyword . '%');
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $this->sets([
            "arrListing" => FatApp::getDb()->fetchAll($srch->getResultSet()),
            "canEdit" => $this->objPrivilege->canEditDiscussionForum(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Issue Report Option Form
     *
     * @param int $id
     */
    public function form($id)
    {
        $this->objPrivilege->canEditDiscussionForum();
        $id = FatUtility::int($id);
        $frm = $this->getForm();
        if ($id > 0) {
            $data = ForumReportIssueReason::getAttributesById($id,
                            ['frireason_id', 'frireason_identifier', 'frireason_order', 'frireason_active']);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('id', $id);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Issue Report Option
     */
    public function setup()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $id = FatUtility::int($post['frireason_id']);
        if ($id > 0 && empty(ForumReportIssueReason::getAttributesById($id, 'frireason_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        unset($post['frireason_id']);
        $record = new ForumReportIssueReason($id);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_SOMETHING_WENT_WRONG'));
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'id' => $record->getMainTableRecordId()
        ]);
    }

    /**
     * Render Report Issue Option  Language Form
     *
     * @param int $id
     * @param int $langId
     */
    public function langForm($id, $langId)
    {
        $this->objPrivilege->canEditDiscussionForum();
        $id = FatUtility::int($id);
        $langId = FatUtility::int($langId);
        if (empty(ForumReportIssueReason::getAttributesById($id, 'frireason_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($langId, $id);
        $languages = $langFrm->getField('frireasonlang_lang_id')->options;
        if (!array_key_exists($langId, $languages)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langData = ForumReportIssueReason::getAttributesByLangId($langId, $id);
        if (empty($langData)) {
            $langData = [
                'frireasonlang_frireason_id' => $id,
                'frireasonlang_lang_id' => $langId
            ];
        }
        $langFrm->fill($langData);
        $this->sets([
            'languages' => $languages,
            'id' => $id,
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
        $this->objPrivilege->canEditDiscussionForum();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['frireasonlang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (empty(ForumReportIssueReason::getAttributesById($post['frireasonlang_frireason_id'], 'frireason_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = ['frireasonlang_lang_id' => $post['frireasonlang_lang_id'], 'frireason_name' => $post['frireason_name']];
        $obj = new ForumReportIssueReason($post['frireasonlang_frireason_id']);
        if (!$obj->updateLangData($post['frireasonlang_lang_id'], $data)) {
            FatUtility::dieJsonError($obj->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(ForumReportIssueReason::DB_TBL_LANG, $post['frireasonlang_frireason_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'id' => $post['frireasonlang_frireason_id']
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (empty(ForumReportIssueReason::getAttributesById($id, 'frireason_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $ForumReportIssueReason = new ForumReportIssueReason($id);
        if (!$ForumReportIssueReason->changeStatus($status)) {
            FatUtility::dieJsonError($ForumReportIssueReason->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Update Sort Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $ForumReportIssueReason = new ForumReportIssueReason();
            if (!$ForumReportIssueReason->updateOrder($post['ForumReportIssueReasons'])) {
                FatUtility::dieJsonError($ForumReportIssueReason->getError());
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
        $frm = new Form('frmfrireasons');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'frireason_id');
        $fld = $frm->addRequiredField(Label::getLabel('LBL_OPTION_IDENTIFIER'), 'frireason_identifier');
        $fld->setUnique(ForumReportIssueReason::DB_TBL, 'frireason_identifier', 'frireason_id', 'frireason_id', 'frireason_id');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'frireason_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Language Form
     *
     * @param int $langId
     * @param int $recordId
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $recordId = 0): Form
    {
        $frm = new Form('langFrmfrireasons');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'frireasonlang_frireason_id');
        $frm->addSelectBox('', 'frireasonlang_lang_id', Language::getAllNames(), '', [], '');
        $frm->addRequiredField(Label::getLabel('LBL_TITLE', $langId), 'frireason_name');
        Translator::addTranslatorActions($frm, $langId, $recordId, ForumReportIssueReason::DB_TBL_LANG);
        return $frm;
    }
}
