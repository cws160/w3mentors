<?php

/**
 * Email Template is used for Email Templates handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class EmailTemplatesController extends AdminBaseController
{

    /**
     * Initialize Email Templates
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewEmailTemplates();
        $this->set("includeEditor", true);
    }

    /**
     * Render Template Search Form
     */
    public function index()
    {
        $this->set("frmSearch", $this->getSearchForm());
        $this->set("canEdit", $this->objPrivilege->canEditEmailTemplates(true));
        $this->_template->render();
    }

    /**
     * Search & List Templates
     */
    public function search()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $srch = EmailTemplates::getSearchObject($this->siteLangId);
        if (!Course::isEnabled()) {
            $srch->addCondition('etpl_code' , 'NOT IN' , Course::getEmailTemplates());
        }
        if (!GroupClass::isEnabled()) {
            $srch->addCondition('etpl_code' , 'NOT IN' , GroupClass::getEmailTemplates());
        }
        $srch->addOrder('etpl_status', 'DESC');
        $srch->addOrder('etpl_lang_id', 'ASC');
        $srch->addGroupBy('etpl_code');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('etpl_code', 'like', '%' . $keyword . '%', 'AND');
            $cond->attachCondition('etpl_name', 'like', '%' . $keyword . '%', 'OR');
            $cond->attachCondition('etpl_subject', 'like', '%' . $keyword . '%', 'OR');
        }
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("canEdit", $this->objPrivilege->canEditEmailTemplates(true));
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('langId', $this->siteLangId);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Setup Template Language
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditEmailTemplates();
        $data = FatApp::getPostedData();
        if (!Course::isEnabled() && in_array($data['etpl_code'], Course::getEmailTemplates())) {
            FatUtility::dieWithError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        $frm = $this->getLangForm($data['etpl_code'], $data['etpl_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray($data)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $etpl = new EmailTemplates();
        $data = [
            'etpl_lang_id' => $data['etpl_lang_id'],
            'etpl_code' => $post['etpl_code'],
            'etpl_name' => $post['etpl_name'],
            'etpl_subject' => $post['etpl_subject'],
            'etpl_body' => $post['etpl_body'],
            'etpl_status' => $post['etpl_status'],
        ];
        if (!$etpl->addUpdateData($data)) {
            FatUtility::dieJsonError($etpl->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(EmailTemplates::DB_TBL, $data['etpl_code'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Render Template Language Form
     * 
     * @param string $etplCode
     * @param type $lang_id
     */
    public function langForm(string $etplCode = '', $lang_id = 0)
    {
        $this->objPrivilege->canEditEmailTemplates();
        $lang_id = FatUtility::int($lang_id);
        if ($etplCode == '' || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!Course::isEnabled() && in_array($etplCode, Course::getEmailTemplates())) {
            FatUtility::dieWithError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        $langFrm = $this->getLangForm($etplCode, $lang_id);
        $etplObj = new EmailTemplates();
        $langData = $etplObj->getEtpl($etplCode, $lang_id);
        $langData['etpl_lang_id'] = $lang_id;
        if (!isset($langData['etpl_vars']) || $langData['etpl_vars'] == '') {
            $etplData = $etplObj->getEtpl($etplCode);
            $langData['etpl_vars'] = $etplData['etpl_vars'];
            $langData['etpl_status'] = $etplData['etpl_status'];
        }
        $langFrm->fill($langData);
        $this->set('etplCode', $etplCode);
        $this->set('langId', $lang_id);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditEmailTemplates();
        $etplCode = FatApp::getPostedData('etplCode', FatUtility::VAR_STRING, '');
        $status = FatApp::getPostedData('status', FatUtility::VAR_STRING, '');
        if ($etplCode == '') {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!Course::isEnabled() && in_array($etplCode, Course::getEmailTemplates())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        $etpl = new EmailTemplates();
        $records = $etpl->getEtpl($etplCode);
        if ($records == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$etpl->activateEmailTemplate($status, $etplCode)) {
            FatUtility::dieJsonError($etpl->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
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
        $f1 = $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Template Language Form
     * 
     * @param string $etplCode
     * @return Form
     */
    private function getLangForm(string $etplCode = '', int $langId = 0): Form
    {
        $frm = new Form('frmEtplLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'etpl_code', $etplCode);
        $frm->addHiddenField('', 'etpl_status', '');
        $frm->addSelectBox(Label::getLabel('LBL_Language', $langId), 'etpl_lang_id', Language::getAllNames(), '', [], '');
        $frm->addRequiredField(Label::getLabel('LBL_Name', $langId), 'etpl_name');
        $frm->addRequiredField(Label::getLabel('LBL_Subject', $langId), 'etpl_subject');
        $fld = $frm->addHtmlEditor(Label::getLabel('LBL_Body', $langId), 'etpl_body');
        $fld->requirements()->setRequired(true);
        $frm->addHtml(Label::getLabel('LBL_Replacement_Caption', $langId), 'replacement_caption', '<span class="font-bold">' . Label::getLabel('LBL_Replacement_Vars', $langId) . '</span>');
        $frm->addHtml(Label::getLabel('LBL_Replacement_Vars', $langId), 'etpl_vars', '');
        Translator::addTranslatorActions($frm, $langId, $etplCode, EmailTemplates::DB_TBL);
        $fld_button = $frm->addButton('', 'btn_preview', Label::getLabel('LBL_Save_&_Preview', $langId));
        $fld_submit = $frm->getField('btn_submit');
        $fld_submit->attachField($fld_button);
        return $frm;
    }

    /**
     * Function to preview email content with its layouts
     *
     * @param string $etplCode
     * @param int    $langId
     * @return void
     */
    public function preview(string $etplCode, int $langId)
    {
        if (empty($etplCode) || $langId < 1) {
            FatUtility::dieWithError('LBL_Invalid_Request');
        }
        if (!Course::isEnabled() && in_array($etplCode, Course::getEmailTemplates())) {
            FatUtility::dieWithError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        $etplObj = new EmailTemplates();
        /* get email layout */
        if (!$layoutData = $etplObj->getEtpl('emails_header_footer_layout', $langId)) {
            $defaultLangId = FatApp::getConfig('CONF_SITE_LANGUAGE');
            if ($defaultLangId != $langId) {
                $layoutData = $etplObj->getEtpl('emails_header_footer_layout', $defaultLangId);
            }
        }
        if (!$layoutData) {
            FatUtility::dieWithError(Label::getLabel('LBL_MAIL_CONTENT_DOES_NOT_EXISTS_FOR_THIS_LANGUAGE'));
        }
        $dir = Language::getAttributesById($langId, 'language_direction');
        $class = ($dir == 'rtl') ? 'left' : 'right';
        if (!$emailBody = $etplObj->getEtpl($etplCode, $langId)) {
            FatUtility::dieWithError(Label::getLabel('LBL_EMAIL_CONTENT_NOT_AVAILABLE_FOR_THIS_LANGUAGE'));
        }
        $emailContent = str_replace('{email_body}', $emailBody['etpl_body'], $layoutData['etpl_body']);
        $themeData = Theme::getAttributesById(FatApp::getConfig('CONF_ACTIVE_THEME'), [
                    'theme_primary_color',
                    'theme_secondary_color',
                    'theme_secondary_inverse_color'
        ]);
        $social_media_icons = '';
        $socialLinks = SocialPlatform::getAll();
        foreach ($socialLinks as $name => $link) {
            $img = MyUtility::makeFullUrl('images',  ($name == 'x' ? 'twitter' : $name) . '.png', [], CONF_WEBROOT_FRONTEND);
            $social_media_icons .= '<a style="display:inline-block;vertical-align:top; width:35px; height:35px; margin:0 0 0 5px; border-radius:100%;" href="' . $link . '" target="_blank">'.
                '<img src="' . $img . '" style="width: 25px;height: 25px; margin:5px auto 0; display:block;" /></a>';
        }
        $vars = [
            '{website_url}' => MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL),
            '{Company_Logo}' => MyUtility::getLogo($this->siteLangId),
            '{website_name}' => FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId, FatUtility::VAR_STRING, ''),
            '{contact_us_url}' => MyUtility::makeFullUrl('contact', '', [], CONF_WEBROOT_FRONT_URL),
            '{notifcation_email}' => FatApp::getConfig('CONF_FROM_EMAIL'),
            '{social_media_icons}' => $social_media_icons,
            '{current_date}' => date('M d, Y'),
            '{current_year}' => date('Y'),
            '{primary-color}' => '#' . $themeData['theme_primary_color'],
            '{secondary-color}' => '#' . $themeData['theme_secondary_color'],
            '{secondary-inverse-color}' => '#' . $themeData['theme_secondary_inverse_color'],
            '{dir}' => $dir,
            '{stylecls}' => $class
        ];
        foreach ($vars as $key => $value) {
            $emailContent = str_replace($key, $value, $emailContent);
        }
        echo $emailContent;
        die;
    }

}
