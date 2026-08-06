<?php

class ForumController extends MyAppController
{

    /**
     * Initialize Forum Questions
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (in_array($action, ['addComment', 'markComment', 'upVote', 'downVote', 'addReaction', 'reportForm', 'setupReportQuestion'])) {
            if(empty($this->siteUserId)) {
                $msg = Label::getLabel('LBL_SESSION_EXPIRED');
                FatUtility::dieJsonError($msg);
            } elseif ($this->siteUserType == User::AFFILIATE) {
                $msg = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
                FatUtility::dieJsonError($msg);
            }
            if (!FatUtility::isAjaxCall()) {
                http_response_code(401);
            }
            
        }
    }

    public function index()
    {
        $srchFrmObj = $this->getSearchForm();
        $filterData = $this->processQueryStringData();
        $srchFrmObj->fill($filterData);
        $srch = new ForumQuestionSearch();
        $srchCount = clone $srch;
        $srchCount->applyPrimaryConditions();
        /* All questions, irrespective of language, deleted, spammed. Not included Draft */
        $srchCount->addStatusCondition([ForumQuestion::FORUM_QUE_PUBLISHED, ForumQuestion::FORUM_QUE_RESOLVED, ForumQuestion::FORUM_QUE_SPAMMED]);
        $srchCount->doNotCalculateRecords();
        $tagIds = [];
        if (0 < $filterData['tag_id']) {
            $tagIds = [$filterData['tag_id']];
        } elseif (0 < $this->siteUserId) {
            $tagObj = new ForumTag();
            $subscribedTags = $tagObj->getSubscribedTagsList($this->siteUserId, $this->siteLangId);
            if (0 < count($subscribedTags)) {
                $tagIds = array_keys($subscribedTags);
            }
        }
        $this->getSideBarContent([], $tagIds);
        $this->sets([
            'srchFrmObj' => $srchFrmObj,
            'srchWithType' => $filterData['srch_type'],
            'totalQuestions' => $srchCount->getRecordCount(),
            'srchTypes' => ForumQuestionSearch::getSearchTypeArr(),
            'totalComments' => ForumQuestionCommentSearch::getTotalComments(),
        ]);
        $this->addCommonJsCss();
        $this->_template->render();
    }

    public function frame(int $id)
    {
        $data = ForumQuestion::getAttributesById($id, 'fque_description');
        $this->set('data', $data);
        $this->_template->render(false, false, '_partial/frame.php');
    }

    private function processSearchTagSlug(string $tagSlug): array
    {
        if (empty($tagSlug)) {
            return [];
        }
        $tagSlug = explode('-', $tagSlug);
        if (2 > count($tagSlug)) {
            return [];
        }
        $tagId = FatUtility::int(array_pop($tagSlug));
        if (1 > $tagId) {
            return [];
        }
        $tag['tag_name'] = implode('-', $tagSlug);
        $tag['tag_id'] = $tagId;
        return $tag;
    }

    private function addCommonJsCss()
    {
        $this->_template->addJs(['js/select2.js', 'forum/page-js/common.js']);
        $this->_template->addCss(['css/select2.min.css', 'css/forum' . '-' . $this->siteLanguage['language_direction'] . '.css']);
    }

    public function tags($tag = '')
    {
        $this->index($tag);
    }

    public function search()
    {
        $srch = new ForumQuestionSearch();
        $srch->applyPrimaryConditions();
        $srch->joinWithUsers();
        $srchQryStrArr = $this->processQueryStringData();
        $srch->applySearchByType($srchQryStrArr['srch_type']);
        if (0 < $srchQryStrArr['tag_id']) {
            $srchQryStrArr['keyword'] = '';
            $srchQryStrArr['tags'] = [$srchQryStrArr['tag_id']];
            $srch->joinWithTags();
        }
        $srch->applySearchConditions($srchQryStrArr);
        $srch->joinWithStats();
        $srch->addMultipleFields(ForumQuestionSearch::getListingFields());
        $order = ['fque_updated_on' => 'DESC'];
        if (ForumQuestionSearch::TYPE_POPULAR == $srchQryStrArr['srch_type']) {
            $order = ['pop_count' => 'DESC'];
        }
        $srch->addOrderBy($order);
        $srch->doNotCalculateRecords();
        $srchQryStrArr['pagesize'] = AppConstant::PAGESIZE;
        $srch->setPageSize($srchQryStrArr['pagesize']);
        $srch->setPageNumber($srchQryStrArr['pageno']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $questIds = array_column($records, 'fque_id');
        $quesTags = [];
        $loggedUserReactions = [];
        if (!empty($questIds)) {
            $quesTags = $this->getQuestionTags($questIds);
            if (0 < $this->siteUserId) {
                $rectObj = new ForumReaction($this->siteUserId, 0, ForumReaction::REACT_TYPE_QUESTION);
                $loggedUserReactions = $rectObj->getLoggedUserReactions($questIds);
            }
        }
        $statusArr = ForumQuestion::getQuestionStatusArray();
        $this->sets([
            "arrListing" => $records,
            "statusArr" => $statusArr,
            "loggedUserReactions" => $loggedUserReactions,
            "post" => $srchQryStrArr,
            "quesTags" => $quesTags,
            "recordCount" => $srch->getRecordCount()
        ]);
        $this->_template->render(false, false);
    }

    private function processQueryStringData()
    {
        $data = [
            'keyword' => '',
            'tag_id' => 0,
            'pageno' => 1,
            'srch_type' => FatApp::getQueryStringData('search_type', FatUtility::VAR_INT, ForumQuestionSearch::TYPE_ALL),
            'lang_id' => $this->siteLangId,
        ];
        $keyword = FatApp::getQueryStringData('keyword', null, '');
        $keyword = trim($keyword);
        if (0 < strlen($keyword)) {
            $data['keyword'] = $keyword;
        }
        $tag = FatApp::getQueryStringData('tag', null, '');
        $tag = $this->processSearchTagSlug($tag);
        if (array_key_exists('tag_id', $tag) && 0 < $tag['tag_id']) {
            $data['keyword'] = $tag['tag_name'];
            $data['tag_id'] = $tag['tag_id'];
        }
        $data['pageno'] = FatApp::getQueryStringData('pageno', FatUtility::VAR_INT, 1);
        if (1 > $data['pageno']) {
            $data['pageno'] = 1;
        }
        if (!array_key_exists($data['srch_type'], ForumQuestionSearch::getSearchTypeArr())) {
            $data['srch_type'] = ForumQuestionSearch::TYPE_ALL;
        }
        return $data;
    }

    public function view(string $slug)
    {
        if (empty($slug)) {
            FatUtility::exitWithErrorCode(404);
        }
        $srch = new ForumQuestionSearch();
        $srch->applyPrimaryConditions();
        $srch->joinWithUsers();
        $activeStatuses = [ForumQuestion::FORUM_QUE_PUBLISHED, ForumQuestion::FORUM_QUE_RESOLVED];
        $srch->addStatusCondition($activeStatuses);
        $srch->applySearchConditions(['fque_slug' => $slug, 'lang_id' => $this->siteLangId]);
        $srch->joinWithStats();
        $srch->addMultipleFields(ForumQuestionSearch::getListingFields());
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        if (!is_array($data)) {
            FatUtility::exitWithErrorCode(404);
        }
        $que = new ForumQuestion($data['fque_id'], $this->siteUserId, $this->siteLangId);
        $queTags = $que->getTags([$data['fque_id']], $this->siteLangId);
        $queTagIds = [];
        if (0 < count($queTags)) {
            $queTagIds = array_keys($queTags);
        } elseif (0 < $this->siteUserId) {
            $tagObj = new ForumTag();
            $subscribedTags = $tagObj->getSubscribedTagsList($this->siteUserId, $this->siteLangId);
            if (0 < count($subscribedTags)) {
                $queTagIds = array_keys($subscribedTags);
            }
        }
        $this->getSideBarContent([$data['fque_id']], $queTagIds);
        if ($que->addViewEntry(session_id(), $this->getIPAddress())) {
            $data['fstat_views']++;
        }
        $commFrmObj = $this->getCommentsForm();
        $commFrmObj->fill(['fcomm_fque_id' => $data['fque_id']]);
        $srchCommentsFrm = $this->getCommentSearchForm();
        $srchCommentsFrm->fill(['fque_id' => $data['fque_id']]);
        $loggedUserReactions = [];
        if (0 < $this->siteUserId) {
            $rectObj = new ForumReaction($this->siteUserId, 0, ForumReaction::REACT_TYPE_QUESTION);
            $loggedUserReactions = $rectObj->getLoggedUserReactions((array) $data['fque_id']);
        }
        $repQue = new ForumReportedQuestion($this->siteUserId, $data['fque_id']);
        $reportedData = $repQue->getReportDetail($this->siteLangId, ['fquerep_id', 'fquerep_user_id']);
        $this->sets([
            'commFrmObj' => $commFrmObj,
            'srchCommentsFrm' => $srchCommentsFrm,
            'quesId' => $data['fque_id'],
            'tags' => $queTags,
            'data' => $data,
            'siteUserId' => $this->siteUserId,
            'siteUser' => $this->siteUser,
            'loggedUserReactions' => $loggedUserReactions,
            'reportedData' => $reportedData,
        ]);
        $this->addCommonJsCss();
        $this->_template->render();
    }

    private function getSideBarContent(array $queIds = [], array $tagIds = [])
    {
        $srch = new TeacherSearch($this->siteLangId, $this->siteUserId, 0);
        $srch->applyPrimaryConditions();
        $fQueObj = new ForumQuestionSearch();
        $this->sets([
            'totalTutors' => $srch->getRecordCount(),
            'topRatedTeachers' => TeacherSearch::getTopRatedTeachers($this->siteLangId, 0, 5),
            'popularTags' => ForumTagSearch::getPopularTags($this->siteLangId),
            'recommendedPosts' => $fQueObj->getRecommendedPosts($this->siteLangId, $queIds, $tagIds, $this->siteUserId),
        ]);
    }

    public function addComment()
    {
        $frm = $this->getCommentsForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError($frm->getValidationErrors());
        }
        
        AbusiveWord::validateContent($post['fcomm_comment']);
        
        $que = new ForumQuestion($post['fcomm_fque_id'], $this->siteUserId, ForumReaction::REACT_TYPE_QUESTION, $this->siteLangId);
        if (!$que->canUserComment()) {
            FatUtility::dieJsonError($record->getError());
        }
        $record = new ForumQuestionComment($this->siteUserId, $post['fcomm_fque_id']);
        $data = [
            "fquecom_fque_id" => $post['fcomm_fque_id'],
            "fquecom_comment" => $post['fcomm_comment'],
            "fquecom_user_id" => $this->siteUserId,
            "fquecom_added_on" => date('Y-m-d H:i:s')
        ];
        $record->assignValues($data);
        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$record->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($record->getError());
        }
        $forumStat = new ForumStat($post['fcomm_fque_id'], ForumReaction::REACT_TYPE_QUESTION);
        if (!$forumStat->setCommentCount()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($forumStat->getError());
        }
        $db->commitTransaction();
        $que->loadFromDb();
        $data['author_id'] = $que->getFldValue('fque_user_id');
        if ($data['author_id'] !== $this->siteUserId) {
            $data['by_user_id'] = $this->siteUserId;
            $data['que_title'] = $que->getFldValue('fque_title');
            $data['que_link'] = MyUtility::makeFullUrl('Forum', 'View', [$que->getFldValue('fque_slug')], CONF_WEBROOT_FRONTEND);
            $record->sendPostCommentNotifications($data);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Comment_Added_Successfully'));
    }

    public function comments()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $queId = FatApp::getPostedData('que_id', FatUtility::VAR_INT, 0);
        $queData = ForumQuestion::getAttributesById($queId, ['fque_comments_allowed', 'fque_user_id']);
        $srch = new ForumQuestionCommentSearch($queId);
        $srch->applyPrimaryConditions();
        $srchObj = clone $srch;
        $status = [ForumQuestion::FORUM_QUE_PUBLISHED, ForumQuestion::FORUM_QUE_RESOLVED];
        $status = array_map(function ($statusVals) {
            return 'mysql_func_' . $statusVals;
        }, $status);
        $srch->addCondition('fque_status', 'IN', $status, 'AND', true);
        $srch->addMultipleFields([
            'fque_id',
            'fque_user_id',
            'fquecom_id',
            'fquecom_comment',
            'fquecom_user_id',
            'fquecom_accepted',
            'fquecom_added_on',
            'user_last_name',
            'user_first_name',
            'COALESCE(fstat_dislikes, 0) as fstat_dislikes',
            'COALESCE(fstat_likes, 0) as fstat_likes'
        ]);
        $orderBy = FatApp::getPostedData('order_by', null, 'fquecom_id');
        $order = 'DESC';
        /* if ('latest' == $orderBy) {
          $orderBy = 'fquecom_id';
          } */
        if (!in_array($orderBy, ['fquecom_id', 'most_liked'])) {
            $orderBy = 'fquecom_id';
        }
        $srch->addOrder('fquecom_accepted', 'DESC');
        if ('most_liked' == $orderBy) {
            $srch->addFld('COALESCE((fstat_likes - fstat_dislikes), 0) as most_liked');
            $srch->addOrder('most_liked', 'DESC');
        }
        $srch->addOrder($orderBy, $order);
        $srch->addOrder('fquecom_id', 'DESC');
        $srch->setPageSize(AppConstant::PAGESIZE);
        $srch->setPageNumber($page);
        $quesCommnts = FatApp::getDb()->fetchAll($srch->getResultSet(), 'fquecom_id');
        $totalPages = $srch->pages();
        $totalCount = $srch->recordCount();
        $srchObj->addCondition('fquecom_accepted', '=', 'mysql_func_' . AppConstant::YES, 'AND', true);
        $srchObj->addMultipleFields(['fquecom_id']);
        $srchObj->doNotCalculateRecords();
        $srchObj->setPageSize(1);
        $acceptedRow = FatApp::getDb()->fetch($srchObj->getResultSet());
        $commentsIds = array_column($quesCommnts, 'fquecom_id');
        $loggedUserReactions = [];
        if (0 < $this->siteUserId && 0 < count($commentsIds)) {
            $rectObj = new ForumReaction($this->siteUserId, 0, ForumReaction::REACT_TYPE_COMMENT);
            $loggedUserReactions = $rectObj->getLoggedUserReactions((array) $commentsIds);
        }
        $this->sets([
            'quesComments' => $quesCommnts,
            'loggedUserReactions' => $loggedUserReactions,
            'acceptedRow' => $acceptedRow,
            'commentsCount' => $totalCount,
            'page' => $page,
            'quesId' => $queId,
            'pageCount' => $totalPages,
            'userId' => $this->siteUserId,
            'commentsAllowed' => $queData['fque_comments_allowed'],
            'queUserId' => $queData['fque_user_id'],
        ]);
        $ret['htm'] = $this->_template->render(false, false, null, true);
        $ret['totalCount'] = $totalCount;
        FatUtility::dieJsonSuccess($ret);
    }

    /**
     * Accept/Unaccept a comment of an Question
     */
    public function markComment()
    {
        $recordId = FatApp::getPostedData('record_id', FatUtility::VAR_INT, 0);
        $queId = FatApp::getPostedData('fque_id', FatUtility::VAR_INT, 0);
        if (1 > $recordId || 1 > $queId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Invalid_Request'));
        }
        $recordObj = new ForumQuestionComment($this->siteUserId, $queId, $recordId);
        if (!$recordObj->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$recordObj->canUserMarkComment()) {
            FatUtility::dieJsonError($recordObj->getError());
        }
        $accepted = $recordObj->getFldValue('fquecom_accepted');
        $db = FatApp::getDb();
        $accept = AppConstant::YES;
        /* Mark question as resolved and disallow the further commenting */
        $openForComments = AppConstant::NO;
        $quesStatus = ForumQuestion::FORUM_QUE_RESOLVED;
        $db->startTransaction();
        if (AppConstant::YES == $accepted) {
            $accept = AppConstant::NO;
            /* Mark question as published and allow the further commenting */
            $quesStatus = ForumQuestion::FORUM_QUE_PUBLISHED;
        } else {
            if (!$db->updateFromArray(ForumQuestionComment::DB_TBL, ['fquecom_accepted' => AppConstant::NO], ['smt' => 'fquecom_fque_id = ?', 'vals' => [$queId]])) {
                /* Mark all other comments as unaccepted */
                $db->rollbackTransaction();
                FatUtility::dieJsonError($db->getError());
            }
        }
        /* Mark current comment as accepted */
        $recordObj->assignValues(['fquecom_accepted' => $accept]);
        /* Mark current comment as accepted */
        if (!$recordObj->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($recordObj->getError());
        }
        $question = new ForumQuestion($queId);
        $dataToupdate = [
            'fque_status' => $quesStatus,
            'fque_comments_allowed' => $openForComments
        ];
        $question->assignValues($dataToupdate);
        if (!$question->save()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($question->getError());
        }
        if (!$db->commitTransaction()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($db->getError());
        }
        $question->loadFromDb();
        if (AppConstant::YES === $accept) {
            $data = [
                'author_id' => $question->getFldValue('fque_user_id'),
                'by_user_id' => $recordObj->getFldValue('fquecom_user_id'),
                'question_title' => $question->getFldValue('fque_title'),
                'question_view_link' => MyUtility::makeFullUrl('Forum', 'View', [$question->getFldValue('fque_slug')], CONF_WEBROOT_FRONTEND)
            ];
            $recordObj->sendCommentAcceptStatusNotifications($data);
        }
        $msg = Label::getLabel('LBL_Comment_Marked_Accepted_Successfully');
        if (AppConstant::NO === $accept) {
            $msg = Label::getLabel('LBL_Comment_Marked_Unaccepted_Successfully');
        }
        $ret = [
            'marked' => $accept,
            'msg' => $msg
        ];
        FatUtility::dieJsonSuccess($ret);
    }

    public function upVote()
    {
        $this->addReaction(ForumReaction::REACTION_LIKE);
    }

    public function downVote()
    {
        $this->addReaction(ForumReaction::REACTION_DISLIKE);
    }

    private function addReaction($reaction)
    {
        if (!$this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_You_have_to_login_first'));
        }
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $reactType = FatApp::getPostedData('reactType', FatUtility::VAR_INT, 0);
        $record = new ForumReaction($this->siteUserId, $recordId, $reactType);
        $recordData = $record->getRecord();
        $voted = $reaction;
        $db = FatApp::getDb();
        $db->startTransaction();
        if (empty($recordData)) {
            $data = [
                "freact_user_id" => $this->siteUserId,
                "freact_type" => $reactType,
                "freact_record_id" => $recordId,
                "freact_reaction" => $reaction,
                "freact_added_on" => date('Y-m-d H:i:s')
            ];
            $record->assignValues($data);
            if (!$record->save()) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($record->getError());
            }
        } else {
            if ($recordData['freact_reaction'] == $reaction) {
                $voted = 0;
                if (!FatApp::getDb()->deleteRecords(ForumReaction::DB_TBL, ['smt' => 'freact_id = ?', 'vals' => [$recordData['freact_id']]])) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError(FatApp::getDb()->getError());
                }
            } else {
                if (!FatApp::getDb()->updateFromArray(ForumReaction::DB_TBL, ['freact_reaction' => $reaction], ['smt' => 'freact_id = ?', 'vals' => [$recordData['freact_id']]])) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError(FatApp::getDb()->getError());
                }
            }
        }
        $upVote = 0;
        $downVote = 0;
        if (ForumReaction::REACTION_LIKE == $reaction) {
            $upVote = 1;
        } elseif (ForumReaction::REACTION_DISLIKE == $reaction) {
            $downVote = 1;
        }
        if (!empty($recordData)) {
            if (0 == $voted) {
                if (ForumReaction::REACTION_DISLIKE == $reaction && $recordData['freact_reaction'] == $reaction) {
                    $downVote = -1;
                    $upVote = 0;
                } else {
                    $upVote = -1;
                    $downVote = 0;
                }
            } else {
                if (ForumReaction::REACTION_LIKE == $reaction && ForumReaction::REACTION_DISLIKE == $recordData['freact_reaction']) {
                    $upVote = 1;
                    $downVote = -1;
                } elseif (ForumReaction::REACTION_DISLIKE == $reaction && ForumReaction::REACTION_LIKE == $recordData['freact_reaction']) {
                    $downVote = 1;
                    $upVote = -1;
                }
            }
        }
        $forumStat = new ForumStat($recordId, $reactType);
        if (!$forumStat->updateReactionCount($upVote, $downVote)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($forumStat->getError());
        }
        if (!$db->commitTransaction()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($forumStat->getError());
        }
        $ret = [
            'msg' => Label::getLabel('LBL_Updated_Successfully'),
            'voteType' => $voted
        ];
        FatUtility::dieJsonSuccess($ret);
    }

    public function reportForm()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $que = new ForumQuestion($recordId);
        if (!$que->canUserReport($this->siteUserId, $this->siteLangId)) {
            FatUtility::dieJsonError($que->getError());
        }
        $frm = $this->getReportForm();
        $frm->fill(['fquerep_fque_id' => $recordId]);
        $this->sets([
            'frm' => $frm,
            'id' => $recordId,
        ]);
        $this->_template->render(false, false);
    }

    public function setupReportQuestion()
    {
        if ($this->siteUserId < 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Invalid_Request'));
        }
        $frm = $this->getReportForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (!$post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $queId = $post['fquerep_fque_id'];
        $que = new ForumQuestion($queId);
        if (!$que->canUserReport($this->siteUserId, $this->siteLangId)) {
            FatUtility::dieJsonError($que->getError());
        }
        unset($post['btn_submit']);
        $post['fquerep_frireason_id'] = $post['rep_reason'];
        unset($post['rep_reason']);
        $post['fquerep_user_id'] = $this->siteUserId;
        $post['fquerep_added_on'] = date('Y-m-d H:i:s');
        $record = new TableRecord(ForumQuestion::DB_TBL_QUEST_REPORTED);
        $record->assignValues($post);
        if (!$record->addNew([], $post)) {
            FatUtility::dieJsonError($record->getError());
        }
        $que->loadFromDb();
        $data['author_id'] = $que->getFldValue('fque_user_id');
        if ($data['author_id'] !== $this->siteUserId) {
            $data['by_user_id'] = $this->siteUserId;
            $data['que_title'] = $que->getFldValue('fque_title');
            $data['que_link'] = MyUtility::makeFullUrl('Forum', 'View', [$que->getFldValue('fque_slug')], CONF_WEBROOT_URL);
            $que->sendReportSpamNotifications($data);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Question_Reported_Successfully'));
    }

    public function autoCompleteTags()
    {
        $keyword = FatApp::getPostedData('keyword', null, '');
        $tags = ForumTag::getAllTags(false, true, false, FatApp::getConfig('CONF_ADMIN_PAGESIZE'), $keyword, $this->siteLangId);
        FatUtility::dieJsonSuccess(['data' => $tags]);
    }

    private function getQuestionTags($queId)
    {
        $ques = new ForumQuestion(0, 0, $this->siteLangId);
        $data = $ques->getTags($queId, $this->siteLangId, false);
        if (is_array($queId)) {
            $formattedData = [];
            foreach ($data as $val) {
                $formattedData[$val['ftagque_fque_id']][$val['ftag_id']] = $val['ftag_name'];
            }
            return $formattedData;
        }
        return $data;
    }

    private function getSearchForm()
    {
        $frm = new Form('srchQuestionForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Forum_Question_Search_keyword'), 'keyword');
        $frm->addHiddenField('', 'search_type');
        $frm->addHiddenField('', 'tag_id');
        $fld = $frm->addHiddenField('', 'pageno');
        $fld->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'), ['class' => '']);
        return $frm;
    }

    private function getCommentsForm()
    {
        $frm = new Form('addCommentForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextArea('', 'fcomm_comment', '')->requirements()->setRequired();
        $frm->addHiddenField('', 'fcomm_fque_id');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Submit'), ['class' => 'btn btn--primary']);
        return $frm;
    }

    private function getCommentSearchForm()
    {
        $frm = new Form('frmComments');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'page');
        $frm->addHiddenField('', 'fque_id');
        return $frm;
    }

    private function getReportForm()
    {
        $frm = new Form('frmReportQuestion');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Title'), 'rep_reason', ForumReportIssueReason::getAllReasons(true, 0, true, $this->siteLangId), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $frm->addTextArea(Label::getLabel('LBL_Comment'), 'fquerep_comments', '')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        $frm->addHiddenField('', 'fquerep_fque_id')->requirements()->setIntPositive();
        return $frm;
    }

    private function getIPAddress()
    {
        //whether ip is from the share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from the proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from the remote address
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Get Bread Crumb Nodes
     *
     * @param type $action
     * @return type
     */
    public function getBreadcrumbNodes($action)
    {
        $nodes = [];
        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $urlController = implode('-', $arr);
        $className = ucwords(implode(' ', $arr));
        if ($action == 'index') {
            $nodes[] = ['title' => $className];
        } else {
            $nodes[] = ['title' => $className, 'href' => MyUtility::makeUrl($urlController)];
        }
        $parameters = FatApp::getParameters();
        if (!empty($parameters) && $action == 'view') {
            $queId = reset($parameters);
            $queId = FatUtility::int($queId);
            $question = ForumQuestion::getAllAttributesById($queId, 0, $this->siteLangId);
            $title = $question['fque_title'];
        }
        switch ($action) {
            default:
                $nodes[] = ['title' => $title ?? '', 'href' => MyUtility::makeUrl($urlController)];
                $nodes[] = ['title' => $title ?? ''];
                break;
        }
        return $nodes;
    }

}
