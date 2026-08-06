<?php

/**
 * Teach Language Controller is used for TeachLanguage handling
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeachLanguageController extends AdminBaseController
{

    /**
     * Initialize Teach Language
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewTeachLanguage();
    }

    /**
     * Render Search Form
     * @param int $parentId
     */
    public function index($parentId = 0)
    {
        $frm = $this->getSearchForm();
        $frm->fill(['parent_id' => $parentId]);
        $this->set('frm', $frm);
        $this->set('langParentId', TeachLanguage::getAttributesById($parentId, 'tlang_parent'));
        $this->set('canEdit', $this->objPrivilege->canEditTeachLanguage(true));
        $this->set("includeEditor", true);
        $this->_template->render();
    }

    /**
     * Get Search Form
     *
     * @param int $cateId
     * @return \Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm->addHiddenField('', 'parent_id');
        return $frm;
    }

    /**
     * Search & List Teach Languages
     * @param int $parentId
     */
    public function search($parentId = 0)
    {
        $data = FatApp::getPostedData();
        $srch = TeachLanguage::getSearchObject($this->siteLangId, false);
        $srch->addOrder('tlang_active', 'desc');
        $srch->addOrder('tlang_order', 'asc');
        $srch->addOrder('tlang_id', 'desc');
        $srch->addMultipleFields([
            'tlang_id', 'tlang_active', 'tlang_subcategories', 'tlang_identifier', 'IFNULL(tlang_featured, 0) tlang_featured', 'tlang_parent',
            'tlang_max_price', 'tlang_min_price', 'tlang_hourly_price', 'tlang_name'
        ]);
        if (FatApp::getPostedData('export')) {
            $srch->addCondition('tlang_parent', '=', $data['parent_id']);
            $data['level'] = (TeachLanguage::getAttributesById($data['parent_id'], 'tlang_level'))+1;
            return ['post' => $data, 'srch' => $srch];
        }
        $srch->addCondition('tlang_parent', '=', $parentId);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('arrListing', $records);
        $this->set('canEdit', $this->objPrivilege->canEditTeachLanguage(true));

        $this->set('level', TeachLanguage::getAttributesById($parentId, 'tlang_level'));
        $this->set('parentId', $parentId);
        $this->set('adminManagePrice', FatApp::getConfig('CONF_MANAGE_PRICES'));
        $this->_template->render(false, false);
    }

    /**
     * Teach Language Form
     * @param int $tLangId
     * @param int $parentId
     */
    public function form(int $tLangId = 0, $parentId = 0)
    {
        $this->objPrivilege->canEditTeachLanguage();
        $data = ['tlang_id' => $tLangId, 'tlang_parent' => $parentId];
        
        if ($tLangId > 0) {
            $data = TeachLanguage::getAttributesById($tLangId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
        }
        
        if ($parentId && !TeachLanguage::getAttributesById($parentId, 'tlang_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $frm = $this->getForm($tLangId);
        $frm->fill($data);
        $this->sets([
            'languages' => Language::getAllNames(),
            'frm' => $frm,
            'canUploadMedia' => $this->canUploadMedia($data)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Teach Language
     */
    public function setup()
    {
        $this->objPrivilege->canEditTeachLanguage();
        $post = FatApp::getPostedData();
        if (isset($post['tlang_slug'])) {
            $post['tlang_slug'] = CommonHelper::seoUrl($post['tlang_slug']);
        }
        $tLangId = $post['tlang_id'];
        $frm = $this->getForm($tLangId);
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        unset($post['tlang_id']);
        $record = new TeachLanguage($tLangId);
        if (!$record->setup($post)) {
            FatUtility::dieJsonError($record->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'tLangId' => $record->getMainTableRecordId()
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Teach Lang Language Form
     * 
     * @param int $tLangId
     * @param int $langId
     */
    public function langForm(int $tLangId, int $langId = 0)
    {
        if (empty($data = TeachLanguage::getAttributesById($tLangId, ['tlang_parent']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($langId, $tLangId);
        $languages = $langFrm->getField('tlanglang_lang_id')->options;
        if (!array_key_exists($langId, $languages)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langData = TeachLanguage::getAttributesByLangId($langId, $tLangId);
        if (empty($langData)) {
            $langData = ['tlanglang_lang_id' => $langId, 'tlanglang_tlang_id' => $tLangId];
        }
        $langFrm->fill($langData);
        $this->sets([
            'tLangId' => $tLangId,
            'languages' => $languages,
            'lang_id' => $langId,
            'langFrm' => $langFrm,
            'formLayout' => Language::getLayoutDirection($langId),
            'canUploadMedia' => $this->canUploadMedia($data)
        ]);
        $this->_template->render(false, false);
    }

    private function canUploadMedia($data, $returnBool = true)
    {
        $canUpload = ($data['tlang_parent'] < 1) ? true : false;
        if (!$returnBool && !$canUpload) {
            FatUtility::dieJsonError(Label::getLabel('LBL_REQUEST_NOT_AUTHORISED_TO_UPLOAD'));
        }
        return $canUpload;
    }

    /**
     * Setup Teach Lang Language Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditTeachLanguage();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['tlanglang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (empty(TeachLanguage::getAttributesById($post['tlanglang_tlang_id'], 'tlang_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = [
            'tlanglang_lang_id' => $post['tlanglang_lang_id'],
            'tlanglang_tlang_id' => $post['tlanglang_tlang_id'],
            'tlang_name' => $post['tlang_name'],
            'tlang_description' => $post['tlang_description']
        ];
        $teachLanguage = new TeachLanguage($post['tlanglang_tlang_id']);
        if (!$teachLanguage->updateLangData($post['tlanglang_lang_id'], $data)) {
            FatUtility::dieJsonError($teachLanguage->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(TeachLanguage::DB_TBL_LANG, $post['tlanglang_tlang_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'tLangId' => $post['tlanglang_tlang_id']
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditTeachLanguage();
        $tLangId = FatApp::getPostedData('tLangId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (!TeachLanguage::getAttributesById($tLangId, ['tlang_id', 'tlang_subcategories'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($status == AppConstant::INACTIVE && !empty(TeachLanguage::getByParentId($tLangId))) {
            FatUtility::dieJsonError(Label::getLabel("LBL_CANNOT_MARK_INACTIVE_AS_THERE_ARE_ACTIVE_SUB_LANGUAGES_ATTACHED"));
        }
        $teachLanguage = new TeachLanguage($tLangId);
        if (!$teachLanguage->updateStatus($status)) {
            FatUtility::dieJsonError($teachLanguage->getError());
        }
        if ($status == AppConstant::NO) {
            (new UserTeachLanguage())->removeTeachLang([$tLangId]);
            (new TeacherStat(0))->setTeachLangPricesBulk();
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditTeachLanguage();
        $tLangId = FatApp::getPostedData('tLangId', FatUtility::VAR_INT, 0);
        $teachLang = TeachLanguage::getAttributesById($tLangId);
        if (empty($teachLang)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($teachLang['tlang_subcategories'] > 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_LANGUAGES_ATTACHED_WITH_THE_SUB_LANGUAGES_CANNOT_BE_DELETED'));
        }
        $teachLanguage = new TeachLanguage($tLangId);
        if (!$teachLanguage->remove($tLangId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        (new UserTeachLanguage())->removeTeachLang([$tLangId]);
        (new TeacherStat(0))->setTeachLangPricesBulk();
        (new Afile(Afile::TYPE_TEACHING_LANGUAGES))->removeFile($tLangId, true);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Teach Lang Form
     * 
     * @param bool $setUnique
     * @return Form
     */
    private function getForm($tlangId = 0): Form
    {
        $frm = new Form('frmLessonPackage');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'tlang_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setIntPositive();
        $fld = $frm->addRequiredField(Label::getLabel('LBL_LANGUAGE_IDENTIFIER'), 'tlang_identifier');
        $fld = $frm->addTextBox(Label::getLabel('LBL_LANGUAGE_SLUG'), 'tlang_slug');
        $fld->requirements()->setRequired();
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(3, 100);
        $options = [0 => Label::getLabel('LBL_ROOT_LANGUAGE')] + TeachLanguage::getOptions($this->siteLangId, !empty($tlangId) ? [$tlangId] : [], false);
        $parentFld = $frm->addSelectBox(Label::getLabel('LBL_PARENT'), 'tlang_parent', $options);
        $parentFld->requirements()->setRequired();
        $frm->addCheckBox(Label::getLabel('LBL_FEATURED'), 'tlang_featured', 1, [], false, 0);
        if(empty(TeachLanguage::getAttributesById($tlangId, 'tlang_subcategories'))){
            if (FatApp::getConfig('CONF_MANAGE_PRICES')) {
                $fld = $frm->addFloatField(Label::getLabel('LBL_HOURLY_PRICE'), 'tlang_hourly_price');
                $fld->requirements()->setRequired(true);
                $fld->requirements()->setRange(1, 9999999999);
            } else {
                $fld = $frm->addFloatField(Label::getLabel('LBL_HOURLY_MIN_PRICE'), 'tlang_min_price');
                $fld->requirements()->setRequired(true);
                $fld->requirements()->setRange(1, 9999999999);
                $fld->requirements()->setCompareWith('tlang_max_price', 'lt', Label::getLabel('LBL_MAX_PRICE'));
                $fld = $frm->addFloatField(Label::getLabel('LBL_HOURLY_MAX_PRICE'), 'tlang_max_price');
                $fld->requirements()->setRequired(true);
                $fld->requirements()->setRange(1, 9999999999);
            }
        }

        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'tlang_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get TeachLang Language Form
     * @param int $langId
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $recordId = 0): Form
    {
        $frm = new Form('frmTeachLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'tlanglang_tlang_id');
        $frm->addSelectBox('', 'tlanglang_lang_id', Language::getAllNames(), '', [], '');
        $frm->addRequiredField(Label::getLabel('LBL_Teach_Language_name', $langId), 'tlang_name');
        $descFld = $frm->addHtmlEditor(Label::getLabel('LBL_LANGUAGE_DESCRIPTION', $langId), 'tlang_description');
        $descFld->requirements()->setRequired();
        Translator::addTranslatorActions($frm, $langId, $recordId, TeachLanguage::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Render Media Form
     * 
     * @param int $tLangId
     */
    public function mediaForm(int $tLangId)
    {
        if (empty($data = TeachLanguage::getAttributesById($tLangId, ['tlang_parent']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->canUploadMedia($data, false);

        $this->sets([
            "tLangId" => $tLangId,
            "canEdit" => $this->objPrivilege->canEditTeachLanguage(true),
            "mediaFrm" => $this->getMediaForm($tLangId),
            "languages" => Language::getAllNames(),
            "image" => (new Afile(Afile::TYPE_TEACHING_LANGUAGES))->getFile($tLangId),
            "teachLangExt" => implode(', ', Afile::getAllowedExts(Afile::TYPE_TEACHING_LANGUAGES)),
            "teachLangSize" => implode('x', (new Afile(Afile::TYPE_TEACHING_LANGUAGES))->getImageSizes()['LARGE']),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Media Form
     * 
     * @param int $tlang_id
     * @return Form
     */
    private function getMediaForm($tlang_id): Form
    {
        $frm = new Form('frmTeachLanguageMedia');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'tlang_id', $tlang_id);
        $frm->addFileUpload('', 'tlang_image_file');
        $frm->addButton(
            Label::getLabel('LBL_LANGUAGE_IMAGE'),
            'tlang_image',
            Label::getLabel('LBL_UPLOAD_FILE'),
            ['class' => 'tlanguageFile-Js', 'id' => 'tlang_image', 'data-tlang_id' => $tlang_id]
        );
        return $frm;
    }

    /**
     * Upload File
     * 
     * @param int $tlanguageId
     */
    public function uploadFile($tlanguageId)
    {
        $this->objPrivilege->canEditTeachLanguage();
        if (empty($data = TeachLanguage::getAttributesById($tlanguageId, ['tlang_parent']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->canUploadMedia($data, false);

        $type = FatApp::getPostedData('imageType', FatUtility::VAR_INT, Afile::TYPE_TEACHING_LANGUAGES);
        if (empty($_FILES['file']['name'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_Please_Select_A_File'));
        }
        $file = new Afile($type);
        if (!$file->saveFile($_FILES['file'], $tlanguageId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess([
            'tlang_id' => $tlanguageId,
            'msg' => Label::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY')
        ]);
    }

    /**
     * Remove File
     * 
     * @param int $tlanguageId
     * @param int $fileType
     */
    public function removeFile($tlanguageId, $fileType)
    {
        $this->objPrivilege->canEditTeachLanguage();
        $tlanguageId = FatUtility::int($tlanguageId);
        $fileType = FatUtility::int($fileType);
        if (1 > $fileType) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (empty(TeachLanguage::getAttributesById($tlanguageId, 'tlang_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile($fileType);
        if (!$file->removeFile($tlanguageId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_DELETED_SUCCESSFULLY'));
    }

    /**
     * Update Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditTeachLanguage();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $teachLangObj = new TeachLanguage();
            if (!$teachLangObj->updateOrder($post['teachingLangages'])) {
                FatUtility::dieJsonError($teachLangObj->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
        }
    }

    /**
     * Auto Complete JSON
     */
    public function autoCompleteJson()
    {
        $keyword = trim(FatApp::getPostedData('keyword', FatUtility::VAR_STRING, ''));
        if (empty($keyword)) {
            FatUtility::dieJsonSuccess(['data' => []]);
        }
        $langId = MyUtility::getSiteLangId();
        $data = TeachLanguage::getTeachLanguages($langId, false, ['keyword' => $keyword, 'pagesize' => 20]);
        if (!empty($data)) {
            $data = TeachLanguage::getNames($langId, array_keys($data), false);
        }
        FatUtility::dieJsonSuccess(['data' => $data]);
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = [];
        $parameters = FatApp::getParameters();
        if (!empty($parameters[0])) {
            $parentIds = TeachLanguage::getAttributesById($parameters[0], ['tlang_parentids']);
            $ids = array_merge([$parameters[0]], explode(',', $parentIds['tlang_parentids'] ?? ''));
            $languages = TeachLanguage::getNamesByLangIds([$this->siteLangId], $ids);
            $keys = array_keys($languages);
            $languages = array_combine(array_reverse($keys), array_reverse($languages));

            $count = count($languages);
            $nodes = [['title' => Label::getLabel('LBL_ROOT_LANGUAGES'), 'href' => MyUtility::generateUrl('TeachLanguage', 'index')]];
            $node = 1;
            foreach ($languages as $id => $lang) {
                $arr = ['title' => $lang[$this->siteLangId]];
                if ($node < $count) {
                    $arr['href'] = MyUtility::generateUrl('TeachLanguage', 'index', [$id]);
                }
                $nodes[] = $arr;
                $node++;
            }
        } else {
            $nodes = [['title' => Label::getLabel('LBL_ROOT_LANGUAGES')]];
        }
        return $nodes;
    }
}
