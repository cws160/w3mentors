<?php

/**
 * ForumQuestions Controller is used for add/edit Forum Tags
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumController extends AdminBaseController
{

    /**
     * Initialize ForumQuestions
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewDiscussionForum();
    }

    /**
     * Render Forum Question Form
     */
    public function index()
    {
        $this->set('canEdit', $this->objPrivilege->canEditDiscussionForum(true));
        $this->set('frmSearch', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Forum Question
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $pageNo = FatApp::getPostedData('pageno', FatUtility::VAR_INT, 1);
        $keyword = FatApp::getPostedData('keyword', null, '');
        $srch = new ForumQuestionSearch();
        $srch->applyPrimaryConditions();
        $srch->joinWithUsers();
        $srch->joinWithStats();
        $srch->addMultipleFields(ForumQuestionSearch::getListingFields());
        if ($post['fque_status'] != '') {
            $srch->addCondition('fque_status', '=', $post['fque_status']);
        }
        $srchConds = [];
        if (0 < strlen($keyword)) {
            $srchConds = ['keyword' => $keyword,];
        }
        if (!empty($post['date_from'])) {
            $srchConds['date_from'] = MyDate::formatToSystemTimezone($post['date_from'] . ' 00:00:00');
        }
        if (!empty($post['date_till'])) {
            $srchConds['date_till'] = MyDate::formatToSystemTimezone($post['date_till'] . ' 23:59:59');
        }
        if (!empty($post['lang_id'])) {
            $srchConds['lang_id'] = FatUtility::int($post['lang_id']);
        }
        if (0 < count($srchConds)) {
            $srch->applySearchConditions($srchConds);
        }
        $srch->addOrderBy(['fque_status' => 'ASC']);
        $post['pageno'] = (0 < $pageNo ? $pageNo : 1);
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = $srch->fetchAndFormat();
        $languages = Language::getAllNames();
        $this->sets([
            "arrListing" => $records,
            "languages" => $languages,
            "post" => $post,
            "recordCount" => $srch->recordCount(),
            "canEdit" => $this->objPrivilege->canEditDiscussionForum(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * View Question Detail
     *
     * @param int $quesId
     */
    public function view($quesId)
    {
        $srch = new ForumQuestionSearch();
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = fque_user_id');
        $srch->addCondition('fque_id', '=', $quesId);
        $srch->addMultipleFields([
            'fque_title', 'fque_description', 'fque_status',
            'fque_added_on', 'CONCAT(user_first_name, " ", user_last_name) AS username'
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = current($srch->fetchAndFormat());
        $que = new ForumQuestion($quesId);
        $queTags = $que->getTags([$quesId], 0);
        $this->set('queTags', $queTags);
        $this->set('records', $records);
        $this->set('statusArr', ForumQuestion::getQuestionStatusArray());
        $this->_template->render(false, false);
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (empty(ForumQuestion::getAttributesById($id, 'fque_id'))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!FatApp::getDb()->updateFromArray(
                        ForumQuestion::DB_TBL,
                        ['fque_deleted' => AppConstant::YES, 'fque_updated_on' => date('Y-m-d H:i:s')],
                        ['smt' => 'fque_id = ?', 'vals' => [$id]]
                )) {
            FatUtility::dieJsonError(FatApp::getDb()->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Question_Deleted_SUCCESSFULLY'));
    }

    /**
     * Get Search Form
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_keyword'), 'keyword', '');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'fque_status', ForumQuestion::getQuestionStatusArray(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_FORUM_LANGUAGE'), 'lang_id', Language::getAllNames(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'date_from', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'date_till', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $fldSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fldCancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fldSubmit->attachField($fldCancel);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        return $frm;
    }

    /**
     * View Question Comments
     *
     */
    public function Comments($id = 0)
    {
        $frm = $this->getCommentSearchForm();
        $frm->fill(['quesId' => $id]);
        $this->set('frmSearch', $frm);
        $this->_template->render();
    }

    /**
     * Comments List
     *
     */
    public function searchComment()
    {
        
        $frmSearch = $this->getCommentSearchForm();
        $post = $frmSearch->getFormDataFromArray(FatApp::getPostedData());
        $queId = FatUtility::int($post['quesId']);
        $page = FatApp::getPostedData('pageno', FatUtility::VAR_INT, 1);
        if (1 > $queId) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Invalid_Request'));
        }
        $srch = new ForumQuestionCommentSearch($queId);
        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $srch->setPageNumber($page);
        $srch->addMultipleFields([
            'COALESCE(fstat_views, 0) as fstat_views', 'COALESCE(fstat_dislikes, 0) as fstat_dislikes',
            'fquecom_id', 'fquecom_comment', 'fquecom_accepted', 'fquecom_added_on', 'fquecom_user_id',
            'fquecom_status', 'fquecom_deleted', 'fque_id', 'fque_user_id', 'fque_title', 'fque_status',
            'fque_deleted', 'fque_added_on', 'COALESCE(fstat_likes, 0) as fstat_likes', 'user_id',
            'user_first_name', 'user_last_name'
        ]);
        $srch->addOrder('fquecom_id', 'DESC');
        $records = $srch->fetchAndFormat();
        $post['pageno'] = (0 < $page ? $page : 1);
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $post['pageCount'] = $srch->pages();
        $this->set("records", $records);
        $this->set('post', $post);
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false, 'forum/comment-search.php');
    }

    /**
     * Get Comment Search Form
     * @return Form
     */
    private function getCommentSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'quesId');
        return $frm;
    }

}
