<?php

class ForumTagsController extends AccountController
{

    /**
     * Initialize Home
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * to get subscribed tags index function
     */
    public function subscribed()
    {
        $subscFormObj = $this->subscribeTagForm();
        $this->set('subscFormObj', $subscFormObj);
        $this->_template->render(true, true, 'forum/subscribed-tags-index.php');
    }

    /**
     * To get subscribed tags list HTML
     */
    public function subscribedSearch()
    {
        $srch = new SearchBase(ForumTag::DB_TBL_SUBSCRIBED_TAGS, 'fsubsctag');
        $srch->joinTable(ForumTag::DB_TBL, 'LEFT JOIN', 'fsubsctag_ftag_id = ftag_id', 'ftag');
        $srch->addMultipleFields(['ftag_id', 'ftag_name', 'ftag_deleted', 'ftag_active']);
        $srch->addCondition('fsubsctag_user_id', '=', $this->siteUserId);
        $srch->addCondition('ftag_language_id', '=', $this->siteLangId);
        $srch->addOrder('ftag_name');
        $scbscTags = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('scbscTags', $scbscTags);
        $ret['html'] = $this->_template->render(false, false, 'forum/subscribed-tags-search.php', true, true);
        $ret['count'] = count($scbscTags);
        FatUtility::dieJsonSuccess($ret);
    }

    /**
     * To get subscribed tags list HTML
     */
    public function systemTagsList()
    {
        $srch = new ForumTagSearch($this->siteLangId, true, false);
        $pageNo = FatApp::getPostedData('pageno', FatUtility::VAR_INT, 1);
        $post['pageno'] = (0 < $pageNo ? $pageNo : 1);
        $post['pagesize'] = 50;
        $srch->addOrder('ftag_name');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addMultipleFields(['ftag_id', 'ftag_name', 'ftag_deleted', 'ftag_active']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->sets([
            "systemTags" => $records,
            "post" => $post,
            "recordCount" => $srch->recordCount()
        ]);
        $ret['html'] = $this->_template->render(false, false, 'forum/system-tags-list.php', true, true);
        FatUtility::dieJsonSuccess($ret);
    }

    /**
     * Subscribe a tag to get notifications
     */
    public function subscribe()
    {
        $tagId = FatApp::getPostedData('ftag_id', FatUtility::VAR_INT, 0);
        if (1 > $tagId) {
            FatUtility::dieWithError(Label::getLabel('ERR_Invalid_request'));
        }
        $tagObj = new ForumTag($tagId, $this->siteUserId);
        if (!$tagObj->subscribe()) {
            FatUtility::dieWithError($tagObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_You_have_subscribed_successfully'));
    }

    /**
     * Unsubscribe a tag not to get notifications
     */
    public function unSubscribe()
    {
        $tagId = FatApp::getPostedData('ftag_id', FatUtility::VAR_INT, 0);
        if (1 > $tagId) {
            FatUtility::dieWithError(Label::getLabel('ERR_Invalid_request'));
        }
        $tagObj = new ForumTag($tagId, $this->siteUserId);
        if (!$tagObj->unSubscribe()) {
            FatUtility::dieWithError($tagObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_You_have_unsubscribed_successfully'));
    }

    /**
     * Unsubscribe all tags not to get notifications
     */
    public function unSubscribeAll()
    {
        $tagObj = new ForumTag(0, $this->siteUserId, $this->siteLangId);
        if (!$tagObj->unSubscribeAll()) {
            FatUtility::dieWithError($tagObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_You_have_unsubscribed_From_All_Tags_successfully'));
    }

    /**
     * Forum Tags Auto-complete list
     */
    public function autoSuggestList()
    {
        $noTagResult = ['-1' => ['ftag_id' => '-1', 'ftag_name' => Label::getLabel('LBL_Forum_no_tag_found_wrt_your_search_keyword')]];
        $keyword = trim(FatApp::getPostedData('keyword', null, ''));
        if (empty($keyword)) {
            FatUtility::dieJsonSuccess(['data' => $noTagResult]);
        }
        $tags = ForumTag::getAllTags(false, true, false, FatApp::getConfig('CONF_ADMIN_PAGESIZE'), $keyword, $this->siteLangId);
        if (empty($tags)) {
            $tags = $noTagResult;
        }
        FatUtility::dieJsonSuccess(['data' => $tags]);
    }

    /**
     * form HTML for subscribing a tag
     */
    private function subscribeTagForm()
    {
        $frm = new Form('subscTagFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Forum_Search_Tag_to_Subscribe'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off'])->requirements()->setRequired();
        return $frm;
    }

}
