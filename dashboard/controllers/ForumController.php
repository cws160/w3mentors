<?php

class ForumController extends AccountController
{

    /**
     * Initialize Forum Questions
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $srchFrmObj = $this->getSearchForm();
        $this->_template->set('srchFrmObj', $srchFrmObj);
        $this->_template->render();
    }

    public function search()
    {
        $srch = new ForumQuestionSearch();
        $srch->applyPrimaryConditions();
        $status = FatApp::getPostedData('fque_status', FatUtility::VAR_INT, -1);
        $pageNo = FatApp::getPostedData('pageno', FatUtility::VAR_INT, 1);
        $keyword = FatApp::getPostedData('keyword', null, '');
        if (-1 < $status) {
            $srch->addStatusCondition((array) $status);
        }
        $srch->applySearchConditions(['keyword' => $keyword, 'user_id' => $this->siteUserId, 'lang_id' => $this->siteLangId]);
        $srch->joinWithStats();
        $srch->addOrderBy(['fque_status' => 'ASC']);
        $srch->addMultipleFields([
            'fque_id', 'fque_title', 'fque_slug', 'fque_status', 'fque_comments_allowed',
            'fstat_comments', 'fstat_likes', 'fstat_dislikes', 'fque_added_on',
        ]);
        $post['pageno'] = (0 < $pageNo ? $pageNo : 1);
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($records as $key => $row) {
            $records[$key]['fque_added_on'] = MyDate::convert($row['fque_added_on']);
        }
        $statusArr = ForumQuestion::getQuestionStatusArray();
        $this->sets([
            "arrListing" => $records,
            "statusArr" => $statusArr,
            "post" => $post,
            "recordCount" => $srch->recordCount()
        ]);
        $this->_template->render(false, false, 'forum/my-questions-search.php');
    }

    public function searchComments()
    {
        $queId = FatApp::getPostedData('que_id', FatUtility::VAR_INT, 0);
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        if (1 > $queId) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Invalid_Request'));
        }
        $srch = new ForumQuestionCommentSearch($queId);
        $srch->applyPrimaryConditions();
        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $srch->setPageNumber($page);
        $srch->addMultipleFields(ForumQuestionCommentSearch::getListingFields());
        $srch->addOrder('fquecom_id', 'DESC');
        $records = $srch->fetchAndFormat();
        $rectObj = new ForumReaction($this->siteUserId, 0, ForumReaction::REACT_TYPE_COMMENT);
        $loggedUserReactions = $rectObj->getLoggedUserReactions((array) $queId);
        $this->sets([
            "records" => $records,
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'page' => $page,
            'loggedUserReactions' => $loggedUserReactions,
            'pageSize' => FatApp::getConfig('CONF_ADMIN_PAGESIZE')
        ]);
        $this->_template->render(false, false, 'forum/my-question-comments.php');
    }

    public function form($id = 0)
    {
        $id = FatUtility::int($id);
        $frm = $this->getForm();
        $data['fque_id'] = $id;
        $data['fque_status'] = 0;
        if (1 > $id) {
            $data['fque_lang_id'] = $this->siteLangId;
        }
        $tags = [];
        if (0 < $id) {
            $que = new ForumQuestion($id, $this->siteUserId);
            $data = $que->getData();
            if (!$data || AppConstant::YES == $data['fque_deleted']) {
                FatUtility::dieWithError(Label::getLabel('ERR_Invalid_Request'));
            }
            if (ForumQuestion::FORUM_QUE_RESOLVED == $data['fque_status']) {
                Message::addErrorMessage(Label::getLabel('ERR_Cannot_make_changes_on_Resolved_status'));
            }
            $tags = $que->getTags([$id], 0, false);
        }
        $frm->fill($data);
        $this->sets(["includeEditor" => true, "tags" => $tags, "frm" => $frm, "data" => $data]);
        $this->_template->addJs(['js/forum-common.js']);
        $this->_template->render(true, true, 'forum/add-form.php');
    }

    public function setup()
    {
        $frm = $this->getForm(true);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $id = FatApp::getPostedData('fque_id', FatUtility::VAR_INT, 0);
        $republish = false;
        $sendPublishedNoti = false;
        $status = FatApp::getPostedData('fque_status', FatUtility::VAR_INT, 0);
        if (0 < $id) {
            $data = $this->getAndValidateQuestion($id);
            if (ForumQuestion::FORUM_QUE_PUBLISHED == $status && null != $data['fque_published_on'] && in_array($data['fque_status'], [ForumQuestion::FORUM_QUE_DRAFT, ForumQuestion::FORUM_QUE_RESOLVED])
            ) {
                $republish = true;
                $sendPublishedNoti = true;
            }
        }
        if (ForumQuestion::FORUM_QUE_PUBLISHED == $status && false == $republish) {
            $post['fque_published_on'] = date('Y-m-d H:i:s');
            $sendPublishedNoti = true;
        }
        $qTags = $post['fque_sel_tags'];
        unset($post['fque_tags'], $post['fque_sel_tags']);
        $post['fque_status'] = $status;
        $post['fque_comments_allowed'] = FatApp::getPostedData('fque_comments_allowed', FatUtility::VAR_INT, 0);
        if (0 < $post['fque_comments_allowed']) {
            $post['fque_comments_allowed'] = 1;
        }
        unset($post['fque_id'], $post['btn_submit']);
        $post['fque_title'] = ForumQuestion::sanitizeTitle($post['fque_title']);
        $post['fque_slug'] = CommonHelper::seoUrl($post['fque_slug']);
        $que = new ForumQuestion($id, $this->siteUserId, $this->siteLangId);
        if (!$que->saveQuestion($post)) {
            FatUtility::dieJsonError($que->getError());
        }
        $this->handleTags($que, $qTags);
        if (true === $sendPublishedNoti) {
            $que->sendPublishStatusNotifications($republish);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Forum_Question_Saved_Successfully'));
    }

    private function handleTags(object $que, string $qTags)
    {
        $qTags = explode(',', $qTags);
        $qTags = array_filter($qTags);
        /* Delete previous entries */
        if (0 < count($qTags)) {
            /* add entries */
            if (ForumTag::TAGS_BINDING_LIMIT_WITH_QUE < count($qTags)) {
                $msg = Label::getLabel('MSG_max_{max-tags}_can be_bind_with_a_question');
                $msg = CommonHelper::replaceStringData($msg, ['{max-tags}' => ForumTag::TAGS_BINDING_LIMIT_WITH_QUE]);
                FatUtility::dieJsonError($msg);
            }
        }
        /* Delete previous Tag entries */
        $que->unbindAllTags();
        /* add Tag entries */
        if (0 < count($qTags)) {
            $que->bindTags($qTags);
        }
    }

    private function getAndValidateQuestion($id)
    {
        $data = ForumQuestion::getAttributesById($id, ['fque_id', 'fque_user_id', 'fque_status', 'fque_deleted', 'fque_published_on']);
        if (!$data) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Invalid_Request'));
        }
        if ($this->siteUserId != $data['fque_user_id'] || AppConstant::YES === $data['fque_deleted']) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Invalid_Request'));
        }
        if (ForumQuestion::FORUM_QUE_RESOLVED == $data['fque_status']) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Cannot_make_changes_on_Resolved_status'));
        }
        if (ForumQuestion::FORUM_QUE_SPAMMED == $data['fque_status']) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Cannot_make_changes_on_Spammed_status'));
        }
        return $data;
    }

    private function getForm(bool $setUnique = false)
    {
        $frm = new Form('addquestion');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox(Label::getLabel('LBL_Title'), 'fque_title');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(ForumQuestion::QUEST_TITLE_MIN_LENGTH, ForumQuestion::QUEST_TITLE_MAX_LENGTH);

        $fld = $frm->addTextBox(Label::getLabel('LBL_Question_slug'), 'fque_slug');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(ForumQuestion::QUEST_TITLE_MIN_LENGTH, ForumQuestion::QUEST_TITLE_MAX_LENGTH);
        if ($setUnique) {
            $fld->setUnique(ForumQuestion::DB_TBL, 'fque_slug', 'fque_id', 'fque_id', 'fque_id');
        }

        $frm->addHtmlEditor(Label::getLabel('LBL_Description'), 'fque_description')->requirements()->setRequired();
        $languages = Language::getAllNames();
        $langId = User::getAttributesById($this->siteUserId, 'user_lang_id');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Language'), 'fque_lang_id', $languages, '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addCheckBox(Label::getLabel('LBL_Allow_Comments'), 'fque_comments_allowed', 1, [], false, 0);
        $fld->requirements()->setInt();
        $fld = $frm->addCheckBox(Label::getLabel('LBL_Question_published'), 'fque_status', 1, [], false, 0);
        $fld->requirements()->setInt();
        $fld = $frm->addTextBox(Label::getLabel('LBL_Tags'), 'fque_tags');
        $fld = $frm->addHiddenField('', 'fque_sel_tags', '', ['id' => 'fque_sel_tags']);
        $fld = $frm->addHiddenField('', 'fque_id', '', ['id' => 'fque_id']);
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_ASK_NOW'), ['class' => 'btn btn--primary']);
        return $frm;
    }

    private function getSearchForm()
    {
        $frm = new Form('srchQuestionForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_keyword'), 'keyword');
        $statusArr = ForumQuestion::getQuestionStatusArray();
        $statusArr = [-1 => Label::getLabel('LBL_select')] + $statusArr;
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Status'), 'fque_status', $statusArr, -1, [], '');
        $fld->requirements()->setInt();
        $frm->addHiddenField('', 'pageno');
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'), ['class' => 'btn btn--primary']);
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }

}
