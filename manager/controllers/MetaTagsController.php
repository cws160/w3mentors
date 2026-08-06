<?php

/**
 * Meta Tags Controller is used for Meta Tags handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class MetaTagsController extends AdminBaseController
{

    /**
     * Initialize MetaTags
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewMetaTags();
    }

    public function index()
    {
        $this->sets(['tabsArr' => MetaTag::getTabsArr(), 'activeTab' => MetaTag::META_GROUP_DEFAULT]);
        $this->_template->render();
    }

    /**
     * List Meta Tags
     */
    public function listMetaTags()
    {
        $metaType = FatApp::getPostedData('metaType', FatUtility::VAR_INT, MetaTag::META_GROUP_DEFAULT);
        $searchForm = $this->getSearchForm($metaType);
        $showFilters = (in_array($metaType, [MetaTag::META_GROUP_DEFAULT])) ? false : true;
        $this->sets([
            'metaTypeDefault' => MetaTag::META_GROUP_DEFAULT,
            'showFilters' => $showFilters,
            'metaType' => $metaType,
            'frmSearch' => $searchForm,
            'canEdit' => $this->objPrivilege->canEditMetaTags(true),
            'canAdd' => in_array($metaType, [MetaTag::META_GROUP_OTHER]),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Search & List Meta tags
     */
    public function search()
    {
        $data = FatApp::getPostedData();
        $searchForm = $this->getSearchForm($data['metaType']);
        $post = $searchForm->getFormDataFromArray($data);
        $metaType = FatUtility::convertToType($post['metaType'], FatUtility::VAR_INT, MetaTag::META_GROUP_DEFAULT);
        if ($metaType == MetaTag::META_GROUP_COURSE && !Course::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        if ($metaType == MetaTag::META_GROUP_GRP_CLASS && !GroupClass::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_GROUP_CLASS_MODULE_NOT_AVAILABLE'));
        }
        

        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $metaSrch = new MetaTagSearch($this->siteLangId);
        $criteria['metaType'] = ['val' => $metaType];
        if (!empty($post['keyword'])) {
            $criteria['keyword'] = ['val' => trim($post['keyword'])];
        }
        if (isset($post['hasTagsAssociated']) && $post['hasTagsAssociated'] != '') {
            $criteria['hasTagsAssociated'] = ['val' => $post['hasTagsAssociated']];
        }
        $metaSrch->searchByCriteria($criteria, $this->siteLangId);
        $metaSrch->addMultipleFields($this->getDbColumns($metaType));
        $metaSrch->setPageNumber($page);
        $metaSrch->setPageSize($pagesize);
        // pr($metaSrch->getQuery());
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $metaSrch];
        }
        $records = FatApp::getDb()->fetchAll($metaSrch->getResultSet());
        $this->set("meta_record_id", $this->getMetaRecordcolumn($metaType));
        $this->set("columnsArr", $this->getColumns($metaType));
        $this->set("arr_listing", $records);
        $this->set('pageCount', $metaSrch->pages());
        $this->set('recordCount', $metaSrch->recordCount());
        $this->set('page', $page);
        $this->set('metaType', $metaType);
        $this->set('pageSize', $pagesize);
        $this->set('metaType', $metaType);
        $this->set('postedData', $post);
        $this->set('canEdit', $this->objPrivilege->canEditMetaTags(true));
        $this->_template->render(false, false);
    }

    /**
     * Render Meta Tag Form
     */
    public function form()
    {
        $this->objPrivilege->canEditMetaTags();
        $metaId = FatApp::getPostedData('metaId', FatUtility::VAR_INT, 0);
        $metaType = FatApp::getPostedData('metaType', FatUtility::VAR_INT, MetaTag::META_GROUP_DEFAULT);
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_STRING, '');
        if ($metaType == MetaTag::META_GROUP_COURSE && !Course::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        if ($metaType == MetaTag::META_GROUP_GRP_CLASS && !GroupClass::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_GROUP_CLASS_MODULE_NOT_AVAILABLE'));
        }
        $frm = $this->getForm($metaType);
        $frm->fill(['meta_type' => $metaType, 'meta_record_id' => $recordId]);
        if ($metaId > 0) {
            $data = MetaTag::getAttributesById($metaId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            if ($metaType == MetaTag::META_GROUP_OTHER) {
                $data['meta_slug'] = MetaTag::getOrignialUrlFromComponents($data);
            }
            $frm->fill($data);
        }
        $this->set('frm', $frm);
        $this->set('recordId', $recordId);
        $this->set('metaId', $metaId);
        $this->set('metaType', $metaType);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Setup Meta Tag
     */
    public function setup()
    {
        $this->objPrivilege->canEditMetaTags();
        $tabsArr = MetaTag::getTabsArr();
        $metaType = FatApp::getPostedData('meta_type', FatUtility::VAR_INT, MetaTag::META_GROUP_DEFAULT);
        if ($metaType == MetaTag::META_GROUP_COURSE && !Course::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        if ($metaType == MetaTag::META_GROUP_GRP_CLASS && !GroupClass::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_GROUP_CLASS_MODULE_NOT_AVAILABLE'));
        }
        $metaId = FatApp::getPostedData('meta_id', FatUtility::VAR_INT, 0);
        $frm = $this->getForm($metaType);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!isset($tabsArr[$metaType])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($metaId == 0 && $metaType == MetaTag::META_GROUP_DEFAULT) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($metaId > 0) {
            $data = MetaTag::getAttributesById($metaId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
        }
        if (!$this->setUrlComponents($metaType, $post)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        unset($post['meta_id']);
        $metaTag = new MetaTag($metaId);
        $metaTag->assignValues($post);
        if (!$metaTag->save()) {
            FatUtility::dieJsonError($metaTag->getError());
        }
        $newTabLangId = 0;
        if ($metaId > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!MetaTag::getAttributesByLangId($langId, $metaId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }
        $data = [
            'metaId' => $metaTag->getMainTableRecordId(),
            'metaType' => $metaType,
            'langId' => $newTabLangId,
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Meta Tag Language Form
     * 
     * @param int $metaId
     * @param int $langId
     * @param int $metaType
     */
    public function langForm($metaId = 0, $langId = 0, int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $this->objPrivilege->canEditMetaTags();
        if ($metaType == MetaTag::META_GROUP_COURSE && !Course::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        if ($metaType == MetaTag::META_GROUP_GRP_CLASS && !GroupClass::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_GROUP_CLASS_MODULE_NOT_AVAILABLE'));
        }
        $metaId = FatUtility::int($metaId);
        $langId = FatUtility::int($langId);
        if (!$data = MetaTag::getAttributesById($metaId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($metaId, $langId);
        $recordId = FatUtility::int($data['meta_record_id']);
        $langData = MetaTag::getAttributesByLangId($langId, $metaId);
        if ($langData) {
            $langFrm->fill($langData);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('metaId', $metaId);
        $this->set('metaType', $metaType);
        $this->set('recordId', $recordId);
        $this->set('langId', $langId);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->_template->render(false, false);
    }

    /**
     * Language Setup
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditMetaTags();
        $metaId = FatApp::getPostedData('meta_id', FatUtility::VAR_INT, 0);
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if ($metaId == 0 || $langId == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $metaTag = MetaTag::getAttributesById($metaId, ['meta_id', 'meta_type']);
        if (empty($metaTag)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
        }
        if ($metaTag['meta_type'] == MetaTag::META_GROUP_COURSE && !Course::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        if ($metaTag['meta_type'] == MetaTag::META_GROUP_GRP_CLASS && !GroupClass::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_GROUP_CLASS_MODULE_NOT_AVAILABLE'));
        }
        $data = FatApp::getPostedData();
        if (!$data['meta_other_meta_tags'] == '' && $data['meta_other_meta_tags'] == strip_tags($data['meta_other_meta_tags'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_Invalid_Other_Meta_Tag'));
        }
        $frm = $this->getLangForm($metaId, $langId);
        $data = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $data) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($data['meta_id']);
        unset($data['lang_id']);
        unset($data['btn_submit']);
        unset($data['open_graph_image']);
        $data['metalang_lang_id'] = $langId;
        $data['metalang_meta_id'] = $metaId;
        $metaTag = new MetaTag($metaId);
        if (!$metaTag->updateLangData($langId, $data)) {
            FatUtility::dieJsonError($metaTag->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = MetaTag::getAttributesByLangId($langId, $metaId)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(MetaTag::DB_LANG_TBL, $metaId, $data)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'metaId' => $metaId, 'langId' => $newTabLangId,
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')
        ]);
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditMetaTags();
        $metaId = FatApp::getPostedData('metaId', FatUtility::VAR_INT, 0);
        $metaTag = new MetaTag($metaId);
        if (!$metaTag->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$metaTag->deleteRecord(true)) {
            FatUtility::dieJsonError($metaTag->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Setup OG Image
     * 
     * @param int $metaId
     */
    public function setUpOgImage($metaId)
    {
        $this->objPrivilege->canEditMetaTags();
        $post = FatApp::getPostedData();
        $metaId = FatUtility::int($metaId);
        $metaTag = MetaTag::getAttributesById($metaId, ['meta_id', 'meta_type']);
        if (empty($metaTag) || in_array($metaTag['meta_type'], [MetaTag::META_GROUP_GRP_CLASS, MetaTag::META_GROUP_TEACHER])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if (empty($_FILES['file']['name'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_PLEASE_SELECT_A_FILE'));
        }
        $file = new Afile(Afile::TYPE_OPENGRAPH_IMAGE, $langId);
        if (!$file->saveFile($_FILES['file'], $metaId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess([
            'metaId' => $metaId, 'file' => $_FILES['file']['name'],
            'msg' => $_FILES['file']['name'] . Label::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY')
        ]);
    }

    /**
     * Render Images
     * 
     * @param int $metaId
     * @param int $langId
     */
    public function images($metaId, $langId = 0)
    {
        $metaId = FatUtility::int($metaId);
        $metaDetail = MetaTag::getAttributesById($metaId);
        if (false == $metaDetail) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile(Afile::TYPE_OPENGRAPH_IMAGE, $langId);
        $image = $file->getFile($metaId);
        $this->set('canEdit', $this->objPrivilege->canEditMetaTags());
        $this->set('image', $image);
        $this->set('metaId', $metaId);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Remove Image
     */
    public function removeImage()
    {
        $this->objPrivilege->canEditMetaTags();
        $post = FatApp::getPostedData();
        $metaId = FatUtility::int($post['metaId']);
        $langId = FatUtility::int($post['langId']);
        if (1 > $metaId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile(Afile::TYPE_OPENGRAPH_IMAGE, $langId);
        if (!$file->removeFile($metaId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Search Form
     * 
     * @param int $metaType
     * @return Form
     */
    private function getSearchForm(int $metaType): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField(Label::getLabel('LBL_Type'), 'metaType', $metaType);
        switch ($metaType) {
            case MetaTag::META_GROUP_DEFAULT:
                return $frm;
                break;
            case MetaTag::META_GROUP_OTHER:
                $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword');
                break;
            default:
                $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword');
                $frm->addSelectBox(Label::getLabel('LBL_Tags_Associated'), 'hasTagsAssociated', AppConstant::getYesNoArr(), false, [], Label::getLabel('LBL_Does_not_Matter'));
                break;
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear'));
        return $frm;
    }

    /**
     * Get Meta Tag Form
     * 
     * @param int $metaId
     * @param int $metaType
     * @param int $recordId
     * @return Form
     */
    private function getForm($metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $frm = new Form('frmMetaTag');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'meta_id');
        $frm->addHiddenField('', 'meta_type', $metaType);
        if ($metaType == MetaTag::META_GROUP_OTHER) {
            $frm->addRequiredField(Label::getLabel('LBL_SLUG'), 'meta_slug');
        } else {
            $fld = $frm->addHiddenField(Label::getLabel('LBL_ENTITY_ID'), 'meta_record_id');
            $fld->requirements()->setRequired();
        }
        $fld = $frm->addRequiredField(Label::getLabel('LBL_IDENTIFIER'), 'meta_identifier');
        $fld->setUnique(MetaTag::DB_TBL, 'meta_identifier', 'meta_id', 'meta_id', 'meta_id');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Language Form
     * 
     * @param int $metaId
     * @param int $langId
     * @return Form
     */
    private function getLangForm(int $metaId = 0, int $langId = 0)
    {
        $frm = new Form('frmMetaTagLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'meta_id', $metaId);
        $frm->addHiddenField('', 'lang_id', $langId);
        $frm->addTextBox(Label::getLabel('LBL_Meta_Title', $langId), 'meta_title');
        $frm->addTextarea(Label::getLabel('LBL_Meta_Keywords', $langId), 'meta_keywords');
        $frm->addTextarea(Label::getLabel('LBL_Meta_Description', $langId), 'meta_description');
        $frm->addTextarea(Label::getLabel('LBL_Other_Meta_Tags', $langId), 'meta_other_meta_tags');
        $frm->addTextBox(Label::getLabel('LBL_Open_Graph_Title', $langId), 'meta_og_title');
        $fld = $frm->addTextBox(Label::getLabel('LBL_Open_Graph_Url', $langId), 'meta_og_url');
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::URL_REGEX);
        $frm->addTextarea(Label::getLabel('LBL_Open_Graph_Description', $langId), 'meta_og_description');
        $fld = $frm->addButton(Label::getLabel("LBL_Open_Graph_Image", $langId), 'open_graph_image', Label::getLabel("LBL_Upload_File", $langId), [
            'class' => 'meta-tag', 'id' => 'open_graph_image', 'meta_id' => $metaId
        ]);
        Translator::addTranslatorActions($frm, $langId, $metaId, MetaTag::DB_LANG_TBL);
        return $frm;
    }

    /**
     * Set Url Components
     * 
     * @param int $metaType
     * @param array $post
     * @return boolean
     */
    private function setUrlComponents($metaType, &$post)
    {
        $tabsArr = MetaTag::getTabsArr();
        switch ($metaType) {
            case MetaTag::META_GROUP_TEACHER:
                $userDetail = User::getByUsername($post['meta_record_id'], ['user_username']);
                if (!$userDetail) {
                    return false;
                }
                $post['meta_controller'] = $tabsArr[$metaType]['controller'];
                $post['meta_action'] = $tabsArr[$metaType]['action'];
                $post['meta_record_id'] = $userDetail['user_username'];
                break;
            case MetaTag::META_GROUP_OTHER:
                $urlComponents = explode("/", $post['meta_slug']);
                $post['meta_controller'] = FatUtility::dashed2Camel($urlComponents[0], true);
                $post['meta_action'] = $urlComponents[1] ?? 'index';
                $post['meta_record_id'] = $urlComponents[2] ?? 0;
                $post['meta_action'] = FatUtility::dashed2Camel($post['meta_action']);
                break;
            case MetaTag::META_GROUP_GRP_CLASS:
                $groupDetails = GroupClass::getClassBySlug($post['meta_record_id']);
                if (!$groupDetails) {
                    return false;
                }
                $post['meta_controller'] = $tabsArr[$metaType]['controller'];
                $post['meta_action'] = $tabsArr[$metaType]['action'];
                $post['meta_record_id'] = $groupDetails['grpcls_slug'];
                break;
            case MetaTag::META_GROUP_COURSE:
                $groupDetails = Course::getCourseBySlug($post['meta_record_id']);
                if (!$groupDetails) {
                    return false;
                }
                $post['meta_controller'] = $tabsArr[$metaType]['controller'];
                $post['meta_action'] = $tabsArr[$metaType]['action'];
                $post['meta_record_id'] = $groupDetails['course_slug'];
                break;
            default:
                $post['meta_controller'] = $tabsArr[$metaType]['controller'];
                $post['meta_action'] = $tabsArr[$metaType]['action'];
                break;
        }
        return true;
    }

    /**
     * Get Columns
     * 
     * @param int $metaType
     * @return array
     */
    private function getColumns(int $metaType): array
    {
        $columnsArr = [];
        switch ($metaType) {
            case MetaTag::META_GROUP_DEFAULT:
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'meta_identifier' => Label::getLabel('LBL_META_IDENTIFIER'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_OTHER:
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'url' => Label::getLabel('LBL_Slug'),
                    'meta_identifier' => Label::getLabel('LBL_META_IDENTIFIER'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_CMS_PAGE:
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'cpage_title' => Label::getLabel('LBL_CMS_Page'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'has_tag_associated' => Label::getLabel('LBL_Tags_Associated'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_TEACHER:
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'teacher_name' => Label::getLabel('LBL_Teacher_Name'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'has_tag_associated' => Label::getLabel('LBL_Tags_Associated'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_GRP_CLASS:
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'grpcls_title' => Label::getLabel('LBL_Group_Class'),
                    'teacher_name' => Label::getLabel('LBL_Teacher_Name'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'has_tag_associated' => Label::getLabel('LBL_Tags_Associated'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_BLOG_CATEGORY;
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'bpcategory_identifier' => Label::getLabel('LBL_Blog_Categories'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'has_tag_associated' => Label::getLabel('LBL_Tags_Associated'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_BLOG_POST;
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'post_identifier' => Label::getLabel('LBL_Post_Title'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'has_tag_associated' => Label::getLabel('LBL_Tags_Associated'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_COURSE;
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'course_title' => Label::getLabel('LBL_Course_Title'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'has_tag_associated' => Label::getLabel('LBL_Tags_Associated'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
            case MetaTag::META_GROUP_TEACH_LANGUAGE;
                $columnsArr = [
                    'listserial' => Label::getLabel('LBL_SRNO'),
                    'tlang_name' => Label::getLabel('LBL_LANGUAGE_TITLE'),
                    'meta_title' => Label::getLabel('LBL_META_TITLE'),
                    'has_tag_associated' => Label::getLabel('LBL_Tags_Associated'),
                    'action' => Label::getLabel('LBL_Action'),
                ];
                break;
        }
        return $columnsArr;
    }

    /**
     * Get Db Columns
     * 
     * @param int $metaType
     * @return array
     */
    private function getDbColumns(int $metaType): array
    {
        $dbcolumnsArr = ['meta_id', 'meta_record_id', 'meta_identifier', 'meta_title'];
        switch ($metaType) {
            case MetaTag::META_GROUP_OTHER:
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['meta_controller', 'meta_action', 'meta_record_id']);
                break;
            case MetaTag::META_GROUP_CMS_PAGE:
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['cpage_id', 'IFNULL(cpage_title,cpage_identifier) as cpage_title']);
                break;
            case MetaTag::META_GROUP_TEACHER:
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['CONCAT(user_first_name, " ", user_last_name) as teacher_name', 'user_username', 'u.user_id']);
                break;
            case MetaTag::META_GROUP_GRP_CLASS:
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['grpcls_title', 'grpcls_slug', 'grpcls_id', 'concat(u.user_first_name," ",u.user_last_name) as teacher_name']);
                break;
            case MetaTag::META_GROUP_BLOG_CATEGORY;
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['IFNULL(bpcategory_name,bpcategory_identifier) as bpcategory_identifier', 'bpcategory_id']);
                break;
            case MetaTag::META_GROUP_BLOG_POST;
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['IFNULL(post_title,post_identifier) as post_identifier ', 'post_id']);
                break;
            case MetaTag::META_GROUP_COURSE;
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['course_title', 'crs.course_id', 'course_slug']);
                break;
            case MetaTag::META_GROUP_TEACH_LANGUAGE;
                $dbcolumnsArr = array_merge($dbcolumnsArr, ['IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name', 'tlang_slug']);
                break;
        }
        return $dbcolumnsArr;
    }

    /**
     * Get Meta Record Column
     * 
     * @param int $metaType
     * @return type
     */
    private function getMetaRecordcolumn(int $metaType)
    {
        $metaRecordColumns = [
            MetaTag::META_GROUP_DEFAULT => 'meta_record_id',
            MetaTag::META_GROUP_OTHER => 'meta_record_id',
            MetaTag::META_GROUP_CMS_PAGE => 'cpage_id',
            MetaTag::META_GROUP_TEACHER => 'user_username',
            MetaTag::META_GROUP_BLOG_CATEGORY => 'bpcategory_id',
            MetaTag::META_GROUP_BLOG_POST => 'post_id',
            MetaTag::META_GROUP_GRP_CLASS => 'grpcls_slug',
            MetaTag::META_GROUP_GRP_CLASS => 'grpcls_slug',
            MetaTag::META_GROUP_COURSE => 'course_slug',
            MetaTag::META_GROUP_TEACH_LANGUAGE => 'tlang_slug',
        ];
        return $metaRecordColumns[$metaType];
    }

}
