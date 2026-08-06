<?php

/**
 * Content Pages Controller is used for Content Pages handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ContentPagesController extends AdminBaseController
{

    /**
     * Initialize ContentPages
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewContentPages();
    }

    /**
     * Render Search Form 
     */
    public function index()
    {
        $this->set('includeEditor', true);
        $this->set('frmSearch', $this->getSearchForm());
        $this->set("canEdit", $this->objPrivilege->canEditContentPages(true));
        $this->_template->render();
    }

    /**
     * Search & List Content Pages
     */
    public function search()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $searchForm = $this->getSearchForm();
        $data = FatApp::getPostedData();
        $post = $searchForm->getFormDataFromArray($data);
        $srch = ContentPage::getSearchObject($this->siteLangId);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('p.cpage_identifier', 'like', '%' . $keyword . '%');
            $cond->attachCondition('p_l.cpage_title', 'like', '%' . $keyword . '%');
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("canEdit", $this->objPrivilege->canEditContentPages(true));
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->_template->render(false, false);
    }

    /**
     * Layouts
     */
    public function layouts()
    {
        $this->_template->render(false, false);
    }

    /**
     * Render Form
     * 
     * @param int $pageId
     */
    public function form($pageId = 0)
    {
        $this->objPrivilege->canEditContentPages();
        $pageId = FatUtility::int($pageId);
        $blockFrm = $this->getForm($pageId);
        if (0 < $pageId) {
            $data = ContentPage::getAttributesById($pageId, ['cpage_id', 'cpage_identifier', 'cpage_layout']);
            if ($data === false) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $blockFrm->fill($data);
            $this->set('cpage_layout', $data['cpage_layout']);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('cpage_id', $pageId);
        $this->set('blockFrm', $blockFrm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Content Block
     */
    public function setup()
    {
        $this->objPrivilege->canEditContentPages();
        $frm = $this->getForm(0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $pageId = $post['cpage_id'];
        unset($post['cpage_id']);
        $contentPage = new ContentPage($pageId);
        $contentPage->assignValues($post);
        if (!$contentPage->save()) {
            FatUtility::dieJsonError($contentPage->getError());
        }
        $newTabLangId = 0;
        if ($pageId > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = ContentPage::getAttributesByLangId($langId, $pageId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $pageId = $contentPage->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'pageId' => $pageId, 'langId' => $newTabLangId,
            'cpage_layout' => $post['cpage_layout']
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Language Form
     * 
     * @param type $pageId
     * @param type $lang_id
     * @param type $cpage_layout
     */
    public function langForm($pageId = 0, $lang_id = 0)
    {
        $this->objPrivilege->canEditContentPages();
        $pageId = FatUtility::int($pageId);
        $lang_id = FatUtility::int($lang_id);
        if ($pageId == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $contentPage = ContentPage::getAllAttributesById($pageId);
        if (empty($contentPage)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $blockLangFrm = $this->getLangForm($pageId, $lang_id, $contentPage['cpage_layout']);
        $langData = ContentPage::getAttributesByLangId($lang_id, $pageId);
        if ($langData) {
            $blockData = ContentPage::getPageBlocksContent($pageId, $lang_id);
            foreach ($blockData as $blockKey => $blockContent) {
                $langData['cpblock_content_block_' . $blockKey] = $blockContent['cpblocklang_text'];
            }
            $blockLangFrm->fill($langData);
        }
        $file = new Afile(Afile::TYPE_CPAGE_BACKGROUND_IMAGE, $lang_id);
        $bgImage = $file->getFile($pageId);
        $this->set('bgImage', $bgImage);
        $this->set('bannerTypeArr', AppConstant::bannerTypeArr());
        $this->set('languages', Language::getAllNames());
        $this->set('cpage_id', $pageId);
        $this->set('cpage_lang_id', $lang_id);
        $this->set('cpage_layout', $contentPage['cpage_layout']);
        $this->set('blockLangFrm', $blockLangFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    /**
     * Language Setup
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditContentPages();
        $post = FatApp::getPostedData();
        $pageId = $post['cpage_id'];
        $lang_id = $post['lang_id'];
        $cpage_layout = $post['cpage_layout'];
        if ($pageId == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($pageId, $lang_id, $cpage_layout);
        if (false === $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($post['cpage_id']);
        unset($post['lang_id']);
        $data = [
            'cpagelang_lang_id' => $lang_id,
            'cpagelang_cpage_id' => $pageId,
            'cpage_title' => $post['cpage_title']
        ];
        if ($cpage_layout == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
            $data['cpage_image_title'] = $post['cpage_image_title'];
        } else {
            $data['cpage_content'] = $post['cpage_content'];
        }
        $contentPage = new ContentPage($pageId);
        if (!$contentPage->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($contentPage->getError());
        }
        $pageId = $contentPage->getMainTableRecordId();
        if (!$pageId) {
            $pageId = FatApp::getDb()->getInsertId();
        }

        if ($cpage_layout == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
            for ($i = 1; $i <= ContentPage::CONTENT_PAGE_LAYOUT1_BLOCK_COUNT; $i++) {
                $data['cpblocklang_text'] = $post['cpblock_content_block_' . $i];
                $data['cpblocklang_block_id'] = $i;
                if (!$contentPage->addUpdateContentPageBlocks($lang_id, $pageId, $data)) {
                    FatUtility::dieJsonError($contentPage->getError());
                }
            }
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = ContentPage::getAttributesByLangId($langId, $pageId)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $post['cpage_id'] = $pageId;
        $translator = new Translator();
        if (!$translator->validateAndTranslate(ContentPage::DB_TBL_LANG, $pageId, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'pageId' => $pageId, 'langId' => $newTabLangId,
            'cpage_layout' => $cpage_layout
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditContentPages();
        $pageId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (!$identifier = ContentPage::getAttributesById($pageId, 'cpage_identifier')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $contentPage = new ContentPage($pageId);
        $contentPage->assignValues([ContentPage::tblFld('deleted') => 1, 'cpage_identifier' => $identifier . '-' . $pageId]);
        if (!$contentPage->save()) {
            FatUtility::dieJsonError($contentPage->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Auto Complete
     */
    public function autoComplete()
    {
        $db = FatApp::getDb();
        $srch = ContentPage::getSearchObject($this->siteLangId);
        $post = FatApp::getPostedData();
        if (!empty($post['keyword'])) {
            $srch->addCondition('cpage_title', 'LIKE', '%' . trim($post['keyword']) . '%');
        }
        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10));
        $srch->addMultipleFields(['cpage_id', 'IFNULL(cpage_title,cpage_identifier) as cpage_name']);
        $products = $db->fetchAll($srch->getResultSet(), 'cpage_id');
        $json = [];
        foreach ($products as $key => $product) {
            $json[] = ['id' => $key, 'name' => strip_tags(html_entity_decode($product['cpage_name'], ENT_QUOTES, 'UTF-8'))];
        }
        die(json_encode($json));
    }

    /**
     * Set Up Background Image
     */
    public function setUpBgImage()
    {
        $this->objPrivilege->canEditContentPages();
        $post = FatApp::getPostedData();
        $file_type = FatApp::getPostedData('file_type', FatUtility::VAR_INT, 0);
        $pageId = FatApp::getPostedData('cpage_id', FatUtility::VAR_INT, 0);
        $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $cpage_layout = FatApp::getPostedData('cpage_layout', FatUtility::VAR_INT, 0);
        if (!$file_type || !$pageId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile(Afile::TYPE_CPAGE_BACKGROUND_IMAGE, $lang_id);
        if (!$file->saveFile($_FILES['file'], $pageId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        $fileData = $file->getSavedFile();
        $img = MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_CPAGE_BACKGROUND_IMAGE, $pageId, Afile::SIZE_LARGE, $lang_id]) . '?' . time();
        $msg = $fileData['file_name'] . ' ' . Label::getLabel('LBL_Uploaded_Successfully');
        FatUtility::dieJsonSuccess(['img' => $img, 'file' => $fileData['file_name'], 'cpage_id' => $pageId, 'cpage_layout' => $cpage_layout, 'lang_id' => $lang_id, 'msg' => $msg]);
    }

    /**
     * Remove Background Image
     * 
     * @param int $pageId
     * @param int $langId
     */
    public function removeBgImage($pageId = 0, $langId = 0)
    {
        $this->objPrivilege->canEditContentPages();
        $pageId = FatUtility::int($pageId);
        $langId = FatUtility::int($langId);
        if (!$pageId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile(Afile::TYPE_CPAGE_BACKGROUND_IMAGE, $langId);
        if (!$file->removeFile($pageId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Deleted_Successfully'));
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmPagesSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear_Search'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Form
     * 
     * @param int $pageId
     * @return Form
     */
    private function getForm(int $pageId = 0): Form
    {
        $frm = new Form('frmBlock');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'cpage_id', 0);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Page_Identifier'), 'cpage_identifier');
        $fld->setUnique(ContentPage::DB_TBL, 'cpage_identifier', 'cpage_id', 'cpage_id', 'cpage_id');
        $frm->addSelectBox(Label::getLabel('LBL_Layout_Type'), 'cpage_layout', $this->getAvailableLayouts(), '', ['id' => 'cpage_layout'], '')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Available Layouts
     * 
     * @return array
     */
    private function getAvailableLayouts(): array
    {
        return [
            ContentPage::CONTENT_PAGE_LAYOUT1_TYPE => Label::getLabel('LBL_Content_Page_Layout1'),
            ContentPage::CONTENT_PAGE_LAYOUT2_TYPE => Label::getLabel('LBL_Content_Page_Layout2')
        ];
    }

    /**
     * Get Lang Form
     * 
     * @param int $pageId
     * @param int $langId
     * @param int $cpage_layout
     * @return Form
     */
    private function getLangForm(int $pageId = 0, int $langId = 0, int $cpage_layout = 0): Form
    {
        $frm = new Form('frmBlockLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'cpage_id', $pageId);
        $frm->addHiddenField('', 'lang_id', $langId);
        $frm->addHiddenField('', 'cpage_layout', $cpage_layout);
        $frm->addRequiredField(Label::getLabel('LBL_Page_Title', $langId), 'cpage_title');
        if ($cpage_layout == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
            $frm->addButton(Label::getLabel('LBL_Backgroud_Image', $langId), 'cpage_bg_image', Label::getLabel('LBL_Upload_Image'), [
                'class' => 'bgImageFile-Js',
                'id' => 'cpage_bg_image',
                'data-file_type' => Afile::TYPE_CPAGE_BACKGROUND_IMAGE,
                'data-frm' => 'frmBlock'
            ]);
            $frm->addTextBox(Label::getLabel('LBL_Background_Image_Title', $langId), 'cpage_image_title');
            for ($i = 1; $i <= ContentPage::CONTENT_PAGE_LAYOUT1_BLOCK_COUNT; $i++) {
                $fld = $frm->addHtmlEditor(Label::getLabel('LBL_Content_Block_' . $i, $langId), 'cpblock_content_block_' . $i);
                if ($i == 2) {
                    $textFld = $frm->addHtml('', 'block_2_helptext', '<small>' . Label::getLabel("LBL_CONTENT_LAYOUT_HELPTEXT") . '</small>');
                    $fld->attachField($textFld);
                }
            }
        } else {
            $frm->addHtmlEditor(Label::getLabel('LBL_Page_Content', $langId), 'cpage_content');
        }
        Translator::addTranslatorActions($frm, $langId, $pageId, ContentPage::DB_TBL_LANG);
        return $frm;
    }
}
