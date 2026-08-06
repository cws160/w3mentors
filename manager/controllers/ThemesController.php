<?php

class ThemesController extends AdminBaseController
{

    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewThemeManagement();
    }

    /**
     * Listing page
     *
     * @return void
     */
    public function index()
    {
        /* get search form */
        $search = $this->getSearchForm();
        $this->set("search", $search);
        $this->_template->addJs('js/jscolor.min.js');
        $this->_template->render();
    }

    /**
     * Function to get themes lising data
     *
     * @return void
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        /* fetc themes list */
        $srch = new SearchBase(Theme::DB_TBL, 'th');
        if (!empty(trim($post['keyword']))) {
            $srch->addCondition('theme_title', 'like', '%' . trim($post['keyword']) . '%');
        }
        $srch->addOrder('theme_created', 'desc');
        $srch->addOrder('theme_id', 'desc');
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $srch->setPageNumber($page);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $srch->setPageSize($pagesize);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('canEdit', $this->objPrivilege->canEditThemeManagement(true));
        $this->_template->render(false, false);
    }

    /**
     * Function to activate selected theme
     *
     * @return bool
     */
    public function activate($stopPreview = 0)
    {
        $this->objPrivilege->canEditThemeManagement();
        $this->objPrivilege->canEditThemeManagement(true);
        $themeId = FatApp::getPostedData('themeId', FatUtility::VAR_INT, 0);
        /* validate theme id is valid or not */
        if (!Theme::getAttributesById($themeId, 'theme_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* update theme id in configurations */
        $configurationObj = new Configurations();
        if (!$configurationObj->update(array('CONF_ACTIVE_THEME' => $themeId))) {
            FatUtility::dieJsonError($configurationObj->getError());
        }
        if ($stopPreview == 1 && isset($_SESSION['preview_theme'])) {
            unset($_SESSION['preview_theme']);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_THEME_ACTIVATED_SUCCESSFULLY'));
    }

    /**
     * Function to clone|update selected theme
     *
     * @return bool
     */
    public function edit()
    {
        $this->objPrivilege->canEditThemeManagement();
        $this->objPrivilege->canEditThemeManagement(true);
        $themeId = FatApp::getPostedData('themeId', FatUtility::VAR_INT, 0);
        if ($themeId < 1) {
            FatUtility::dieWithError(Label::getLabel('LBL_Invalid_Request'));
        }
        $fields = [];
        if (FatApp::getPostedData('action', FatUtility::VAR_STRING, 'update') == 'clone') {
            $fields = [
                'theme_title', 'theme_primary_color', 'theme_primary_inverse_color', 'theme_secondary_color',
                'theme_secondary_inverse_color', 'theme_footer_color', 'theme_footer_inverse_color',
                'theme_gradient_primary_color', 'theme_gradient_secondary_color', 'theme_borders_style', 'theme_niche'];
        }
        /* validate theme id is valid or not */
        if (!$themeData = Theme::getAttributesById($themeId, $fields)) {
            FatUtility::dieWithError(Label::getLabel('LBL_Invalid_Request'));
        }
        $this->set('themeData', $themeData);
        /* get and fill form */
        $frm = $this->getForm();
        $frm->fill($themeData);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Function to setup theme data
     *
     * return bool
     */
    public function setup()
    {
        $this->objPrivilege->canEditThemeManagement(true);
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $themeId = FatApp::getPostedData('theme_id', FatUtility::VAR_INT, 0);
        $themeObj = new Theme($themeId);
        $themeObj->assignValues($post);
        if ($post['theme_id'] < 1) {
            $themeObj->setFldValue('theme_created', date('Y-m-d H:i:s'));
        }
        if (!$themeObj->save()) {
            FatUtility::dieJsonError($themeObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_THEME_SETUP_SUCCESSFUL'));
    }

    public function delete()
    {
        $this->objPrivilege->canEditThemeManagement();
        $themeId = FatApp::getPostedData('themeId', FatUtility::VAR_INT, 0);
        if ($themeId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* validate theme id is valid or not */
        if (!Theme::getAttributesById($themeId, 'theme_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* check if the same theme is active */
        if (FatApp::getConfig('CONF_ACTIVE_THEME') == $themeId) {
            FatUtility::dieJsonError(Label::getLabel('MSG_CANNOT_DELETE_CURRENT_ACTIVE_THEME'));
        }
        $themeObj = new Theme($themeId);
        if (!$themeObj->deleteRecord()) {
            FatUtility::dieJsonError($themeObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_THEME_DELETED_SUCCESSFULLY'));
    }

    /**
     * Function to show preview of the selected theme
     *
     * @param int $themeId
     *
     * @return void
     */
    public function preview(int $themeId)
    {
        if ($themeId < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        /* validate if theme id is valid or not */
        if (!Theme::getAttributesById($themeId, 'theme_id')) {
            FatUtility::exitWithErrorCode(404);
        }
        $_SESSION['preview_theme'] = $themeId;
        FatApp::redirectUser(MyUtility::makeUrl('', '', [], '/'));
    }

    public function stopPreview()
    {
        unset($_SESSION['preview_theme']);
        FatApp::redirectUser(MyUtility::makeUrl('', '', [], '/'));
    }

    /**
     * Get Clone/Edit form
     *
     * @return form
     */
    private function getForm()
    {
        $frm = new Form('frmTheme');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'theme_id');
        $frm->addHiddenField('', 'theme_niche');
        $frm->addRequiredField(Label::getLabel('LBL_Title'), 'theme_title');
        $frm->addRequiredField(Label::getLabel('LBL_Primary_Color'), 'theme_primary_color');
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Primary_Inverse_Color'), 'theme_primary_inverse_color');
        $frm->addRequiredField(Label::getLabel('LBL_Secondary_Color'), 'theme_secondary_color');
        $frm->addRequiredField(Label::getLabel('LBL_Secondary_Inverse_Color'), 'theme_secondary_inverse_color');
        $frm->addRequiredField(Label::getLabel('LBL_Footer_Color'), 'theme_footer_color');
        $frm->addRequiredField(Label::getLabel('LBL_Footer_Inverse_Color'), 'theme_footer_inverse_color');
        $frm->addRequiredField(Label::getLabel('LBL_Gradient_Primary_Color'), 'theme_gradient_primary_color');
        $frm->addRequiredField(Label::getLabel('LBL_Gradient_Secondary_Color'), 'theme_gradient_secondary_color');
        $frm->addSelectBox(Label::getLabel('LBL_Borders_Style'), 'theme_borders_style', AppConstant::getButtonStyles());
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Search form
     *
     * @return form
     */
    private function getSearchForm()
    {
        $frm = new Form('frmSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

}
