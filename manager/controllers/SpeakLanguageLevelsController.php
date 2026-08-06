<?php

/**
 * Speak Language Levels Controller is used for Speak Language Levels handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SpeakLanguageLevelsController extends AdminBaseController
{
    /**
     * Initialize Speak Language
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSpeakLanguageLevels();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->sets([
            'frmSearch' => $this->getSearchForm(),
            'canEdit' => $this->objPrivilege->canEditSpeakLanguageLevels(true),
        ]);
        $this->_template->render();
    }

    /**
     * Search & List Language Levels
     */
    public function search()
    {
        $data = FatApp::getPostedData();
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray($data);
        $srch = new SearchBased(SpeakLanguageLevel::DB_TBL, 'slanglvl');
        $joinon = 'slevellang.slanglvllang_slanglvl_id = slanglvl.slanglvl_id AND slevellang.slanglvllang_lang_id = ' . $this->siteLangId;
        $srch->joinTable(SpeakLanguageLevel::DB_TBL_LANG, 'LEFT JOIN', $joinon, 'slevellang');
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('slanglvl.slanglvl_identifier', 'like', '%' . $keyword . '%');
            $cond->attachCondition('slanglang.slanglvl_name', 'like', '%' . $keyword . '%');
        }
        $srch->addOrder('slanglvl_active', 'DESC');
        $srch->addOrder('slanglvl_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $this->sets([
            'arrListing' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'canEdit' => $this->objPrivilege->canEditSpeakLanguageLevels(true)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Speak Language Levels Form
     * 
     * @param int $sLangLevelId
     */
    public function form($sLangLevelId = 0)
    {
        $this->objPrivilege->canEditSpeakLanguageLevels();
        $sLangLevelId = FatUtility::int($sLangLevelId);
        $frm = $this->getForm();
        $frm->getField('slanglvl_id')->value = $sLangLevelId;
        if ($sLangLevelId > 0) {
            $data = SpeakLanguageLevel::getAttributesById($sLangLevelId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->sets([
            'languages' => Language::getAllNames(), 
            'sLangLevelId' => $sLangLevelId, 'frm' => $frm
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Speak Lang Levels Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmSpeakLangLevel');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'slanglvl_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setIntPositive();
        $fld = $frm->addRequiredField(Label::getLabel('LBL_LANGUAGE_LEVEL_IDENTIFIER'), 'slanglvl_identifier');
        $fld->setUnique(SpeakLanguageLevel::DB_TBL, 'slanglvl_identifier', 'slanglvl_id', 'slanglvl_id', 'slanglvl_id');
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'slanglvl_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Setup Speak Lang Level
     */
    public function setup()
    {
        $this->objPrivilege->canEditSpeakLanguageLevels();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $sLangLevelId = $post['slanglvl_id'];
        if ($sLangLevelId > 0) {
            $data = SpeakLanguageLevel::getAttributesById($sLangLevelId, ['slanglvl_id']);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
        }
        unset($post['slanglvl_id']);
        $speakLanguageLevel = new SpeakLanguageLevel($sLangLevelId);
        $speakLanguageLevel->assignValues($post);
        if (!$speakLanguageLevel->save()) {
            FatUtility::dieJsonError($speakLanguageLevel->getError());
        }
        if ($post['slanglvl_active'] == AppConstant::NO) {
            $userSpeakLang = new UserSpeakLanguage();
            $userSpeakLang->removeSpeakLangLevel([$sLangLevelId]);
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'sLangLevelId' => $speakLanguageLevel->getMainTableRecordId()
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Speak Lang Level Language Form
     * 
     * @param int $sLangLevelId
     * @param int $langId
     */
    public function langForm($sLangLevelId = 0, $langId = 0)
    {
        $this->objPrivilege->canEditSpeakLanguageLevels();
        $sLangLevelId = FatUtility::int($sLangLevelId);
        $langId = FatUtility::int($langId);
        $data = SpeakLanguageLevel::getAttributesById($sLangLevelId, ['slanglvl_id']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($langId, $sLangLevelId);
        $languages = $langFrm->getField('slanglvllang_lang_id')->options;
        if (!array_key_exists($langId, $languages)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langData = SpeakLanguageLevel::getAttributesByLangId($langId, $sLangLevelId);
        if (empty($langData)) {
            $langData = ['slanglvllang_lang_id' => $langId, 'slanglvllang_slanglvl_id' => $sLangLevelId];
        }
        $langFrm->fill($langData);
        $this->sets([
            'languages' => $languages,
            'lang_id' => $langId,
            'sLangLevelId' => $sLangLevelId,
            'langFrm' => $langFrm,
            'formLayout' => Language::getLayoutDirection($langId),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Speak Lang Level Language Form
     * 
     * @param int $langId
     * @param int $recordId
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $recordId = 0): Form
    {
        $frm = new Form('frmSpeakLangLevelLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'slanglvllang_slanglvl_id');
        $frm->addSelectBox('', 'slanglvllang_lang_id', Language::getAllNames(), '', [], '');
        $frm->addRequiredField(Label::getLabel('LBL_LANGUAGE_LEVEL_NAME', $langId), 'slanglvl_name');
        Translator::addTranslatorActions($frm, $langId, $recordId, SpeakLanguageLevel::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Setup Speak Lang Level Language Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditSpeakLanguageLevels();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['slanglvllang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (empty(SpeakLanguageLevel::getAttributesById($post['slanglvllang_slanglvl_id'], ['slanglvl_id']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = [
            'slanglvllang_lang_id' => $post['slanglvllang_lang_id'],
            'slanglvllang_slanglvl_id' => $post['slanglvllang_slanglvl_id'],
            'slanglvl_name' => $post['slanglvl_name']
        ];
        $speakLanguageLevel = new SpeakLanguageLevel($post['slanglvllang_slanglvl_id']);
        if (!$speakLanguageLevel->updateLangData($post['slanglvllang_lang_id'], $data)) {
            FatUtility::dieJsonError($speakLanguageLevel->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(SpeakLanguageLevel::DB_TBL_LANG, $post['slanglvllang_slanglvl_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'sLangLevelId' => $post['slanglvllang_slanglvl_id']
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditSpeakLanguageLevels();
        $sLangLevelId = FatApp::getPostedData('sLangLevelId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (empty(SpeakLanguageLevel::getAttributesById($sLangLevelId, ['slanglvl_id']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $speakLanguageLevel = new SpeakLanguageLevel($sLangLevelId);
        if (!$speakLanguageLevel->changeStatus($status)) {
            FatUtility::dieJsonError($speakLanguageLevel->getError());
        }
        if ($status == AppConstant::NO) {
            $userSpeakLang = new UserSpeakLanguage();
            $userSpeakLang->removeSpeakLangLevel([$sLangLevelId]);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditSpeakLanguageLevels();
        $sLangLevelId = FatApp::getPostedData('sLangLevelId', FatUtility::VAR_INT, 0);
        if (empty(SpeakLanguageLevel::getAttributesById($sLangLevelId, 'slanglvl_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $speakLanguageLevel = new SpeakLanguageLevel($sLangLevelId);
        if (!$speakLanguageLevel->deleteRecord($sLangLevelId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        (new UserSpeakLanguage())->removeSpeakLangLevel([$sLangLevelId]);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Update Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditSpeakLanguageLevels();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $spokeLangLevelObj = new SpeakLanguageLevel();
            if (!$spokeLangLevelObj->updateOrder($post['spokenLangageLevels'])) {
                FatUtility::dieJsonError($spokeLangLevelObj->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
        }
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
        $frm->addTextBox(Label::getLabel('LBL_LANGUAGE_LEVEL_IDENTIFIER'), 'keyword', '');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }
}