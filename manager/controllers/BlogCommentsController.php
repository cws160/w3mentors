<?php

/**
 * Blog Comments Controller is used for Blog Post Comments handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class BlogCommentsController extends AdminBaseController
{

    /**
     * Initialize Blog Comments
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewBlogComments();
    }

    /**
     * Render Blog Comments Search Form
     */
    public function index()
    {
        $search = $this->getSearchForm();
        $search->fill(FatApp::getPostedData());
        $this->set("search", $search);
        $this->_template->render();
    }

    /**
     * Search & List Blog Comments
     */
    public function search()
    {
        $srchFrm = $this->getSearchForm();
        if (!$post = $srchFrm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
        }
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $srch = BlogComment::getSearchObject($this->siteLangId);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('bpcomment_author_name', 'like', '%' . $keyword . '%');
            $cond->attachCondition('bpcomment_author_email', 'like', '%' . $keyword . '%');
        }
        if (isset($post['bpcomment_approved']) && $post['bpcomment_approved'] != '') {
            $srch->addCondition('bpcomment_approved', '=', $post['bpcomment_approved']);
        }
        if (isset($post['bpcomment_id']) && $post['bpcomment_id'] != '') {
            $srch->addCondition('bpcomment_id', '=', $post['bpcomment_id']);
        }
        $srch->addMultipleFields([
            'bpcomment_id',
            'bpcomment_author_name',
            'bpcomment_author_email',
            'bpcomment_content',
            'bpcomment_approved',
            'bpcomment_added_on',
            'post_id',
            'ifnull(post_title, post_identifier) post_title'
        ]);
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('bpcomment_added_on', 'desc');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $records = $this->fetchAndFormat($records);
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('canEdit', $this->objPrivilege->canEditBlogComments(true));
        $this->_template->render(false, false);
    }

    /**
     * View Comment Detail 
     * 
     * @param int $commentId
     */
    public function view($commentId)
    {
        $this->objPrivilege->canEditBlogComments();
        $commentId = FatUtility::int($commentId);
        if ($commentId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm($commentId);
        $srch = BlogComment::getSearchObject($this->siteLangId);
        $srch->addCondition('bpcomment_id', '=', $commentId);
        $data = FatApp::getDb()->fetchAll($srch->getResultSet());
        $data = $this->fetchAndFormat($data, true);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
        }
        if (empty($data['post_title'])) {
            $data['post_title'] = $data['post_identifier'];
        }
        $frm->fill($data);
        $this->set('data', $data);
        $this->set('frm', $frm);
        $this->set('bpcomment_id', $commentId);
        $this->set('statusArr', BlogPost::getCommentStatuses());
        $this->_template->render(false, false);
    }

    /**
     * Update Status
     */
    public function updateStatus()
    {
        $this->objPrivilege->canEditBlogComments();
        $commentId = FatApp::getPostedData('bpcomment_id', FatUtility::VAR_INT, 0);
        if ($commentId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm($commentId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $record = new BlogComment(FatUtility::int($post['bpcomment_id']));
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_COMMENT_STATUS_UPDATED_SUCCESSFUL'));
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditBlogComments();
        $commentId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($commentId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $blogComment = new BlogComment($commentId);
        if (!$blogComment->canMarkRecordDelete($commentId)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_UNAUTHORIZED_ACCESS'));
        }
        $blogComment->assignValues([BlogComment::tblFld('deleted') => 1]);
        if (!$blogComment->save()) {
            FatUtility::dieJsonError($blogComment->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Form
     * 
     * @param int $commentId
     * @return \Form
     */
    private function getForm(int $commentId = 0): Form
    {
        $frm = new Form('frmBlogComment', ['id' => 'frmBlogComment']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'bpcomment_id', $commentId);
        $frm->addSelectBox(Label::getLabel('LBL_Comment_Status'), 'bpcomment_approved', BlogPost::getCommentStatuses(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm', ['id' => 'srchForm']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '', ['class' => 'search-input']);
        $frm->addSelectBox(Label::getLabel('LBL_Comment_Status'), 'bpcomment_approved', BlogPost::getCommentStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'bpcomment_id');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }
    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['bpcomment_added_on'] = MyDate::formatDate($row['bpcomment_added_on']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
