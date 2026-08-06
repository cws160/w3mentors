<?php

class ForumTagRequestsController extends AdminBaseController
{

    /**
     * Initialize Forum Tag Requests
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $this->sets([
            'frmSearch' => $this->getSearchForm(),
            'canEdit' => $this->objPrivilege->canEditDiscussionForum(true)
        ]);
        $this->_template->render();
    }

    /**
     * Search & List
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        if (!$post = $searchForm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError($searchForm->getValidationErrors());
        }
        $srch = new ForumTagRequestSearch();
        $srch->joinWithUserTable();
        $srch->addMultipleFields([
            'ftagreq_id', 'ftagreq_language_id', 'ftagreq_name',
            'ftagreq_status', 'user_first_name', 'user_last_name'
        ]);
        $srch->addOrder('ftagreq_status', 'ASC');
        $srch->addOrder('ftagreq_id', 'DESC');
        if (!empty($post['keyword'])) {
            $srch->addCondition('ftagreq_name', 'like', '%' . trim($post['keyword']) . '%');
        }
        if ($post['lang_id'] != '') {
            $srch->addCondition('ftagreq_language_id', '=', $post['lang_id']);
        }
        if ($post['req_status'] != '') {
            $srch->addCondition('ftagreq_status', '=', $post['req_status']);
        }
        if (1 > $post['pageno']) {
            $post['pageno'] = 1;
        }
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $this->sets([
            "arrListing" => FatApp::getDb()->fetchAll($srch->getResultSet()),
            "languages" => Language::getAllNames(),
            "statusArr" => ForumTagRequest::getStatusArray(),
            "post" => $post,
            "recordCount" => $srch->recordCount(),
            "canEdit" => $this->objPrivilege->canEditDiscussionForum(true),
        ]);
        $this->_template->render(false, false);
    }

    public function statusChangeForm($id)
    {
        $this->objPrivilege->canEditDiscussionForum();
        $id = FatUtility::int($id);
        if (1 > $id) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $tagReq = ForumTagRequest::getAttributesById($id);
        if (false == $tagReq) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getStatusChangeForm();
        $frm->fill($tagReq);
        $this->sets([
            'frm' => $frm,
        ]);
        $this->_template->render(false, false);
    }

    public function changeStatus()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $frm = $this->getStatusChangeForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError($frm->getValidationErrors());
        }
        if (!array_key_exists($post['ftagreq_status'], ForumTagRequest::getAllowedStatusArray())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $attrs = [
            'ftagreq_name',
            'ftagreq_id',
            'ftagreq_user_id',
            'ftagreq_language_id',
            'ftagreq_status'
        ];
        $req = new ForumTagRequest($post['ftagreq_user_id'], $post['ftagreq_language_id'], $post['ftagreq_id']);
        if (!$req->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $tagReq = $req->getFlds();
        if (ForumTagRequest::STATUS_PENDING !== $tagReq['ftagreq_status']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $tagName = ForumTag::sanitizeName($tagReq['ftagreq_name']);
        $tag = ForumTag::getTagByName($tagName, $tagReq['ftagreq_language_id'], -1);
        if (!empty($tag) && $post['ftagreq_status'] == ForumTagRequest::STATUS_APPROVED) {
            FatUtility::dieJsonError(Label::getLabel('ERR_tag_already_available'));
        }
        $requestData['ftagreq_status'] = $post['ftagreq_status'];
        $req->assignValues($requestData);
        $db = FatAPp::getDb();
        $db->startTransaction();
        if (!$req->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($tag->getError());
        }
        $statuses = ForumTagRequest::getAllowedStatusArray();
        $data['user_id'] = $tagReq['ftagreq_user_id'];
        $data['tag_name'] = $tagReq['ftagreq_name'];
        $data['request_status'] = $statuses[$post['ftagreq_status']];
        if (ForumTagRequest::STATUS_REJECTED == $post['ftagreq_status']) {
            $db->commitTransaction();
            $req->sendStatusUpdateNotifications($data);
            FatUtility::dieJsonSuccess(Label::getLabel('MSG_request_rejected'));
        }
        $tag = new ForumTag();
        $vals['ftag_user_id'] = $tagReq['ftagreq_user_id'];
        $vals['ftag_language_id'] = $tagReq['ftagreq_language_id'];
        $vals['ftag_name'] = ForumTag::sanitizeName($tagReq['ftagreq_name']);
        $tag->assignValues($vals);
        if (!$tag->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($tag->getError());
        }
        $db->commitTransaction();
        $req->sendStatusUpdateNotifications($data);
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_Request_approved_and_tag_added_successfully'));
    }

    private function getStatusChangeForm()
    {
        $frm = new Form('frmftagReqStatus');
        $frm = CommonHelper::setFormProperties($frm);
        $statusArr = ForumTagRequest::getAllowedStatusArray();
        $frm->addSelectBox(Label::getLabel('LBL_Request_status'), 'ftagreq_status', $statusArr, '', [], Label::getLabel('LBL_SELECT'));
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Update'));
        $frm->addHiddenField('', 'ftagreq_id', 0)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'ftagreq_user_id', 0)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'ftagreq_language_id', 0)->requirements()->setIntPositive();
        return $frm;
    }

    /**
     * Get Search Form
     *
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '');
        $languages = Language::getAllNames();
        $frm->addSelectBox(Label::getLabel('LBL_FORUM_LANGUAGE'), 'lang_id', $languages, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_status'), 'req_status', ForumTagRequest::getStatusArray(), '', [], Label::getLabel('LBL_SELECT'));
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fldCancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fld_submit->attachField($fldCancel);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        return $frm;
    }

}
