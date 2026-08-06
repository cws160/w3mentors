<?php

/**
 * Email Template is used for Email Templates handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class FaqCategoriesController extends AdminBaseController
{

    /**
     * Initialize Faq Category
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewFaqCategory();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("searchFrm", $this->getSearchForm());
        $this->set("canEdit", $this->objPrivilege->canEditFaqCategory(true));
        $this->_template->render();
    }

    /**
     * Search & List Faq Categories
     */
    public function search()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $srch = FaqCategory::getSearchObject($this->siteLangId);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $condition = $srch->addCondition('fc.faqcat_identifier', 'like', '%' . $keyword . '%');
            $condition->attachCondition('fc_l.faqcat_name', 'like', '%' . $keyword . '%', 'OR');
        }
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        $srch->addOrder('faqcat_active', 'DESC');
        $srch->addOrder('faqcat_order', 'asc');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("canEdit", $this->objPrivilege->canEditFaqCategory(true));
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->_template->render(false, false);
    }

    /**
     * Render Faq Category Form
     * 
     * @param int $faqcat_id
     */
    public function form($faqcat_id = 0)
    {
        $this->objPrivilege->canEditFaqCategory();
        $faqcat_id = FatUtility::int($faqcat_id);
        $faqCatFrm = $this->getForm();
        $faqCatFrm->fill(['faqcat_id' => $faqcat_id]);
        if (0 < $faqcat_id) {
            $data = FaqCategory::getAttributesById($faqcat_id, ['faqcat_id', 'faqcat_identifier', 'faqcat_active', 'faqcat_featured']);
            if ($data === false) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $faqCatFrm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('faqcat_id', $faqcat_id);
        $this->set('faqCatFrm', $faqCatFrm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Faq Category 
     */
    public function setup()
    {
        $this->objPrivilege->canEditFaqCategory();
        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $faqcat_id = FatUtility::int($post['faqcat_id']);
        unset($post['faqcat_id']);
        $faqCategory = new FaqCategory($faqcat_id);
        if ($faqcat_id == 0) {
            $display_order = $faqCategory->getMaxOrder();
            $post['faqcat_order'] = $display_order;
        }
        $faqCategory->assignValues($post);
        if (!$faqCategory->save()) {
            FatUtility::dieJsonError($faqCategory->getError());
        }
        $newTabLangId = 0;
        if ($faqcat_id > 0) {
            $catId = $faqcat_id;
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = FaqCategory::getAttributesByLangId($langId, $faqcat_id)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $catId = $faqCategory->getMainTableRecordId();
            $newTabLangId = $this->siteLangId;
        }
        $data = [
            'msg' => Label::getLabel('LBL_Category_Setup_Successful'),
            'catId' => $catId, 'langId' => $newTabLangId
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Faq Category Language Form
     * 
     * @param int $faqcat_id
     * @param int $lang_id
     */
    public function langForm($faqcat_id = 0, $lang_id = 0)
    {
        $faqcat_id = FatUtility::int($faqcat_id);
        $lang_id = FatUtility::int($lang_id);
        if ($faqcat_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $faqCatLangFrm = $this->getLangForm($lang_id, $faqcat_id);
        $formData = ['faqcat_id' => $faqcat_id, 'lang_id' => $lang_id];
        $langData = FaqCategory::getAttributesByLangId($lang_id, $faqcat_id);
        if ($langData) {
            $formData = array_merge($formData, $langData);
        }
        $faqCatLangFrm->fill($formData);
        $this->set('languages', Language::getAllNames());
        $this->set('faqcat_id', $faqcat_id);
        $this->set('faqcat_lang_id', $lang_id);
        $this->set('faqCatLangFrm', $faqCatLangFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    /**
     * Setup Faq Category Language
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditFaqCategory();
        $post = FatApp::getPostedData();
        $faqcat_id = $post['faqcat_id'];
        $lang_id = $post['lang_id'];
        if ($faqcat_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($lang_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        unset($post['faqcat_id']);
        unset($post['lang_id']);
        $data = [
            'faqcatlang_lang_id' => $lang_id,
            'faqcatlang_faqcat_id' => $faqcat_id,
            'faqcat_name' => $post['faqcat_name'],
        ];

        $faqcat = new FaqCategory($faqcat_id);
        if (!$faqcat->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($faqcat->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = FaqCategory::getAttributesByLangId($langId, $faqcat_id)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(FaqCategory::DB_LANG_TBL, $faqcat_id, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_Category_Setup_Successful'),
            'catId' => $faqcat_id, 'langId' => $newTabLangId
        ]);
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditFaqCategory();

        $faqcatId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (1 > $faqcatId) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
        }

        if (!$identifier = FaqCategory::getAttributesById($faqcatId, 'faqcat_identifier')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_CATEGORY_NOT_FOUND'));
        }
        
        $faqCategory = new FaqCategory($faqcatId);
        $faqCategory->assignValues(array(FaqCategory::tblFld('deleted') => 1, 'faqcat_identifier' => $identifier . '-' . $faqcatId));
        if (!$faqCategory->save()) {
            return false;
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Update Sort Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditFaqCategory();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $faqCat = new FaqCategory();
            if (!$faqCat->updateOrder($post['faqcat'])) {
                FatUtility::dieJsonError($faqCat->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_Order_Updated_Successfully'));
        }
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditFaqCategory();
        $faqcatId = FatApp::getPostedData('faqcatId', FatUtility::VAR_INT, 0);
        if (!$this->updateFaqCatStatus($faqcatId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Update Faq Category Status
     * 
     * @param int $faqcatId
     * @param int $status
     * @return boolean
     */
    private function updateFaqCatStatus(int $faqcatId, int $status = null)
    {
        $faqCategory = new FaqCategory($faqcatId);
        if (!$faqCategory->loadFromDb()) {
            return false;
        }
        $data = $faqCategory->getFlds();
        if (is_null($status)) {
            $status = ($data['faqcat_active'] == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        }
        if (!$faqCategory->changeStatus($status)) {
            return false;
        }
        return true;
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
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear_Search'), ['onclick' => 'clearSearch();']);
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Faq Category Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmFaqCat');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'faqcat_id');
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Category_Identifier'), 'faqcat_identifier');
        $fld->setUnique(FaqCategory::DB_TBL, 'faqcat_identifier', 'faqcat_id', 'faqcat_id', 'faqcat_id');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'faqcat_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Language Form
     * 
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $faqcatId = 0): Form
    {
        $frm = new Form('frmFaqCatLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'faqcat_id');
        $frm->addHiddenField('', 'lang_id');
        $frm->addRequiredField(Label::getLabel('LBL_Category_Name', $langId), 'faqcat_name');
        Translator::addTranslatorActions($frm, $langId, $faqcatId, FaqCategory::DB_LANG_TBL);
        return $frm;
    }
}
