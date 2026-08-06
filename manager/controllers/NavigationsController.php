<?php

/**
 * Meta Tags Controller is used for Meta Tags handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class NavigationsController extends AdminBaseController
{

    /**
     * Initialize Navigation
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewNavigationManagement();
    }

    public function index()
    {
        Navigation::clearCache();
        $this->_template->render();
    }

    /**
     * Search & List Navigation
     */
    public function search()
    {
        $srch = Navigations::getSearchObject($this->siteLangId, false);
        $srch->addOrder('nav_active', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $this->set("arr_listing", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set("canEdit", $this->objPrivilege->canEditNavigationManagement(true));
        $this->_template->render(false, false);
    }

    /**
     * Render Navigation Form
     * 
     * @param int $nav_id
     */
    public function form($nav_id = 0)
    {
        $this->objPrivilege->canEditNavigationManagement();
        $nav_id = FatUtility::int($nav_id);
        $frm = $this->getForm($nav_id);
        if (0 < $nav_id) {
            $data = Navigations::getAttributesById($nav_id, ['nav_id', 'nav_identifier', 'nav_active', 'nav_type', 'nav_deleted']);
            if ($data === false) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('nav_id', $nav_id);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Navigation
     */
    public function setup()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $nav_id = $post['nav_id'];
        if (1 > $nav_id) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = Navigations::getAttributesById($nav_id, ['nav_id', 'nav_identifier']);
        if ($data === false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $record = new Navigations($nav_id);
        if (!$record->updateContent($post)) {
            FatUtility::dieJsonError($record->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = Navigations::getAttributesByLangId($langId, $nav_id)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'navId' => $nav_id, 'langId' => $newTabLangId
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Navigation Language Form
     * 
     * @param int $nav_id
     * @param int $lang_id
     */
    public function langForm($nav_id = 0, $lang_id = 0)
    {
        $this->objPrivilege->canEditNavigationManagement();
        $nav_id = FatUtility::int($nav_id);
        $lang_id = FatUtility::int($lang_id);
        if ($nav_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($nav_id, $lang_id);
        $langData = Navigations::getAttributesByLangId($lang_id, $nav_id);
        if ($langData) {
            $langFrm->fill($langData);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('nav_id', $nav_id);
        $this->set('nav_lang_id', $lang_id);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    /**
     * Navigation Language Setup
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $post = FatApp::getPostedData();
        $nav_id = FatApp::getPostedData('nav_id', FatUtility::VAR_INT, 0);
        $lang_id = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        if ($nav_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($nav_id, $lang_id);
        $post = $frm->getFormDataFromArray($post);
        unset($post['nav_id']);
        unset($post['lang_id']);
        $data = [
            'navlang_nav_id' => $nav_id,
            'navlang_lang_id' => $lang_id,
            'nav_name' => $post['nav_name']
        ];
        $obj = new Navigations($nav_id);
        if (!$obj->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($obj->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!Navigations::getAttributesByLangId($langId, $nav_id)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(Navigations::DB_TBL_LANG, $nav_id, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'navId' => $nav_id, 'langId' => $newTabLangId,
        ]);
    }

    /**
     * Render Navigation Pages
     * 
     * @param int $nav_id
     */
    public function pages($nav_id)
    {
        $nav_id = FatUtility::int($nav_id);
        if (!$nav_id) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            FatApp::redirectUser(MyUtility::makeUrl('navigations'));
        }
        $srch = Navigations::getLinkSearchObj($this->siteLangId);
        $srch->addMultipleFields([
            'nlink_id', 'nlink_nav_id', 'nlink_cpage_id',
            'nlink_target', 'nlink_type', 'nlink_parent_id',
            'nlink_caption', 'nlink_identifier'
        ]);
        $srch->addCondition('nav_id', '=', $nav_id);
        $srch->addOrder('nlink_order', 'asc');
        $srch->addOrder('nlink_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('nav_id', $nav_id);
        $this->set('arrListing', $arrListing);
        $this->set('canEdit', $this->objPrivilege->canEditNavigationManagement(true));
        $shortview = FatApp::getPostedData('shortview', FatUtility::VAR_INT, 0);
        $this->set('shortview', $shortview);
        if ($shortview == AppConstant::YES) {
            $this->_template->render(false, false);
        } else {
            $this->_template->render();
        }
    }

    /**
     * Render Navigation Link Form
     */
    public function navigationLinkForm()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $post = FatApp::getPostedData();
        $nav_id = FatUtility::int($post['nav_id']);
        $nlink_id = FatUtility::int($post['nlink_id']);
        if (!$nav_id) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getNavigationLinksForm();
        if (!$nlink_id) {
            $frm->fill(['nlink_nav_id' => $nav_id, 'nlink_id' => $nlink_id]);
        } else {
            $srch = Navigations::getLinkSearchObj($this->siteLangId);
            $srch->addCondition('nlink_id', '=', $nlink_id);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $nlinkRow = FatApp::getDb()->fetch($rs);
            $frm->fill($nlinkRow);
        }
        $this->set('frm', $frm);
        $this->set('nav_id', $nav_id);
        $this->set('nlink_id', $nlink_id);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Setup Navigation Link
     */
    public function setupNavigationLink()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $frm = $this->getNavigationLinksForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $nlink_nav_id = FatUtility::int($post['nlink_nav_id']);
        $nlink_id = FatUtility::int($post['nlink_id']);
        unset($post['nlink_id']);
        if (1 > $nlink_nav_id) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $srch = Navigations::getSearchObject($this->siteLangId, false);
        $srch->addCondition('nav_id', '=', $nlink_nav_id);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (!$navRow = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $post['nlink_category_id'] = FatApp::getPostedData('nlink_category_id', FatUtility::VAR_INT, 0);
        $post['nlink_cpage_id'] = FatApp::getPostedData('nlink_cpage_id', FatUtility::VAR_INT, 0);
        if ($post['nlink_type'] == NavigationLinks::NAVLINK_TYPE_CMS) {
            $post['nlink_url'] = '';
            $post['nlink_category_id'] = 0;
        }
        if ($post['nlink_type'] == NavigationLinks::NAVLINK_TYPE_EXTERNAL_PAGE) {
            $post['nlink_cpage_id'] = 0;
            $post['nlink_category_id'] = 0;
        }
        if ($post['nlink_type'] == NavigationLinks::NAVLINK_TYPE_CATEGORY_PAGE) {
            $post['nlink_url'] = '';
            $post['nlink_cpage_id'] = 0;
        }
        $navLinkObj = new NavigationLinks($nlink_id);
        $dataToSaveArr = $post;
        $navLinkObj->assignValues($dataToSaveArr);
        if (!$navLinkObj->save()) {
            FatUtility::dieJsonError($navLinkObj->getError());
        }
        $newTabLangId = 0;
        if ($nlink_id > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = Navigations::getAttributesByLangId($langId, $nlink_id)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $nlink_id = $navLinkObj->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }
        $data = [
            'langId' => $newTabLangId, 'nlinkId' => $nlink_id,
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Navigation Link Language Form
     */
    public function navigationLinkLangForm()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $post = FatApp::getPostedData();
        $nav_id = FatUtility::int($post['nav_id']);
        $nlink_id = FatUtility::int($post['nlink_id']);
        $lang_id = FatUtility::int($post['lang_id']);
        if (!$nav_id || !$lang_id || !$nlink_id) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getNavigationLinksLangForm($lang_id, $nlink_id);
        $langData = NavigationLinks::getAttributesByLangId($lang_id, $nlink_id);
        if ($langData) {
            $langData['nlink_id'] = $langData['nlinklang_nlink_id'];
            $langData['nav_id'] = $nav_id;
            $langFrm->fill($langData);
        } else {
            $langFrm->fill(['lang_id' => $lang_id, 'nav_id' => $nav_id, 'nlink_id' => $nlink_id]);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('nav_id', $nav_id);
        $this->set('nlink_id', $nlink_id);
        $this->set('nav_lang_id', $lang_id);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    /**
     * Setup Navigation Links Languages
     */
    public function setupNavigationLinksLang()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $post = FatApp::getPostedData();
        $nlink_id = FatUtility::int($post['nlink_id']);
        $lang_id = $post['lang_id'];
        if ($nlink_id == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getNavigationLinksLangForm($lang_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($post['nlink_id']);
        unset($post['lang_id']);
        $data = [
            'nlinklang_nlink_id' => $nlink_id,
            'nlinklang_lang_id' => $lang_id,
            'nlink_caption' => $post['nlink_caption'],
        ];
        $navLinkObj = new NavigationLinks($nlink_id);
        if (!$navLinkObj->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($navLinkObj->getError());
        }
        $newTabLangId = 0;
        if ($nlink_id > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = NavigationLinks::getAttributesByLangId($langId, $nlink_id)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $nlink_id = $navLinkObj->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(NavigationLinks::DB_TBL_LANG, $nlink_id, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        $data = ['langId' => $newTabLangId, 'nlinkId' => $nlink_id, 'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Delete Navigation Link
     */
    public function deleteNavigationLink()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $nlinkId = FatApp::getPostedData('nlinkId', FatUtility::VAR_INT, 0);
        if ($nlinkId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        if (!NavigationLinks::getAttributesById($nlinkId, 'nlink_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NAVIGATION_NOT_FOUND'));
        }

        $obj = new NavigationLinks($nlinkId);
        if (!$obj->deleteRecord(true)) {
            FatUtility::dieJsonError($obj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Update Navigation Link Sort Order
     */
    public function updateNlinkOrder()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $nlinkObj = new NavigationLinks();
            if (!$nlinkObj->updateOrder($post['pageList'])) {
                FatUtility::dieJsonError($nlinkObj->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_Order_Updated_Successful'));
        }
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditNavigationManagement();
        $navId = FatApp::getPostedData('navId', FatUtility::VAR_INT, 0);
        if (0 == $navId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $navigationData = Navigations::getAttributesById($navId, ['nav_active']);
        if (!$navigationData) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $status = ($navigationData['nav_active'] == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        $navObj = new Navigations($navId);
        if (!$navObj->changeStatus($status)) {
            FatUtility::dieJsonError($navObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Get Navigation Links Form
     * 
     * @return Form
     */
    private function getNavigationLinksForm(): Form
    {
        $frm = new Form('frmNavigationLink');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addRequiredField(Label::getLabel('LBL_Caption_Identifier'), 'nlink_identifier');
        $frm->addSelectBox(Label::getLabel('LBL_Type'), 'nlink_type', NavigationLinks::getLinkTypeArr(), '', [], '')->requirements()->setRequired();
        $frm->addSelectBox(Label::getLabel('LBL_Link_Target'), 'nlink_target', AppConstant::getLinkTargetsArr(), '', [], '')->requirements()->setRequired();
        $frm->addSelectBox(Label::getLabel('LBL_Login_Protected'), 'nlink_login_protected', NavigationLinks::getLinkLoginTypeArr(), '', [], '')->requirements()->setRequired();
        $frm->addSelectBox(Label::getLabel('LBL_Link_to_CMS_Page'), 'nlink_cpage_id', ContentPage::getPagesForSelectBox($this->siteLangId), '', [], Label::getLabel('LBL_SELECT'));
        $fld = $frm->addTextBox(Label::getLabel('LBL_External_Page'), 'nlink_url');
        $fld->htmlAfterField = '<br/><small>' . Label::getLabel('LBL_TO_LINK_WITH_AN_EXISTING_URL,_USE_PREFIX_{domain}_FOLLOWED_BY_THE_ORIGINAL_URL.') . ' For eg. {domain}teachers Or {domain}aboutus</small>';
        $frm->addHiddenField('', 'nlink_nav_id');
        $frm->addHiddenField('', 'nlink_id');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Navigation Links Lang Form
     * 
     * @param int $lang_id
     * @return Form
     */
    private function getNavigationLinksLangForm($langId, int $linkId = 0): Form
    {
        $frm = new Form('frmNavigationLink');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'nav_id');
        $frm->addHiddenField('', 'nlink_id');
        $frm->addHiddenField('', 'lang_id', $langId);
        $frm->addRequiredField(Label::getLabel('LBL_Caption', $langId), 'nlink_caption');
        Translator::addTranslatorActions($frm, $langId, $linkId, NavigationLinks::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Get Navigation Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $this->objPrivilege->canViewNavigationManagement();
        $frm = new Form('frmNavigation');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'nav_id', 0);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Identifier'), 'nav_identifier');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'nav_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Navigation Lang Form
     * 
     * @param int $nav_id
     * @param int $langId
     * @return Form
     */
    private function getLangForm($nav_id = 0, $langId = 0): Form
    {
        $frm = new Form('frmNavigationLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'nav_id', $nav_id);
        $frm->addHiddenField('', 'lang_id', $langId);
        $frm->addRequiredField(Label::getLabel('LBL_Title', $langId), 'nav_name');
        Translator::addTranslatorActions($frm, $langId, $nav_id, Navigations::DB_TBL_LANG);
        return $frm;
    }
}
