<?php

/**
 * Course Languages Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseLanguagesController extends AdminBaseController
{

    /**
     * Initialize Course Language
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (!Course::isEnabled()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $this->objPrivilege->canViewCourseLanguage();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set('canEdit', $this->objPrivilege->canEditCourseLanguage(true));
        $this->_template->render();
    }

    /**
     * Search & List Languages
     */
    public function search()
    {
        $srch = new SearchBased(CourseLanguage::DB_TBL, 'clang');
        $srch->joinTable(CourseLanguage::DB_TBL_LANG, 'LEFT JOIN', 'clanglang.clanglang_clang_id '
                . ' = clang.clang_id AND clanglang.clanglang_lang_id = ' . $this->siteLangId, 'clanglang');
        $srch->addCondition('clang_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addOrder('clang_active', 'DESC');
        $srch->addOrder('clang_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $this->sets([
            'arrListing' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'canEdit' => $this->objPrivilege->canEditCourseLanguage(true)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Add/Edit Language Form
     * 
     * @param int $cLangId
     */
    public function form($cLangId = 0)
    {
        $this->objPrivilege->canEditCourseLanguage();
        $cLangId = FatUtility::int($cLangId);
        $frm = $this->getForm();
        $frm->fill(['clang_id' => $cLangId]);
        if ($cLangId > 0) {
            $data = CourseLanguage::getAttributesById($cLangId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->sets(['languages' => Language::getAllNames(), 'cLangId' => $cLangId, 'frm' => $frm]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Lang
     */
    public function setup()
    {
        $this->objPrivilege->canEditCourseLanguage();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $cLangId = $post['clang_id'];
        if ($cLangId > 0) {
            $data = CourseLanguage::getAttributesById($cLangId, ['clang_id']);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
        }
        /* check identifier already in use */
        $srch = new SearchBase(CourseLanguage::DB_TBL);
        $srch->addCondition('clang_identifier', '=', trim($post['clang_identifier']));
        $srch->addDirectCondition('clang_deleted IS NULL');
        if ($cLangId > 0) {
            $srch->addCondition('clang_id', '!=', $cLangId);
        }
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_LANGUAGE_IDENTIFIER_NOT_AVAILABLE'));
        }
        /* check if lang attached to course and can be deactivated */
        $status = $post['clang_active'];
        if ($status == AppConstant::INACTIVE && $this->checkCoursesExistsForLang($cLangId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_ATTACHED_LANGUAGE_CANNOT_BE_DEACTIVATED.'));
        }
        unset($post['clang_id']);
        $courseLanguage = new CourseLanguage($cLangId);
        $courseLanguage->assignValues($post);
        if (!$courseLanguage->save()) {
            FatUtility::dieJsonError($courseLanguage->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'cLangId' => $courseLanguage->getMainTableRecordId()
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Course Lang Language Form
     * 
     * @param int $cLangId
     * @param int $langId
     */
    public function langForm($cLangId = 0, $langId = 0)
    {
        $this->objPrivilege->canEditCourseLanguage();
        $cLangId = FatUtility::int($cLangId);
        $langId = FatUtility::int($langId);
        $data = CourseLanguage::getAttributesById($cLangId, ['clang_id']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($langId, $cLangId);
        $languages = $langFrm->getField('clanglang_lang_id')->options;
        if (!array_key_exists($langId, $languages)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langData = CourseLanguage::getAttributesByLangId($langId, $cLangId);
        if (empty($langData)) {
            $langData = ['clanglang_lang_id' => $langId, 'clanglang_clang_id' => $cLangId];
        }
        $langFrm->fill($langData);
        $this->sets([
            'languages' => $languages,
            'lang_id' => $langId,
            'cLangId' => $cLangId,
            'langFrm' => $langFrm,
            'formLayout' => Language::getLayoutDirection($langId),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Course Lang Language Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditCourseLanguage();
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['clanglang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (empty(CourseLanguage::getAttributesById($post['clanglang_clang_id'], ['clang_id']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = [
            'clanglang_lang_id' => $post['clanglang_lang_id'],
            'clanglang_clang_id' => $post['clanglang_clang_id'],
            'clang_name' => $post['clang_name']
        ];
        $courseLanguage = new CourseLanguage($post['clanglang_clang_id']);
        if (!$courseLanguage->updateLangData($post['clanglang_lang_id'], $data)) {
            FatUtility::dieJsonError($courseLanguage->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(CourseLanguage::DB_TBL_LANG, $post['clanglang_clang_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'cLangId' => $post['clanglang_clang_id']
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditCourseLanguage();
        $cLangId = FatApp::getPostedData('cLangId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (empty(CourseLanguage::getAttributesById($cLangId, ['clang_id']))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($status == AppConstant::INACTIVE && $this->checkCoursesExistsForLang($cLangId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_ATTACHED_LANGUAGE_CANNOT_BE_DEACTIVATED.'));
        }
        $courseLanguage = new CourseLanguage($cLangId);
        if (!$courseLanguage->changeStatus($status)) {
            FatUtility::dieJsonError($courseLanguage->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditCourseLanguage();
        $cLangId = FatApp::getPostedData('cLangId', FatUtility::VAR_INT, 0);
        if (empty(CourseLanguage::getAttributesById($cLangId, 'clang_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($this->checkCoursesExistsForLang($cLangId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_ATTACHED_LANGUAGE_CANNOT_BE_DELETED.'));
        }
        $courseLanguage = new CourseLanguage($cLangId);
        $courseLanguage->setFldValue('clang_deleted', date('Y-m-d H:i:s'));
        if (!$courseLanguage->save()) {
            FatUtility::dieJsonError($courseLanguage->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Lang Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmCourseLanguage');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'clang_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setIntPositive();
        $fld = $frm->addRequiredField(Label::getLabel('LBL_COURSE_LANGUAGE_IDENTIFIER'), 'clang_identifier', '',
                ['id' => 'clang_identifier']
        );
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'clang_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Lang Language Form
     * 
     * @param int $langId
     * @return Form
     */
    private function getLangForm(int $langId = 0, int $recordId = 0): Form
    {
        $frm = new Form('frmCourseLanguageLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'clanglang_clang_id');
        $frm->addSelectBox('', 'clanglang_lang_id', Language::getAllNames(), '', [], '');
        $frm->addRequiredField(Label::getLabel('LBL_COURSE_LANGUAGE_NAME', $langId), 'clang_name');
        Translator::addTranslatorActions($frm, $langId, $recordId, CourseLanguage::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Update Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditCourseLanguage();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $courseLang = new CourseLanguage();
            if (!$courseLang->updateOrder($post['courseLanguages'])) {
                FatUtility::dieJsonError($courseLang->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
        }
    }

    public function checkCoursesExistsForLang(int $cLangId)
    {
        $srch = new CourseSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->applyPrimaryConditions();
        $srch->addCondition('course.course_clang_id', '=', $cLangId);
        $srch->addCondition('course.course_status', '=', Course::PUBLISHED);
        $srch->addFld('course.course_id');
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            return true;
        }
        return false;
    }

}
