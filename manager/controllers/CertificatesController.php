<?php

/**
 * Certificates Controller
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CertificatesController extends AdminBaseController
{

    /**
     * Initialize Certificates
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCertificates();
        $this->set("includeEditor", true);
    }

    /**
     * Render listing page
     */
    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditCertificates(true));
        $this->_template->render();
    }

    /**
     * List certificates
     */
    public function search()
    {
        $srch = CertificateTemplate::getSearchObject($this->siteLangId);
        $srch->addGroupBy('certpl_code');
        $srch->addCondition('certpl_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $data = FatApp::getDb()->fetchAll($srch->getResultSet());
        
        $this->sets([
            'arrListing' => $data,
            'canEdit' => $this->objPrivilege->canEditCertificates(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Update status
     *
     * @param string $certTplCpde
     * @param int    $status
     * @return bool
     */
    public function updateStatus(string $certTplCpde, int $status)
    {
        $this->objPrivilege->canEditCertificates();

        $certTplCpde = FatUtility::convertToType($certTplCpde, FatUtility::VAR_STRING);
        $status = FatUtility::int($status);
        $status = ($status == AppConstant::YES) ? AppConstant::NO : AppConstant::YES;

        $srch = CertificateTemplate::getSearchObject($this->siteLangId);
        $srch->addCondition('certpl_code', '=', $certTplCpde);
        if (!FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $template = new CertificateTemplate();
        if (!$template->updateStatus($certTplCpde, $status)) {
            FatUtility::dieJsonError($template->getError());
        }
        
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Render Certificate Form
     *
     * @param string  $certTplCode
     * @param int     $langId
     */
    public function form(string $certTplCode, int $langId)
    {
        $this->objPrivilege->canEditCertificates();
        $certTplId = FatUtility::convertToType($certTplCode, FatUtility::VAR_STRING, '');
        $langId = FatUtility::int($langId);
        $langId = ($langId < 1) ? $this->siteLangId : $langId;

        if (Language::getAttributesById($langId, 'language_id') < 1) {
            FatUtility::dieWithError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $srch = CertificateTemplate::getSearchObject($langId);
        $srch->addCondition('certpl_code', '=', $certTplCode);
        if (!$data = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieWithError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data['certpl_lang_id'] = $langId;

        /* get template form */
        $frm = $this->getForm($langId, $certTplCode);
        $frm->fill($data);

        /* get certficate image form */
        $mediaFrm = $this->getMediaForm($langId);
        $mediaFrm->fill(['certpl_id' => $certTplId]);

        /* get image dimensions */
        $dimensions = (new Afile(Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE))->getImageSizes();

        $this->sets([
            'frm' => $frm,
            'mediaFrm' => $mediaFrm,
            'data' => $data,
            'content' => json_decode($data['certpl_body'], true),
            'formLayout' => Language::getLayoutDirection($data['certpl_lang_id']),
            'dimensions' => $dimensions['LARGE'],
            'imageExts' => implode(', ', Afile::getAllowedExts(Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE)),
            'layoutDir' => Language::getAttributesById($langId, 'language_direction')
        ]);
        
        $this->_template->render();
    }

    /**
     * Get Default Certificate Content
     */
    public function getDefaultContent()
    {
        $post = FatApp::getPostedData();
        if (empty($post['certpl_code'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $certData = [];
        switch ($post['certpl_code']) {
            case 'evaluation_certificate':
                $certData = json_decode(FatApp::getConfig('CONF_EVALUATION_CERTIFICATE_DEFAULT_CONTENT'), true);
                break;
            case 'course_evaluation_certificate':
                $certData = json_decode(FatApp::getConfig('CONF_COURSE_EVALUATION_CERTIFICATE_DEFAULT_CONTENT'), true);
                break;
            default:
                $certData = json_decode(FatApp::getConfig('CONF_COURSE_CERTIFICATE_DEFAULT_CONTENT'), true);
                break;
        }
        $certData['trainer'] = str_replace('{teacher-name}', '<b>{teacher-name}</b>', $certData['trainer']);
        $certData['certificate_number'] = str_replace('{certificate-number}', '<b>{certificate-number}</b>', $certData['certificate_number']);
        return FatUtility::dieJsonSuccess([
            'data' => $certData,
            'msg' => '',
        ]);
    }

    /**
     * Setup Certificate
     */
    public function setup()
    {
        $this->objPrivilege->canEditCertificates();
        $post = FatApp::getPostedData();
        $post['certpl_body'] = json_encode([
            'heading' => $post['heading'],
            'content_part_1' => $post['content_part_1'],
            'learner' => $post['learner'],
            'content_part_2' => $post['content_part_2'],
            'trainer' => $post['trainer'],
            'certificate_number' => $post['certificate_number'],
        ]);
        $frm = $this->getForm($post['certpl_lang_id'], $post['certpl_code']);
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $template = new CertificateTemplate($post['certpl_id']);
        if (!$template->setup($post)) {
            FatUtility::dieJsonError($template->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(CertificateTemplate::DB_TBL, $post['certpl_code'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * Setup Certificate Media
     */
    public function setupMedia()
    {
        $this->objPrivilege->canEditCertificates();
        $frm = $this->getMediaForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        
        if (!$certType = CertificateTemplate::getAttributesById($post['certpl_id'], 'certpl_type')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $file = new Afile(Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE, 0);
        if (!$file->saveFile($_FILES['certpl_image'], $certType, true)) {
            FatUtility::dieJsonError($file->getError());
        }

        FatUtility::dieJsonSuccess([
            'imgUrl' => MyUtility::makeUrl('image', 'show', [
                    Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE, $certType, Afile::SIZE_LARGE
                ]) . '?time=' . time(),
            'msg' => Label::getLabel('MSG_FILES_UPLOADED_SUCCESSFULLY')
        ]);
    }

    /**
     * Generate Preview
     *
     * @param int $id
     */
    public function generate(int $id)
    {
        $template = CertificateTemplate::getAttributesById($id, ['certpl_code', 'certpl_lang_id']);
        if (empty($template)) {
            FatUtility::dieWithError(Label::getLabel('LBL_CERTIFICATE_TEMPLATE_NOT_FOUND'));
        }
        $langId = $template['certpl_lang_id'];
        $cert = new Certificate(0, $template['certpl_code'], 0, $langId);
        $content = $this->getContent($id, $langId);

        if (!$cert->generatePreview($content)) {
            FatUtility::dieWithError($cert->getError());
        }
        FatUtility::dieWithError(Label::getLabel('LBL_UNABLE_TO_GENERATE_CERTIFICATE'));
    }

    /**
     * Get Html Content
     *
     * @param int $id
     * @param int $langId
     * @return string
     */
    private function getContent(int $id, int $langId)
    {
        $certificateType = CertificateTemplate::getAttributesById($id, 'certpl_type');
        /* get background and logo images */
        $afile = new Afile(Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE, 0);
        $backgroundImg = $afile->getFile($certificateType, false);
        if (!isset($backgroundImg['file_path']) || !file_exists(CONF_UPLOADS_PATH . $backgroundImg['file_path'])) {
            $backgroundImg = CONF_INSTALLATION_PATH . 'public/images/noimage.jpg';
        } else {
            $backgroundImg = CONF_UPLOADS_PATH . $backgroundImg['file_path'];
        }
        $this->set('backgroundImg', $backgroundImg);

        $afile = new Afile(Afile::TYPE_CERTIFICATE_LOGO, $langId);
        $logoImg = $afile->getFile(0, false);
        if (!isset($logoImg['file_path']) || !file_exists(CONF_UPLOADS_PATH . $logoImg['file_path'])) {
            $logoImg = CONF_INSTALLATION_PATH . 'public/images/noimage.jpg';
        } else {
            $logoImg = CONF_UPLOADS_PATH . $logoImg['file_path'];
        }
        $this->set('logoImg', $logoImg);

        $this->set('layoutDir', Language::getAttributesById($langId, 'language_direction'));
        $content = $this->_template->render(false, false, 'certificates/generate.php', true);
        return $content;
    }



    public function setupLangData()
    {
        $post = FatApp::getPostedData();
        $srch = new SearchBase(CertificateTemplate::DB_TBL);
        $srch->addCondition('certpl_code', '=', $post['template_code']);
        $srch->addCondition('certpl_lang_id', 'IN', [$post['lang_id'], FatApp::getConfig('CONF_DEFAULT_LANG')]);
        $srch->addMultipleFields(['certpl_code', 'certpl_name', 'certpl_body', 'certpl_vars', 'certpl_lang_id']);
        $template = FatApp::getDb()->fetchAll($srch->getResultSet(), 'certpl_lang_id');
        if (!isset($template[$post['lang_id']])) {
            $data = [
                'certpl_lang_id' => $post['lang_id'],
                'certpl_created' => date('Y-m-d H:i:s'),
                'certpl_updated' => date('Y-m-d H:i:s'),
                'certpl_status' => AppConstant::ACTIVE
            ] + $template[FatApp::getConfig('CONF_DEFAULT_LANG')];
            $template = new CertificateTemplate();
            if (!$template->setup($data)) {
                FatUtility::dieJsonSuccess(Label::getLabel('LBL_DATA_NOT_FOUND_FOR_THIS_LANGUAGE'));
            }
        }
        FatUtility::dieJsonSuccess('');
    }


    /**
     * Get Form
     *
     * @param int $langId
     * @param string $recordId
     * @return Form
     */
    private function getForm(int $langId = 0, string $recordId = ''): Form
    {
        $frm = new Form('frmCertificate');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'certpl_code')->requirements()->setRequired();
        $fld = $frm->addHiddenField('', 'certpl_id');
        $fld->requirements()->setIntPositive();
        $fld = $frm->addHiddenField('', 'catelang_id');
        $fld->requirements()->setIntPositive();

        $fld = $frm->addSelectBox(
            Label::getLabel('LBL_LANGUAGE', $langId),
            'certpl_lang_id',
            Language::getAllNames(),
            '',
            [],
            ''
        );
        $fld->requirements()->setRequired();

        $frm->addTextBox(Label::getLabel('LBL_NAME', $langId), 'certpl_name')->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBL_Body', $langId), 'certpl_body');
        $fld->requirements()->setRequired(true);
        $frm->addHtml(
            Label::getLabel('LBL_Replacement_Caption', $langId),
            'replacement_caption',
            Label::getLabel('LBL_CERTIFICATE_REPLACEMENT_VARS', $langId)
        );
        $frm->addHtml(Label::getLabel('LBL_Replacement_Vars', $langId), 'certpl_vars', '');

        $frm->addSelectBox(Label::getLabel('LBL_STATUS', $langId), 'certpl_status', AppConstant::getActiveArr(null, $langId), '', [], '')
        ->requirements()
        ->setRequired();
        Translator::addTranslatorActions($frm, $langId, $recordId, CertificateTemplate::DB_TBL);
        $fld_submit = $frm->addSubmitButton('', 'btn_save', Label::getLabel('LBL_SAVE_CHANGES', $langId));
        $fld_button = $frm->addButton('', 'btn_preview', Label::getLabel('LBL_Save_&_Preview', $langId));
        $fld_reset = $frm->addButton('', 'btn_reset', Label::getLabel('LBL_RESET_TO_DEFAULT', $langId));
        $fld_submit->attachField($fld_button);
        $fld_submit->attachField($fld_reset);
        return $frm;
    }

    /**
     * Get Media Form
     *
     * @return Form
     */
    private function getMediaForm(int $langId = 0): Form
    {
        $frm = new Form('frmMedia');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'certpl_id');
        $frm->addFileUpload(Label::getLabel('LBL_BACKGROUND_IMAGE', $langId), 'certpl_image');
        return $frm;
    }
}
