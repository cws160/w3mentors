<?php

/**
 * Questions Controller
 *
 * @package W3Mentors
 * @author Fatbit Team
 */

class QuestionsController extends AdminBaseController
{

    /**
     * Initialize Categories
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewQuestions();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $cateId = FatApp::getQueryStringData('ques_cate_id') ?? 0;
        $frm = $this->getSearchForm($cateId);
        $frm->fill(FatApp::getQueryStringData());
        $this->sets([
            "frmSearch" => $frm,
            "params" => FatApp::getQueryStringData(),
        ]);
        $this->_template->render();
    }

    /**
     * Search & List Questions
     */
    public function search()
    {
        $form = $this->getSearchForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData(), ['ques_subcate_id'])) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new QuestionSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['page']);
        if (isset($post['quiz_id']) && $post['quiz_id'] > 0) {
            $srch->addOrder('quique_order', 'ASC');
        } else {
            $srch->addOrder('ques_status', 'DESC');
            $srch->addOrder('ques_id', 'DESC');
        }
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $data = $srch->fetchAndFormat();
        $this->sets([
            'arrListing' => $data,
            'postedData' => $post,
            'canEdit' => $this->objPrivilege->canEditQuizCategories(true),
            'page' => $post['page'],
            'post' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Question View
     *
     * @param int $quesId
     * return html
     */
    public function view($quesId)
    {
        $srch = new QuestionSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->addCondition('ques_id', '=', $quesId);
        $srch->applyPrimaryConditions();
        $srch->joinTable(
            Category::DB_LANG_TBL,
            'LEFT OUTER JOIN',
            'ques.ques_cate_id = catg_l.catelang_cate_id',
            'catg_l'
        );
        $srch->addSearchListingFields();
        $srch->addFld('catg_l.cate_name as ques_cate_name');
        if (!$data = $srch->fetchAndFormat()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUESTION_NOT_FOUND'));
        }
        $questionData = current($data);
        $question = new Question($quesId);
        $options = $question->getOptions();
        $answerIds = json_decode($questionData['ques_answer'], true);
        $this->sets([
            'questionData' => $questionData,
            'options' => $options,
            'answers' => $answerIds,
        ]);
        $this->_template->render(false, false);
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
     * Get User Search Form
     *
     * @param int $cateId
     * @return \Form
     */
    private function getSearchForm(int $cateId = 0): Form
    {
        $frm = new Form('srchForm');
        $frm->addTextBox(Label::getLabel('LBL_TITLE'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off']);

        $categoryList = Category::getCategoriesByParentId($this->siteLangId, 0, Category::TYPE_QUESTION, false);
        $frm->addSelectBox(Label::getLabel('LBL_CATEGORY'), 'ques_cate_id', $categoryList);
        $subcategories = [];
        if ($cateId > 0) {
            $subcategories = Category::getCategoriesByParentId($this->siteLangId, $cateId, Category::TYPE_QUESTION);
        }
        $frm->addSelectBox(Label::getLabel('LBL_SUBCATEGORY'), 'ques_subcate_id', $subcategories, '', [], Label::getLabel('LBL_SELECT'));

        $frm->addTextBox(Label::getLabel('LBL_TEACHER'), 'teacher', '', ['id' => 'teacher', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'ques_type', Question::getTypes());
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'teacher_id', '');
        $frm->addHiddenField('', 'quiz_id', '');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }
}