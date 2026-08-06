<?php

/**
 * Admin Users Controller is used for Admin User's handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PageLangDataController extends AdminBaseController
{

    /**
     * Initialize Admin User 
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewPageLangData();
    }

    public function index()
    {
        $this->set('includeEditor', true);
        $this->set('frmSearch', $this->getSearchForm());
        $this->set("canEdit", $this->objPrivilege->canEditPageLangData(true));
        $this->_template->render();
    }

    /**
     * Search Users
     */
    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = PageLangData::getSearchObject(false);
        $srch->addCondition('plang_lang_id', '=', FatApp::getConfig('CONF_DEFAULT_LANG'));
        $keyword = trim(FatApp::getPostedData('keyword', FatUtility::VAR_STRING, ''));
        if ($keyword) {
            $srch->addCondition('plang_key', 'LIKE', '%' . $keyword . '%')
                ->attachCondition('plang_title', 'LIKE', '%' . $keyword . '%');
        }
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $this->sets([
            'post' => $post,
            'arr_listing' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditPageLangData(true),
            'canViewAdminPermissions' => $this->objPrivilege->canViewPageLangData(true),
            'adminLoggedInId' => $this->siteAdminId,
            'activeInactiveArr' => AppConstant::getActiveArr(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Language Form
     * 
     * @param int $epage_id
     * @param int $lang_id
     */
    public function langForm($plang_id = 0, $lang_id = 0)
    {
        $this->objPrivilege->canEditPageLangData();
        $plang_id = FatUtility::int($plang_id);
        $lang_id = FatUtility::int($lang_id);
        if ($plang_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $pageKey = PageLangData::getAttributesById($plang_id, 'plang_key');
        if (empty($pageKey)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        /* get default data */
        $this->set('defaultContent', PageLangData::getDefaultDataByKey($pageKey));
        $langData = PageLangData::getAttributesByKey($pageKey, $lang_id);
        $pageLangFrm = $this->getLangForm($pageKey, $lang_id);
        if ($langData) {
            $pageLangFrm->fill($langData);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('plang_id', $plang_id);
        $this->set('plang_lang_id', $lang_id);
        $this->set('pageLangFrm', $pageLangFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->set('langData', $langData);
        $this->_template->render(false, false);
    }

    /**
     * Language Setup
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditPageLangData();
        $post = FatApp::getPostedData();
        $plang_key = FatApp::getPostedData('plang_key', FatUtility::VAR_STRING, '');
        $lang_id = FatApp::getPostedData('plang_lang_id', FatUtility::VAR_INT, FatApp::getConfig('CONF_DEFAULT_LANG'));
        if (empty($plang_key) || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langData = PageLangData::getAttributesByKey($plang_key, FatApp::getConfig('CONF_DEFAULT_LANG'));
        if (empty($langData)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_IDENTIFIER_NOT_FOUND'));
        }

        if ($lang_id != FatApp::getConfig('CONF_DEFAULT_LANG')) {
            $langData = PageLangData::getAttributesByKey($plang_key, $lang_id);
        }
        $pageId = $langData['plang_id'] ?? 0;

        $frm = $this->getLangForm($plang_key, $lang_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $data = [
            'plang_lang_id' => $lang_id,
            'plang_key' => $plang_key,
            'plang_title' => $post['plang_title'],
            'plang_summary' => $post['plang_summary'],
            'plang_summary' => $post['plang_summary'],
            'plang_warring_msg' => $post['plang_warring_msg'],
            'plang_recommendations' => $post['plang_recommendations'],
            'plang_helping_text' => $post['plang_helping_text'],
        ];
        $epage = new PageLangData($pageId);
        $epage->assignValues($data);
        if (!$epage->save()) {
            FatUtility::dieJsonError($epage->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!PageLangData::getAttributesByKey($plang_key, $langId)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(PageLangData::DB_TBL, $plang_key, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'plangId' => $epage->getMainTableRecordId(), 'langId' => $newTabLangId
        ]);
    }

    public function displayAlert()
    {
        $plangId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (1 > $plangId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $pageData = PageLangData::getAttributesById($plangId);
        if (false == $pageData) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->set('pageData', $pageData);
        $jsonData = [
            'html' => $this->_template->render(false, false, 'page-lang-data/display-alert.php', true, true)
        ];

        FatUtility::dieJsonSuccess($jsonData);
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
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Lang Form
     * 
     * @param int $plang_id
     * @param int $lang_id
     * @return Form
     */
    private function getLangForm($plang_key, $langId = 0): Form
    {
        $frm = new Form('frmPageLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'plang_lang_id', $langId);
        $frm->addRequiredField(Label::getLabel('LBL_Page_Identifier', $langId), 'plang_key', $plang_key);
        $frm->addRequiredField(Label::getLabel('LBL_Page_Title', $langId), 'plang_title');
        $frm->addTextBox(Label::getLabel('LBL_Page_Summary', $langId), 'plang_summary');
        $frm->addTextBox(Label::getLabel('LBL_Page_Warning', $langId), 'plang_warring_msg');
        $frm->addTextBox(Label::getLabel('LBL_Recommendation', $langId), 'plang_recommendations');
        $frm->addHtmlEditor(Label::getLabel('LBL_HELPING_TEXT', $langId), 'plang_helping_text');
        Translator::addTranslatorActions($frm, $langId, $plang_key, PageLangData::DB_TBL);
        return $frm;
    }

}