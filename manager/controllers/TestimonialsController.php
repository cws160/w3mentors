<?php

/**
 * Testimonials Controller is used for Testimonials handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class TestimonialsController extends AdminBaseController
{

    /**
     * Initialize Testimonials
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewTestimonial();
    }

    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditTestimonial(true));
        $this->_template->render();
    }

    /**
     * Search & List Testimonials
     */
    public function search()
    {
        $post = FatApp::getPostedData();
        $srch = Testimonial::getSearchObject($this->siteLangId, false);
        $srch->addMultipleFields(['t.*', 't_l.testimonial_text']);
        $srch->addOrder('testimonial_active', 'desc');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $canEdit = $this->objPrivilege->canEditTestimonial(true);
        $this->set("canEdit", $canEdit);
        $this->set("arr_listing", $records);
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Render Testimonial Form
     * 
     * @param int $testimonialId
     */
    public function form($testimonialId)
    {
        $this->objPrivilege->canEditTestimonial();
        $testimonialId = FatUtility::int($testimonialId);
        $frm = $this->getForm($testimonialId);
        if (0 < $testimonialId) {
            $data = Testimonial::getAttributesById($testimonialId, [
                'testimonial_id', 'testimonial_identifier',
                'testimonial_active', 'testimonial_user_name'
            ]);
            if ($data === false) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('testimonial_id', $testimonialId);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Testimonials 
     */
    public function setup()
    {
        $this->objPrivilege->canEditTestimonial();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $testimonialId = $post['testimonial_id'];
        unset($post['testimonial_id']);
        if ($testimonialId == 0) {
            $post['testimonial_added_on'] = date('Y-m-d H:i:s');
        }
        $record = new Testimonial($testimonialId);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        $newTabLangId = 0;
        if ($testimonialId > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!Testimonial::getAttributesByLangId($langId, $testimonialId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $testimonialId = $record->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }
        $data = [
            'langId' => $newTabLangId,
            'testimonialId' => $testimonialId,
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')
        ];
        if ($newTabLangId == 0) {
            $data['openMediaForm'] = true;
        }

        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Testimonial Language Form
     * 
     * @param int $testimonialId
     * @param int $lang_id
     */
    public function langForm($testimonialId = 0, $lang_id = 0)
    {
        $testimonialId = FatUtility::int($testimonialId);
        $lang_id = FatUtility::int($lang_id);
        if ($testimonialId == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($testimonialId, $lang_id);
        $langData = Testimonial::getAttributesByLangId($lang_id, $testimonialId);
        if ($langData) {
            $langFrm->fill($langData);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('testimonialId', $testimonialId);
        $this->set('lang_id', $lang_id);
        $this->set('langFrm', $langFrm);
        $this->set('formLayout', Language::getLayoutDirection($lang_id));
        $this->_template->render(false, false);
    }

    /**
     * Setup Testimonial Language Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditTestimonial();
        $post = FatApp::getPostedData();
        $testimonialId = $post['testimonial_id'];
        $lang_id = $post['lang_id'];
        if ($testimonialId == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($testimonialId, $lang_id);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($post['testimonial_id']);
        unset($post['lang_id']);
        $data = [
            'testimoniallang_lang_id' => $lang_id,
            'testimoniallang_testimonial_id' => $testimonialId,
            'testimonial_text' => $post['testimonial_text']
        ];
        $obj = new Testimonial($testimonialId);
        if (!$obj->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($obj->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = Testimonial::getAttributesByLangId($langId, $testimonialId)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'testimonialId' => $testimonialId,
            'langId' => $newTabLangId
        ];
        if ($newTabLangId == 0) {
            $data['openMediaForm'] = true;
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(Testimonial::DB_TBL_LANG, $testimonialId, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditTestimonial();
        $testimonialId = FatApp::getPostedData('testimonialId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (0 >= $testimonialId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = Testimonial::getAttributesById($testimonialId, ['testimonial_id', 'testimonial_active']);
        if ($data == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $obj = new Testimonial($testimonialId);
        if (!$obj->changeStatus($status)) {
            FatUtility::dieJsonError($obj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditTestimonial();
        $testimonial_id = FatApp::getPostedData('testimonialId', FatUtility::VAR_INT, 0);
        if ($testimonial_id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$identifier = Testimonial::getAttributesById($testimonial_id, 'testimonial_identifier')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $testimonialObj = new Testimonial($testimonial_id);
        $testimonialObj->assignValues([Testimonial::tblFld('deleted') => 1, 'testimonial_identifier' => $identifier . '-' . $testimonial_id]);
        if (!$testimonialObj->save()) {
            FatUtility::dieJsonError($testimonialObj->getError());
        }
        $file = new Afile(Afile::TYPE_TESTIMONIAL_IMAGE);
        $file->removeFile($testimonial_id, true);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Display Testimonials Images
     * 
     * @param int $testimonialId
     */
    public function media($testimonialId = 0)
    {
        $this->objPrivilege->canEditTestimonial();
        $testimonialId = FatUtility::int($testimonialId);
        $testimonialMediaFrm = $this->getMediaForm($testimonialId);
        $file = new Afile(Afile::TYPE_TESTIMONIAL_IMAGE);
        $testimonialImg = $file->getFile($testimonialId);
        $this->set('languages', Language::getAllNames());
        $this->set('testimonialId', $testimonialId);
        $this->set('testimonialMediaFrm', $testimonialMediaFrm);
        $this->set('testimonialImg', $testimonialImg);
        $this->_template->render(false, false);
    }

    /**
     * Get Media Form
     * 
     * @param type $testimonialId
     * @return Form
     */
    public function getMediaForm($testimonialId): Form
    {
        $frm = new Form('frmTestimonialMedia');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addButton(Label::getLabel('Lbl_Image'), 'testimonial_image', Label::getLabel('LBL_Upload_Image'), [
            'class' => 'uploadFile-Js',
            'id' => 'testimonial_image',
            'data-file_type' => Afile::TYPE_TESTIMONIAL_IMAGE,
            'data-testimonial_id' => $testimonialId
        ])->requirements()->setRequired();
        $frm->addHtml('', 'testimonial_image_display_div', '');
        return $frm;
    }

    /**
     * Upload Testimonial Media
     */
    public function uploadTestimonialMedia()
    {
        $this->objPrivilege->canEditTestimonial();
        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Invalid_Request_Or_File_not_supported'));
        }
        $testimonialId = FatApp::getPostedData('testimonialId', FatUtility::VAR_INT, 0);
        if (!$testimonialId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile(Afile::TYPE_TESTIMONIAL_IMAGE);
        if (!$file->saveFile($_FILES['file'], $testimonialId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        $data = [
            'testimonialId' => $testimonialId,
            'file' => $_FILES['file']['name'],
            'msg' => Label::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Remove Testimonial Image
     * 
     * @param int $testimonialId
     * @param int $lang_id
     */
    public function removeTestimonialImage($testimonialId = 0, $lang_id = 0)
    {
        $testimonialId = FatUtility::int($testimonialId);
        $lang_id = FatUtility::int($lang_id);
        if (!$testimonialId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile(Afile::TYPE_TESTIMONIAL_IMAGE);
        if (!$file->removeFile($testimonialId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_Deleted_Successfully'));
    }

    /**
     * Get Testimonial Form
     * 
     * @param int $testimonialId
     * @return Form
     */
    private function getForm($testimonialId = 0): Form
    {
        $frm = new Form('frmTestimonial');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'testimonial_id', FatUtility::int($testimonialId));
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Testimonial_Identifier'), 'testimonial_identifier');
        $fld->setUnique(Testimonial::DB_TBL, 'testimonial_identifier', 'testimonial_id', 'testimonial_id', 'testimonial_id');
        $frm->addRequiredField(Label::getLabel('LBL_Testimonial_User_Name'), 'testimonial_user_name');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'testimonial_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Language Form
     * 
     * @param int $testimonialId
     * @param int $lang_id
     * @return Form
     */
    private function getLangForm($testimonialId = 0, $lang_id = 0): Form
    {
        $frm = new Form('frmTestimonialLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'lang_id', $lang_id);
        $frm->addHiddenField('', 'testimonial_id', $testimonialId);
        $fld = $frm->addTextarea(Label::getLabel('LBL_Testimonial_Text', $lang_id), 'testimonial_text');
        $fld->requirements()->setLength(10, 300);
        $fld->requirements()->setRequired();
        Translator::addTranslatorActions($frm, $lang_id, $testimonialId, Testimonial::DB_TBL_LANG);
        return $frm;
    }
}
