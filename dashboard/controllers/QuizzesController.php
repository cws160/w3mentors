<?php

/**
 * This Controller is used for creating quizzes
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class QuizzesController extends DashboardController
{
    /**
     * Initialize Quizzes
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType != User::TEACHER) {
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Quizzes Search Form
     */
    public function index()
    {
        $frm = QuizSearch::getSearchForm();
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Search & List Quizzes
     */
    public function search()
    {
        $frm = QuizSearch::getSearchForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        $srch = new QuizSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applyPrimaryConditions();
        $srch->applySearchConditions($post);
        $srch->addSearchListingFields();
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('quiz_active', 'DESC');
        $srch->addOrder('quiz_id', 'DESC');
        $this->sets([
            'quizzes' => $srch->fetchAndFormat(),
            'types' => Quiz::getTypes(),
            'status' => Quiz::getStatuses(),
            'post' => $post,
            'recordCount' => $srch->recordCount()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render add new quiz layout
     *
     * @param int $id
     */
    public function form(int $id = 0)
    {
        $quiz = new Quiz($id, $this->siteUserId);
        if ($id > 0 && !$quiz->validate()) {
            Message::addErrorMessage($quiz->getError());
            FatApp::redirectUser(MyUtility::generateUrl('Quizzes'));
        }
        $this->sets([
            "quizId" => $id,
            "includeEditor" => true
        ]);
        $this->_template->addJs('questions/page-js/common.js');
        $this->_template->render();
    }

    /**
     * Render add quiz basic form
     *
     * @return html
     */
    public function basic()
    {
        $id = FatApp::getPostedData('id');
        $data = [];
        if ($id > 0) {
            if (!$data = Quiz::getById($id)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_QUIZ_NOT_FOUND'));
            }
            if ($data['quiz_user_id'] != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
            }
            $data['quiz_type_id'] = $data['quiz_type'];
        }
        $frm = $this->getForm();
        $frm->fill($data);
        $this->sets([
            'frm' => $frm,
            'quizId' => $id
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Quizzes
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (!array_key_exists($post['quiz_type'], Quiz::getTypes())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_QUIZ_TYPE'));
        }
        $quiz = new Quiz($post['quiz_id'], $this->siteUserId);
        if (!$quiz->setup($post)) {
            FatUtility::dieJsonError($quiz->getError());
        }
        FatUtility::dieJsonSuccess([
            'quizId' => $quiz->getMainTableRecordId(),
            'msg' => Label::getLabel('MSG_SETUP_SUCCESSFUL')
        ]);
    }

    /**
     * Questions listing
     */
    public function questions()
    {
        $id = FatApp::getPostedData('id');
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $quiz = new Quiz($id, $this->siteUserId);
        if (!$quiz->validate()) {
            FatUtility::dieJsonError($quiz->getError());
        }
        
        $srch = new QuizQuestionSearch($this->siteLangId, $this->siteUserId, User::TEACHER);
        $srch->addCondition('quique_quiz_id', '=', $id);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addCondition('cate.cate_status', '=', AppConstant::ACTIVE);
        $srch->addCondition('cate.cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addOrder('quique_order', 'ASC');
        $questions = $srch->fetchAndFormat();
        $this->sets([
            'questions' => $questions,
            'quizId' => $id,
            'types' => Question::getTypes(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Settings
     */
    public function setting()
    {
        $id = FatApp::getPostedData('id');
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $quiz = new Quiz($id, $this->siteUserId);
        if (!$quiz->validate()) {
            FatUtility::dieJsonError($quiz->getError());
        }

        /* check certificate available or not */
        $srch = CertificateTemplate::getSearchObject($this->siteLangId);
        $srch->addCondition('certpl_code', '=', 'evaluation_certificate');
        $srch->addCondition('certpl_status', '=', AppConstant::ACTIVE);
        $offerCertificate = true;
        if (!FatApp::getDb()->fetch($srch->getResultSet())) {
            $offerCertificate = false;
        }
        
        $frm = $this->getSettingForm($offerCertificate);
        $frm->fill(['quiz_id' => $id]);

        $data = Quiz::getAttributesById($id, [
            'quiz_duration', 'quiz_attempts', 'quiz_passmark', 'quiz_failmsg', 'quiz_passmsg',
            'quiz_validity', 'quiz_certificate'
        ]);
        $data['quiz_duration'] = ($data['quiz_duration'] > 0) ? ($data['quiz_duration']) / 60 : 0;
        $data['quiz_certificate'] = ($offerCertificate == false) ? AppConstant::NO : $data['quiz_certificate'];
        $frm->fill($data);


        $this->sets([
            'frm' => $frm,
            'quizId' => $id,
            'offerCertificate' => $offerCertificate
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup quiz settings
     */
    public function setupSettings()
    {
        $frm = $this->getSettingForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $quiz = new Quiz($post['quiz_id'], $this->siteUserId);
        if (!$quiz->validate()) {
            FatUtility::dieJsonError($quiz->getError());
        }
        
        if (!$quiz->setupSettings($post, $this->siteLangId)) {
            FatUtility::dieJsonError($quiz->getError());
        }
        Message::addMessage(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
        FatUtility::dieJsonSuccess('');
    }

    /**
     * Update quiz status
     *
     * @return json
     */
    public function updateStatus()
    {
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if ($id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $quiz = new Quiz($id, $this->siteUserId);
        if (!$quiz->updateStatus($status)) {
            FatUtility::dieJsonError($quiz->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Delete quiz
     *
     * @return json
     */
    public function delete()
    {
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $quiz = new Quiz($id, $this->siteUserId);
        if (!$quiz->remove()) {
            FatUtility::dieJsonError($quiz->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_DELETED_SUCCESSFULLY!'));
    }

    /**
     * Get quiz completion status
     *
     * @param int $id
     * @return json
     */
    public function getCompletedStatus(int $id)
    {
        $quiz = new Quiz($id, $this->siteUserId);
        $criteria  = $quiz->getCompletedStatus();
        FatUtility::dieJsonSuccess($criteria);
    }

    /**
     * Get Quizzes Form
     *
     * @return Form
     */
    private function getForm()
    {
        $frm = new Form('frmQuiz');
        $fld = $frm->addTextBox(Label::getLabel('LBL_TITLE'), 'quiz_title', '');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(10, 120);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'quiz_type_id', Quiz::getTypes());
        $fld->requirements()->setRequired();
        $frm->addHiddenField('', 'quiz_type', 0)->requirements()->setRequired();
        $frm->addHtmlEditor(Label::getLabel('LBL_INSTRUCTIONS'), 'quiz_detail', '')->requirements()->setRequired();
        $frm->addHiddenField('', 'quiz_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_&_NEXT'));
        return $frm;
    }

    /**
     * Get settings form
     *
     * @param bool $offerCertificate
     */
    private function getSettingForm(bool $offerCertificate = true)
    {
        $frm = new Form('frmSetting');
        $durationFld = $frm->addTextBox(Label::getLabel('LBL_DURATION_(IN_MINS)'), 'quiz_duration', '');
        $durationFld ->requirements()->setIntPositive();
        $durationFld->requirements()->setRange(0, 9999);
        $attempsFtd = $frm->addRequiredField(Label::getLabel('LBL_NO_OF_ATTEMPTS_ALLOWED'), 'quiz_attempts', '');
        $attempsFtd->requirements()->setInt();
        $attempsFtd->requirements()->setRange(1, 10);
        $percentFld = $frm->addRequiredField(Label::getLabel('LBL_PASS_PERCENTAGE'), 'quiz_passmark', '');
        $percentFld->requirements()->setFloat();
        $percentFld->requirements()->setRange(1, 100);
        $percentFld = $frm->addRequiredField(Label::getLabel('LBL_VALIDITY_(IN_HOURS)'), 'quiz_validity', '');
        $percentFld->requirements()->setInt();
        $percentFld->requirements()->setRange(1, 9999);

        if ($offerCertificate == false) {
            $frm->addHiddenField('', 'quiz_certificate', AppConstant::NO);
        } else {
            $fld = $frm->addRadioButtons(
                Label::getLabel('LBL_OFFER_CERTIFICATE'),
                'quiz_certificate',
                AppConstant::getYesNoArr(),
                AppConstant::NO
            );
            $fld->requirements()->setRequired();
        }

        $failFld = $frm->addTextArea(Label::getLabel('LBL_FAIL_MESSAGE'), 'quiz_failmsg', '');
        $failFld->requirements()->setRequired();
        $failFld->requirements()->setLength(10, 255);
        $passFld = $frm->addTextArea(Label::getLabel('LBL_PASS_MESSAGE'), 'quiz_passmsg', '');
        $passFld->requirements()->setRequired();
        $passFld->requirements()->setLength(10, 255);
        $frm->addHiddenField('', 'quiz_id');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    public function frame(int $id)
    {
        $srch = new SearchBase(Quiz::DB_TBL);
        $srch->addCondition('quiz_id', '=', $id);
        $srch->addFld('quiz_detail');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('data', $data['quiz_detail']);
        $this->_template->render(false, false, '_partial/frame.php');
    }
}
