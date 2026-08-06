<?php

/**
 * States Controller is used for States handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class StatesController extends AdminBaseController
{

    /**
     * Initialize States
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewStates();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("search", $this->getSearchForm($this->siteLangId));
        $this->set('canEdit', $this->objPrivilege->canEditStates(true));
        $this->_template->render();
    }

    /**
     * Search & List States
     */
    public function search()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $srch = new SearchBased(State::DB_TBL, 'st');
        $srch->joinTable(State::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = st.state_id and stlang.stlang_lang_id = ' . $this->siteLangId, 'stlang');
        $srch->joinTable(Country::DB_TBL, 'LEFT JOIN', 'c.country_id = st.state_country_id', 'c');
        $srch->joinTable(Country::DB_TBL_LANG, 'LEFT JOIN', 'clang.countrylang_country_id  = c.country_id  and clang.countrylang_lang_id   = ' . $this->siteLangId, 'clang');
        $srch->addMultipleFields(['st.*', 'stlang.state_name as state_name', 'IFNULL(clang.country_name, c.country_identifier) as country_identifier', 'st.state_identifier']);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $condition = $srch->addCondition('stlang.state_name', 'like', '%' . $keyword . '%');
            $condition->attachCondition('st.state_identifier', 'like', '%' . $keyword . '%', 'OR');
            $condition->attachCondition('st.state_code', 'like', '%' . $keyword . '%', 'OR');
        }
        if (!empty($post['state_country_id'])) {
            $srch->addCondition('st.state_country_id', '=', $post['state_country_id']);
        }
        $srch->addOrder('state_active', 'DESC');
        $srch->addOrder('state_name');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('canEdit', $this->objPrivilege->canEditStates(true));
        $this->set('activeInactiveArr', AppConstant::getActiveArr());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Render state Form
     * 
     * @param int $stateId
     */
    public function form(int $stateId = 0)
    {
        $this->objPrivilege->canEditStates();
        $data = State::getAttributesById($stateId);
        $frm = $this->getForm();
        $frm->fill($data);
        $this->sets([
            'languages' => Language::getAllNames(),
            'stateId' => $stateId, 'frm' => $frm
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup state
     */
    public function setup()
    {
        $this->objPrivilege->canEditStates();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['state_country_id'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $country = Country::getAttributesById($post['state_country_id'], ['country_active']);
        if($country['country_active'] == AppConstant::NO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COUNTRY_IS_INACTIVE', $this->siteLangId));
        }
        $srch = new SearchBase(State::DB_TBL);
        $srch->addCondition('state_country_id', '=', $post['state_country_id']);
        $srch->addCondition('mysql_func_LOWER(state_code)', '=', strtolower(trim($post['state_code'])), 'AND', true);
        $srch->addCondition('state_id', '!=', $post['state_id']);
        $srch->doNotCalculateRecords();
        if (FatApp::getDb()->fetch($srch->getResultSet()) && $post['state_code'] != '') {
            FatUtility::dieJsonError(Label::getLabel('LBL_STATE_CODE_IS_ALREADY_IN_USE', $this->siteLangId));
        }

        $srch = new SearchBase(State::DB_TBL);
        $srch->addCondition('state_country_id', '=', $post['state_country_id']);
        $srch->addCondition('mysql_func_LOWER(state_identifier)', '=', strtolower(trim($post['state_identifier'])), 'AND', true);
        $srch->addCondition('state_id', '!=', $post['state_id']);
        $srch->doNotCalculateRecords();
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_STATE_IDENTIFIER_IS_ALREADY_IN_USE', $this->siteLangId));
        }
        if ($post['state_active'] == AppConstant::NO) {
            $srch = new SearchBase(UserAddresses::DB_TBL);
            $srch->addCondition('usradd_state_id', '=', $post['state_id']);
            $srch->addDirectCondition('usradd_deleted IS NULL');
            $srch->doNotCalculateRecords();
            if (FatApp::getDb()->fetch($srch->getResultSet())) {
                FatUtility::dieJsonError(Label::getLabel('LBL_STATE_ATTACHED_WITH_THE_ADDRESS_CAN_NOT_BE_MARKED_AS_INACTIVE'));
            }
        }

        $stateId = FatUtility::int($post['state_id']);
        $data = State::getAttributesById($stateId);
        $state = new state($stateId);
        $state->assignValues($post);
        if (!$state->save()) {
            FatUtility::dieJsonError($state->getError());
        }
        $data = [
            'stateId' => $state->getMainTableRecordId(),
            'msg' => Label::getLabel('LBL_STATE_SETUP_SUCCESSFUL')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render state Lang Data
     */
    public function langForm()
    {
        $post = FatApp::getPostedData();
        $stateId = $post['stlang_state_id'];
        $langId = $post['stlang_lang_id'];
        if (!State::getAttributesById($stateId, ['state_id'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* get lang data */
        $srch = new SearchBase(State::DB_TBL_LANG);
        $srch->addCondition('stlang_lang_id', '=', $langId);
        $srch->addCondition('stlang_state_id', '=', $stateId);
        $srch->addMultipleFields(['state_name', 'stlang_lang_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        /* fill form data */
        $frm = $this->getLangForm($langId, $stateId);
        $frm->fill($data);
        $frm->fill(['stlang_lang_id' => $langId, 'stlang_state_id' => $stateId]);
        $this->sets([
            'stateId' => $stateId,
            'langFrm' => $frm,
            'formLayout' => Language::getLayoutDirection($langId),
            'languages' => Language::getAllNames(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Lang Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditStates();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['stlang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!State::getAttributesById($post['stlang_state_id'], 'state_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $state = new State($post['stlang_state_id']);
        $countryId = $state::getAttributesById($post['stlang_state_id'],['state_country_id'])['state_country_id'];
        $srch = new SearchBase(State::DB_TBL, 's');
        $srch->joinTable(State::DB_TBL_LANG, 'INNER JOIN', 'sl.stlang_state_id  = s.state_id and sl.stlang_lang_id  = ' . $post['stlang_lang_id'], 'sl');
        $srch->addCondition('s.state_country_id', '=', $countryId);
        $srch->addCondition('sl.state_name', '=', trim($post['state_name']));
        $srch->addCondition('s.state_id', '!=', $post['stlang_state_id']);
        $srch->doNotCalculateRecords();
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_STATE_NAME_IS_ALREADY_IN_USE', $this->siteLangId));
        }
        if (!$state->addUpdateLangData($post)) {
            FatUtility::dieJsonError($state->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(State::DB_TBL_LANG, $post['stlang_state_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'stateId' => $post['stlang_state_id'],
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')
        ]);
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
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword');
        $frm->addSelectBox(Label::getLabel('LBL_Country'), 'state_country_id', Country::getOptions($this->siteLangId), '', [], Label::getLabel('LBL_SELECT'));
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear_Search'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Form
     * 
     * @param int $stateId
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmstate');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_IDENTIFIER'), 'state_identifier');
        $fld->requirements()->setRequired();
        $frm->addHiddenField('', 'state_id');
        $frm->addTextBox(Label::getLabel('LBL_STATE_CODE'), 'state_code', '', []);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_COuntry'), 'state_country_id', Country::getOptions($this->siteLangId), '', [], '');
        $fld->requirements()->setRequired();
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'state_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Language Form
     * 
     * @param int $stateId
     * @param int $lang_id
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $stateId = 0): Form
    {
        $frm = new Form('frmstlang');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'stlang_state_id');
        $fld->requirements()->setRequired();
        $fld = $frm->addHiddenField('', 'stlang_lang_id');
        $fld->requirements()->setRequired();
        $frm->addRequiredField(Label::getLabel('LBL_State_Name', $langId), 'state_name');
        Translator::addTranslatorActions($frm, $langId, $stateId, State::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Change Status of the State
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditStates();
        $stateId = FatApp::getPostedData('stateId', FatUtility::VAR_INT, 0);
        if (0 == $stateId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $state = new State($stateId);
        if (!$state->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $statesData = $state->getFlds();
        $status = ($statesData['state_active'] == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        if ($status == AppConstant::INACTIVE) {
            $srch = new SearchBase(UserAddresses::DB_TBL);
            $srch->addCondition('usradd_state_id', '=', $stateId);
            $srch->addDirectCondition('usradd_deleted IS NULL');
            $srch->doNotCalculateRecords();
            if(FatApp::getDb()->fetch($srch->getResultSet())) {
                FatUtility::dieJsonError(Label::getLabel('LBL_STATE_ATTACHED_WITH_THE_ADDRESS_CAN_NOT_BE_MARKED_AS_INACTIVE'));
            }
        }
        $this->updateStateStatus($stateId, $status);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Update State Status
     * 
     * @param int $stateId
     * @param int $status
     */
    private function updateStateStatus($stateId, $status)
    {
        $status = FatUtility::int($status);
        $stateId = FatUtility::int($stateId);
        if (1 > $stateId || -1 == $status) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $state = new State($stateId);
        if (!$state->changeStatus($status)) {
            FatUtility::dieJsonError($state->getError());
        }
    }
}
