<?php

/**
 * Content Block Controller is used for Content Block handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ContentBlockController extends AdminBaseController
{

    /**
     * Initialize ContentBlock
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewContentBlocks();
    }

    public function index()
    {
        $this->set('includeEditor', true);
        $this->set('blockTypes', ExtraPage::getTypes());
        $this->_template->render();
    }

    /**
     * Search & List Content Blocks
     */
    public function search(int $type)
    {
        $type = FatUtility::int($type);
        if ($type <= 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = ExtraPage::getSearchObject($this->siteLangId, false);
        $srch->addCondition('epage_type', '=', $type);
        $srch->addOrder('epage_active', 'DESC');
        $srch->addOrder('epage_order', 'asc');
        $rs = $srch->getResultSet();
        $records = FatApp::getDb()->fetchAll($rs);
        $activeInactiveArr = AppConstant::getActiveArr();
        $this->set("activeInactiveArr", $activeInactiveArr);
        $this->set("arr_listing", $records);
        $this->set("type", $type);
        $this->set("canEdit", $this->objPrivilege->canEditContentBlocks(true));
        $this->_template->render(false, false);
    }

    /**
     * Render Content Block From
     * 
     * @param type $epage_id
     */
    public function form($epage_id = 0)
    {
        $this->objPrivilege->canEditContentBlocks();
        $epage_id = FatUtility::int($epage_id);
        $blockFrm = $this->getForm($epage_id, $this->siteLangId);
        if (0 < $epage_id) {
            $data = ExtraPage::getAttributesById($epage_id, ['epage_id', 'epage_identifier', 'epage_active']);
            if ($data === false) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $blockFrm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('epage_id', $epage_id);
        $this->set('blockFrm', $blockFrm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Content Block
     */
    public function setup()
    {
        $this->objPrivilege->canEditContentBlocks();
        $frm = $this->getForm(0, $this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $epage_id = $post['epage_id'];
        if (1 > $epage_id) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = ExtraPage::getAttributesById($epage_id, ['epage_id', 'epage_identifier']);
        if ($data === false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $row = new ExtraPage($epage_id);
        $row->setFldValue('epage_active', $post['epage_active']);
        $row->setFldValue('epage_identifier', $post['epage_identifier']);
        if (!$row->save()) {
            FatUtility::dieJsonError($row->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = ExtraPage::getAttributesByLangId($langId, $epage_id)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $data = [
            'epageId' => $epage_id, 'langId' => $newTabLangId,
            'msg' => Label::getLabel('LBL_Setup_Successful')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Language Form
     * 
     * @param int $epage_id
     * @param int $lang_id
     */
    public function langForm($epage_id = 0, $lang_id = 0)
    {
        $this->objPrivilege->canEditContentBlocks();
        $epage_id = FatUtility::int($epage_id);
        $lang_id = FatUtility::int($lang_id);
        if ($epage_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $epageData = ExtraPage::getAttributesById($epage_id);
        $blockLangFrm = $this->getLangForm($epage_id, $lang_id, $epageData['epage_editable']);
        $langData = ExtraPage::getAttributesByLangId($lang_id, $epage_id);
        if ($langData) {
            $blockLangFrm->fill($langData);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('epage_id', $epage_id);
        $this->set('epage_lang_id', $lang_id);
        $this->set('blockLangFrm', $blockLangFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->set('epageData', $epageData);
        $this->_template->render(false, false);
    }

    /**
     * Language Setup
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditContentBlocks();
        $post = FatApp::getPostedData();
        $epage_id = FatUtility::int($post['epage_id']);
        $lang_id = FatUtility::int($post['lang_id']);
        if ($epage_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$epageData = ExtraPage::getAttributesById($epage_id, ['epage_editable'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($epage_id, $lang_id, $epageData['epage_editable']);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($post['epage_id']);
        unset($post['lang_id']);
        $data = [
            'epagelang_lang_id' => $lang_id,
            'epagelang_epage_id' => $epage_id,
            'epage_label' => $post['epage_label'],
            'epage_content' => $post['epage_content'] ?? '',
        ];
        $epage = new ExtraPage($epage_id);
        if (!$epage->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($epage->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = ExtraPage::getAttributesByLangId($langId, $epage_id)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(ExtraPage::DB_TBL_LANG, $epage_id, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'epageId' => $epage_id, 'langId' => $newTabLangId
        ]);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditContentBlocks();
        $epageId = FatApp::getPostedData('epageId', FatUtility::VAR_INT, 0);
        if (0 == $epageId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $epage = new ExtraPage($epageId);
        if (!$epage->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $contentBlockData = $epage->getFlds();
        $status = ($contentBlockData['epage_active'] == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        $this->updateEPageStatus($epageId, $status);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Update Sort Order
     *
     * @param int $onDrag
     * @return json
     */
    public function updateOrder(int $onDrag = 1)
    {
        $this->objPrivilege->canEditContentBlocks();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $cateObj = new ExtraPage();
            if (!$cateObj->updateOrder($post['blockListingTbl'])) {
                FatUtility::dieJsonError($cateObj->getError());
            }
            if ($onDrag == 0) {
                FatUtility::dieJsonSuccess('');
            } else {
                FatUtility::dieJsonSuccess(Label::getLabel('LBL_Order_Updated_Successfully'));
            }
        }
    }

    /**
     * Update EPage Status
     * 
     * @param int $epageId
     * @param int $status
     */
    private function updateEPageStatus($epageId, $status)
    {
        $status = FatUtility::int($status);
        $epageId = FatUtility::int($epageId);
        if (1 > $epageId || -1 == $status) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $EPage = new ExtraPage($epageId);
        if (!$EPage->changeStatus($status)) {
            FatUtility::dieJsonError($EPage->getError());
        }
    }

    /**
     * Get Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $this->objPrivilege->canViewContentBlocks();
        $frm = new Form('frmBlock');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'epage_id', 0);
        $frm->addRequiredField(Label::getLabel('LBL_Page_Identifier'), 'epage_identifier');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'epage_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Lang Form
     * 
     * @param int $epage_id
     * @param int $lang_id
     * @param int $editable
     * @return Form
     */
    private function getLangForm($epage_id = 0, $langId = 0, $editable = 1): Form
    {
        $frm = new Form('frmBlockLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'epage_id', $epage_id);
        $frm->addHiddenField('', 'lang_id', $langId);
        $frm->addRequiredField(Label::getLabel('LBL_Page_Title', $langId), 'epage_label');
        if ($editable == AppConstant::YES) {
            $frm->addHtmlEditor(Label::getLabel('LBL_Page_Content', $langId), 'epage_content');
        }
        
        Translator::addTranslatorActions($frm, $langId, $epage_id, ExtraPage::DB_TBL_LANG);
        return $frm;
    }

}
