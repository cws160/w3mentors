<?php

/**
 * ForumQuestions Controller is used for add/edit Forum Tags
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumQuestionsController extends AdminBaseController
{

    /**
     * Initialize ForumQuestions
     * @param string $action
     */
    public function __construct(string $action)
    {
        die(__FILE__ . 'Note in use');
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
        $status = FatApp::getPostedData('fque_status', FatUtility::VAR_INT, -1);
        $pageNo = FatApp::getPostedData('pageno', FatUtility::VAR_INT, 1);
        $keyword = FatApp::getPostedData('keyword', null, '');
        $srch = new ForumQuestionSearch();
        $srch->applyPrimaryConditions();
        if (-1 < $status) {
            $srch->addStatusCondition((array) $status);
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
        if (0 < count($srchConds)) {
            $srch->applySearchConditions($srchConds);
        }
        $srch->addOrderBy(['fque_status' => 'ASC']);
        $post['pageno'] = (0 < $pageNo ? $pageNo : 1);
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
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
        $srch = new ForumQuestionSearch($this->siteAdmin, false);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = fque_id');
        $srch->addCondition('fque_id', '=', $quesId);
        $srch->addMultipleFields(['fque_title', 'fque_description', 'fque_status', 'fque_added_on', 'CONCAT(user_first_name, " ", user_last_name) AS username']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('records', $records);
        $this->_template->render(false, false);
    }

    /**
     * View Question Comments
     *
     */
    public function searchComments()
    {
        $queId = FatApp::getPostedData('que_id', FatUtility::VAR_INT, 0);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if (1 > $queId) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Invalid_Request'));
        }
        $srch = new ForumQuestionCommentSearch($queId);
        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $srch->setPageNumber($page);
        $srch->addMultipleFields([
            'fquecom_id', 'fquecom_comment', 'fquecom_accepted', 'fquecom_added_on', 'fquecom_user_id', 'fquecom_status', 'fquecom_deleted',
            'fque_id', 'fque_user_id', 'fque_title', 'fque_status', 'fque_deleted', 'fque_added_on',
            'COALESCE(fstat_likes, 0) as fstat_likes',
            'COALESCE(fstat_views, 0) as fstat_views',
            'COALESCE(fstat_dislikes, 0) as fstat_dislikes',
            'user_id', 'user_first_name', 'user_last_name'
        ]);
        $srch->addOrder('fquecom_id', 'DESC');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("records", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $this->_template->render(false, false, 'forum/comments.php');
    }

    /**
     * Mark As Archived
     */
    public function markArchived()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (empty($data = ForumQuestion::getAttributesById($id, 'fque_status')) || $data == ForumQuestion::FORUM_QUE_ARCHIVED) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!FatApp::getDb()->updateFromArray(ForumQuestion::DB_TBL,
                        ['fque_status' => ForumQuestion::FORUM_QUE_ARCHIVED, 'fque_updated_on' => date('Y-m-d H:i:s')],
                        ['smt' => 'fque_id = ?', 'vals' => [$id]])) {
            FatUtility::dieJsonError(FatApp::getDb()->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Marked_As_Archived'));
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
        if (!FatApp::getDb()->updateFromArray(ForumQuestion::DB_TBL,
                        ['fque_deleted' => AppConstant::YES, 'fque_updated_on' => date('Y-m-d H:i:s')],
                        ['smt' => 'fque_id = ?', 'vals' => [$id]])) {
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
        $frm = new Form('frmForumQuestSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_keyword'), 'keyword', '');
        $options = ForumQuestion::getQuestionStatusArray();
        $options = ['-1' => Label::getLabel('LBL_Does_not_matter')] + $options;
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'fque_status', $options, '', array(), '');
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'date_from', '', ['readonly' => 'readonly']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'date_till', '', ['readonly' => 'readonly']);
        $fldSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fldCancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fldSubmit->attachField($fldCancel);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        return $frm;
    }

}
