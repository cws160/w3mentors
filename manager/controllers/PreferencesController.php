<?php

/**
 * Preferences Controller is used for Preferences handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PreferencesController extends AdminBaseController
{

    /**
     * Initialize Preferences
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewPreferences();
    }

    /**
     * Render Search Form
     * 
     * @param int $type
     */
    public function index($type)
    {
        $type = FatUtility::int($type);
        if (!array_key_exists($type, Preference::getPreferenceTypeArr())) {
            FatUtility::exitWithErrorCode(404);
        }
        $this->sets([
            "type" => $type,
            'frmSearch' => $this->getSearchForm($type),
            "canEdit" => $this->objPrivilege->canEditPreferences(true)
        ]);
        $this->_template->render();
    }

    /**
     * Search & List Preferences
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $srch = new SearchBased(Preference::DB_TBL, 'prefer');
        $srch->joinTable(Preference::DB_TBL_LANG, 'LEFT JOIN', 'preferlang.preferlang_prefer_id = prefer.prefer_id AND preferlang.preferlang_lang_id = ' . $this->siteLangId, 'preferlang');
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('prefer_identifier', 'like', '%' . $keyword . '%');
            $cond->attachCondition('preferlang.prefer_title', 'like', '%' . $keyword . '%');
        }
        if ($post['type'] > 0) {
            $srch->addCondition('prefer_type', '=', $post['type']);
        }
        $srch->addOrder('prefer_order', 'asc');
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $this->sets([
            'arrListing' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'canEdit' => $this->objPrivilege->canEditPreferences(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @param int $type
     * @return Form
     */
    private function getSearchForm($type = 0): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $f1 = $frm->addTextBox(Label::getLabel('LBL_Preference_Identifier'), 'keyword', '');
        $f1 = $frm->addHiddenField('', 'type', $type);
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Render Preference Form
     * 
     * @param int $preferenceId
     * @param int $type
     */
    public function form($preferenceId, $type = 0)
    {
        $this->objPrivilege->canEditPreferences();
        $preferenceId = FatUtility::int($preferenceId);
        $type = FatUtility::int($type);
        if (empty($preferenceId) && !array_key_exists($type, Preference::getPreferenceTypeArr())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm($preferenceId);
        $fldType = $frm->getField('prefer_type');
        $fldType->value = $type;
        if (0 < $preferenceId) {
            $data = Preference::getAttributesById($preferenceId, ['prefer_id', 'prefer_identifier', 'prefer_type']);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->sets([
            'frm' => $frm,
            'languages' => Language::getAllNames(),
            'preferId' => $preferenceId
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Preference
     */
    public function setup()
    {
        $this->objPrivilege->canEditPreferences();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $preferenceId = $post['prefer_id'];
        unset($post['prefer_id']);
        // unique identifier check
        $srch = new SearchBase(Preference::DB_TBL);
        $srch->addCondition('mysql_func_LOWER(prefer_identifier)', '=', strtolower(trim($post['prefer_identifier'])), 'AND', true);
        $srch->addCondition('prefer_id', '!=', $preferenceId);
        $srch->addCondition('prefer_type', '=', $post['prefer_type']);
        $srch->doNotCalculateRecords();
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_IDENTIFIER_IS_ALREADY_IN_USE', $this->siteLangId));
        }
        $preference = new Preference($preferenceId);
        $preference->assignValues($post);
        if (!$preference->save()) {
            FatUtility::dieJsonError($preference->getError());
        }
        FatUtility::dieJsonSuccess(['preferenceId' => $preference->getMainTableRecordId(), 'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')]);
    }

    /**
     * Render Preference Language Form
     * 
     * @param int $preferenceId
     * @param int $langId
     */
    public function langForm($preferenceId = 0, $langId = 0)
    {
        $this->objPrivilege->canEditPreferences();
        $preferenceId = FatUtility::int($preferenceId);
        $langId = FatUtility::int($langId);
        $data = Preference::getAttributesById($preferenceId, ['prefer_id']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($langId, $preferenceId);
        $languages = $langFrm->getField('preferlang_lang_id')->options;
        if (!array_key_exists($langId, $languages)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langData = Preference::getAttributesByLangId($langId, $preferenceId);
        if (empty($langData)) {
            $langData = ['preferlang_lang_id' => $langId, 'preferlang_prefer_id' => $preferenceId,];
        }
        $langFrm->fill($langData);
        $this->sets([
            'preferenceId' => $preferenceId,
            'languages' => $languages,
            'lang_id' => $langId,
            'langFrm' => $langFrm,
            'formLayout' => Language::getLayoutDirection($langId),
            'defaultLang' => Language::getDefaultLang(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Preference Language
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditPreferences();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['preferlang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $data = Preference::getAttributesById($post['preferlang_prefer_id'], ['prefer_id']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = [
            'preferlang_lang_id' => $post['preferlang_lang_id'],
            'preferlang_prefer_id' => $post['preferlang_prefer_id'],
            'prefer_title' => $post['prefer_title'],
        ];
        $preference = new Preference($post['preferlang_prefer_id']);
        if (!$preference->updateLangData($post['preferlang_lang_id'], $data)) {
            FatUtility::dieJsonError($preference->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(Preference::DB_TBL_LANG, $post['preferlang_prefer_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess(['preferenceId' => $post['preferlang_prefer_id'], 'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')]);
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditPreferences();
        $preferenceId = FatApp::getPostedData('preferenceId', FatUtility::VAR_INT, 0);
        $data = Preference::getAttributesById($preferenceId, ['prefer_id']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $deleteRecord = new Preference();
        if (!$deleteRecord->deletePreference($preferenceId)) {
            FatUtility::dieJsonError($deleteRecord->getError());
        }
        $this->removeUserPreferences([$preferenceId]);
        $teacherStat = new TeacherStat(0);
        $teacherStat->setPreferenceBulk();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Preference Form
     * 
     * @param int $preferenceId
     * @return Form
     */
    private function getForm(int $preferenceId = 0): Form
    {
        $frm = new Form('frmPreference');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addSelectBox('', 'prefer_type', Preference::getPreferenceTypeArr(), '', [], '');
        $fld->requirements()->setRequired(true);
        $frm->addHiddenField('', 'prefer_id', FatUtility::int($preferenceId));
        $frm->addRequiredField(Label::getLabel('LBL_PREFERENCE_IDENTIFIER'), 'prefer_identifier');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Preference Language Form
     * 
     * @param int $langId
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $recordId = 0): Form
    {
        $frm = new Form('frmPreferenceLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'preferlang_prefer_id');
        $frm->addSelectBox('', 'preferlang_lang_id', Language::getAllNames(), '', [], '');
        $frm->addRequiredField(Label::getLabel('LBL_PREFERENCE_TITLE', $langId), 'prefer_title');
        Translator::addTranslatorActions($frm, $langId, $recordId, Preference::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Update Sort Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditPreferences();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $preference = new Preference();
            if (!$preference->updateOrder($post['preferences'])) {
                FatUtility::dieJsonError($preference->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
        }
    }

    /**
     * Remove User Preferences
     * 
     * @param array $preferencesIds
     * @return bool
     */
    private function removeUserPreferences(array $preferencesIds = []): bool
    {
        $query = 'DELETE FROM ' . Preference::DB_TBL_USER_PREF . ' WHERE 1 = 1';
        if (!empty($preferencesIds)) {
            $preferencesIds = implode(",", $preferencesIds);
            $query .= ' and uprefer_prefer_id IN (' . $preferencesIds . ')';
        }
        $db = FatApp::getDb();
        $db->query($query);
        if ($db->getError()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }
}
