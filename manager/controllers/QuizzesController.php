<?php

/**
 * Quizzes Controller
 *
 * @package W3Mentors
 * @author Fatbit Team
 */

class QuizzesController extends AdminBaseController
{

    /**
     * Initialize Categories
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewQuizzes();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $frm = QuizSearch::getSearchForm();
        $frm->fill(['pagesize' => FatApp::getConfig('CONF_ADMIN_PAGESIZE')]);
        $this->set("frmSearch", $frm);
        $this->_template->render();
    }

    /**
     * Search & List Quizzes
     */
    public function search()
    {
        $form = QuizSearch::getSearchForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new QuizSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('quiz_active', 'DESC');
        $srch->addOrder('quiz_id', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $data = $srch->fetchAndFormat();
        $this->sets([
            'arrListing' => $data,
            'postedData' => $post,
            'page' => $post['pageno'],
            'post' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'canViewQuestion' => $this->objPrivilege->canViewQuestions(true)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Quiz View
     *
     * @param int $quizId
     */
    public function view(int $quizId)
    {
        $srch = new QuizSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->addCondition('quiz_id', '=', $quizId);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $data = $srch->fetchAndFormat();
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUIZ_NOT_FOUND'));
        }
        $this->set('quiz', current($data));
        $this->_template->render(false, false);
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