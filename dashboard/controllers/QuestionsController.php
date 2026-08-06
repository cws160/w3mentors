<?php

/**
 * Questions Controller is used for handling Question Bank
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class QuestionsController extends DashboardController
{
    /**
     * Initialize Questions
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
     * Render Questions Search Form
     */
    public function index()
    {
        $frm = QuestionSearch::getSearchForm($this->siteLangId);
        $this->set('frm', $frm);
        $this->_template->addJs('questions/page-js/common.js');
        $this->_template->render();
    }

    /**
     * Search & List Questions
     */
    public function search()
    {
        $form = QuestionSearch::getSearchForm($this->siteLangId);
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData(), ['ques_subcate_id'])) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $post['teacher_id'] = $this->siteUserId;
        $srch = new QuestionSearch($this->siteLangId, $this->siteUserId, User::SUPPORT);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $post['pagesize'] = empty($post['pagesize']) ? AppConstant::PAGESIZE : $post['pagesize'];
        $post['pageno'] = empty($post['pageno']) ? 1 : $post['pageno'];
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('ques_status', 'DESC');
        $srch->addOrder('ques_id', 'DESC');
        $data = $srch->fetchAndFormat();
        $this->sets([
            'questions' => $data,
            'postedData' => $post,
            'page' => $post['pageno'],
            'post' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Remove Question
     */
    public function remove()
    {
        $quesId = FatApp::getPostedData('quesId', FatUtility::VAR_INT, 0);
        $question = new Question($quesId, $this->siteUserId);
        if (!$question->remove()) {
            FatUtility::dieJsonError($question->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_DELETED_SUCCESSFULLY!'));
    }

    /**
     * Fetch sub categories for selected category
     *
     * @param int $catgId
     * @param int $subCatgId
     * @return html
     */
    public function getSubcategories(int $catgId, int $subCatgId = 0)
    {
        $catgId = FatUtility::int($catgId);
        $subcategories = [];
        if ($catgId > 0) {
            $subcategories = Category::getCategoriesByParentId($this->siteLangId, $catgId, Category::TYPE_QUESTION);
        }
        $this->set('subCatgId', $subCatgId);
        $this->set('subcategories', $subcategories);
        $this->_template->render(false, false);
    }

    /**
     * Render add new question form
     *
     * @param int $id
     * @param int $quizType
     */
    public function form(int $id = 0, int $quizType = 0)
    {
        $question = $options = $answers = [];
        $type = 0;
        if (0 < $id) {
            $question = Question::getById($id);
            if (empty($question) || $question['ques_user_id'] != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $quesObj = new Question($id);
            $options = $quesObj->getOptions();
            $type = $question['ques_type'];
            $answers = json_decode($question['ques_answer']);
        }
        $frm = $this->getForm($quizType);
        $frm->fill($question);
        $this->sets([
            'optionsFrm' => $this->getOptionsForm($type),
            'frm' => $frm,
            'options' => $options,
            'answers' => $answers,
            'quizType' => $quizType,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Questions
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['ques_subcate_id', 'ques_cate_id'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if ($post['ques_type'] != Question::TYPE_TEXT) {
            $optionFrm = $this->getOptionsForm($post['ques_type']);
            if (!$optionFrm->getFormDataFromArray(FatApp::getPostedData())) {
                FatUtility::dieJsonError(current($optionFrm->getValidationErrors()));
            }
            $post['answers'] = FatApp::getPostedData('ques_answer');
            $post['queopt_title'] = FatApp::getPostedData('queopt_title');
        }
        
        $question = new Question($post['ques_id'], $this->siteUserId);
        if (!$question->setup($post)) {
            FatUtility::dieJsonError($question->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * Render Option Fields
     */
    public function optionForm()
    {
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);
        $quesId = FatApp::getPostedData('quesId', FatUtility::VAR_INT, 0);
        $count = FatApp::getPostedData('count', FatUtility::VAR_INT, 0);

        /* validate count */
        $range = $this->getForm()->getField('ques_options_count')->requirements()->getRange();
        if ($count < $range[0] || $count > $range[1]) {
            $msg = Label::getLabel('VLBL_VALUE_OF_{CAPTION}_MUST_BE_BETWEEN_{MINVAL}_AND_{MAXVAL}.');
            $msg = str_replace(['{caption}', '{minval}', '{maxval}'], [Label::getLabel('LBL_OPTION_COUNT'), $range[0], $range[1]], $msg);
            FatUtility::dieJsonError($msg);
        }

        $options = $answers = [];
        if ($quesId > 0) {
            if (!$data = Question::getById($quesId)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_QUESTION_NOT_FOUND'));
            }
            if ($this->siteUserId != $data['ques_user_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
            }
            if ($count == $data['ques_options_count']) {
                $question = new Question($quesId, $this->siteUserId);
                $options = $question->getOptions();
                $answers = json_decode($data['ques_answer'], true);
            }
        }
        $this->sets([
            'frm' => $this->getOptionsForm($type),
            'type' => $type,
            'count' => $count,
            'options' => $options,
            'answers' => $answers
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Update question status
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
        $status = ($status == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        if (!$data = Question::getById($id)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUESTION_NOT_FOUND'));
        }
        if ($this->siteUserId != $data['ques_user_id']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }

        $srch = new QuizQuestionSearch(0, $this->siteUserId, User::TEACHER);
        $srch->addCondition('quiz_user_id', '=', $this->siteUserId);
        $srch->addCondition('quique_ques_id', '=', $id);
        $srch->addCondition('quiz_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->setPageSize(1);
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUESTIONS_ATTACHED_WITH_QUIZZES_CANNOT_BE_DEACTIVATED'));
        }

        $question = new Question($id);
        $question->setFldValue('ques_status', $status);
        if (!$question->save()) {
            FatUtility::dieJsonError($question->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Get Questions Form
     */
    private function getForm($quizType = 0)
    {
        $categoryList = Category::getCategoriesByParentId($this->siteLangId, 0, Category::TYPE_QUESTION, false);
        $frm = new Form('frmQuestion');
        $frm->addHiddenField('', 'ques_id')->requirements()->setInt();
        $types = Question::getTypes();
        if ($quizType == Quiz::TYPE_AUTO_GRADED) {
            unset($types[Question::TYPE_TEXT]);
        } elseif ($quizType == Quiz::TYPE_NON_GRADED) {
            unset($types[Question::TYPE_MULTIPLE]);
            unset($types[Question::TYPE_SINGLE]);
        }
        $typeFld = $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'ques_type', $types, '', [], Label::getLabel('LBL_SELECT'));
        $typeFld->requirements()->setRequired();
        $fld = $frm->addRequiredField(Label::getLabel('LBL_TITLE'), 'ques_title');
        $fld->requirements()->setLength(10, 100);
        $fld = $frm->addTextArea(Label::getLabel('LBL_DESCRIPTION'), 'ques_detail');
        $fld->requirements()->setLength(10, 1000);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_CATEGORY'), 'ques_cate_id', $categoryList, '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_SUBCATEGORY'), 'ques_subcate_id', [], '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setInt();
        $fld = $frm->addIntegerField(Label::getLabel('LBL_MARKS'), 'ques_marks');
        $fld->requirements()->setRequired();
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRange(1, 9999);
        $fld = $frm->addTextBox(Label::getLabel('LBL_HINT'), 'ques_hint');
        $fld->requirements()->setLength(10, 255);

        $countFld = $frm->addIntegerField(Label::getLabel('LBL_OPTION_COUNT'), 'ques_options_count');
        $countFld->requirements()->setRequired();
        $countFld->requirements()->setRange(1, 10);
        
        $reqCountFld = new FormFieldRequirement('ques_options_count', Label::getLabel('LBL_OPTION_COUNT'));
        $reqCountFld->setRequired(true);
        $reqCountFld->setRange(1, 10);
        $notReqCountFld = new FormFieldRequirement('ques_options_count', Label::getLabel('LBL_OPTION_COUNT'));
        $notReqCountFld->setRequired(false);

        $typeFld->requirements()->addOnChangerequirementUpdate(
            Question::TYPE_TEXT,
            'ne',
            'ques_options_count',
            $reqCountFld
        );
        $typeFld->requirements()->addOnChangerequirementUpdate(
            Question::TYPE_TEXT,
            'eq',
            'ques_options_count',
            $notReqCountFld
        );

        $frm->addButton(Label::getLabel('LBL_ADD_OPTION'), 'add_options', Label::getLabel('LBL_ADD_OPTION'));
        $frm->addButton(Label::getLabel('LBL_BACK'), 'btn_back', Label::getLabel('LBL_BACK'));
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Get Question Options Form
     *
     * @param int $type
     */
    private function getOptionsForm(int $type = 0)
    {
        $frm = new Form('frmOptions');
        $fld = $frm->addTextBox(Label::getLabel('LBL_OPTION_TITLE'), 'queopt_title[]');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 255);

        if ($type == Question::TYPE_SINGLE) {
            $options = [1 => Label::getLabel('LBL_IS_CORRECT?')];
            $fld = $frm->addRadioButtons(Label::getLabel('LBL_IS_CORRECT?'), 'ques_answer[]', $options);
            $fld->requirements()->setRequired();
            $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PLEASE_MARK_ANSWERS.'));
        } elseif ($type == Question::TYPE_MULTIPLE) {
            $fld = $frm->addCheckBox(Label::getLabel('LBL_IS_CORRECT?'), 'ques_answer[]', 1);
            $fld->requirements()->setRequired();
            $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PLEASE_MARK_ANSWERS'));
        }
        return $frm;
    }
}
