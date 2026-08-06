<?php

class ForumTagRequestsController extends AccountController
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
     * List of tag requests
     *
     */
    public function index()
    {
        $this->_template->addJs('forum/page-js/tag-requests.js');
        $this->_template->render(true, true, 'forum/tag-requests/index.php');
    }

    /**
     * To get list HTML
     *
     */
    public function search()
    {
        $srch = new SearchBase(ForumTag::DB_TBL_REQUESTS, 'ftagreq');
        $srch->addCondition('ftagreq_user_id', '=', $this->siteUserId);
        $srch->addOrder('ftagreq_status', 'ASC');
        $srch->addOrder('ftagreq_id', 'DESC');
        $srch->addMultipleFields(['ftagreq_id', 'ftagreq_name', 'ftagreq_language_id', 'ftagreq_status',]);
        $this->sets([
            "requests" => FatApp::getDb()->fetchAll($srch->getResultSet()),
            "languages" => Language::getAllNames(),
            "statusArr" => ForumTagRequest::getStatusArray(),
        ]);
        $this->_template->render(false, false, 'forum/tag-requests/search.php');
    }

    /**
     * Approval Request for a new tag
     *
     */
    public function form($id = 0)
    {
        $frm = $this->getForm();
        if (0 < $id) {
            $record = ForumTagRequest::getAttributesById($id);
            if (false === $record) {
                FatUtility::dieWithError(Label::getLabel('ERR_Invalid_Request'));
            }
            if ($this->siteUserId !== FatUtility::int($record['ftagreq_user_id'])) {
                FatUtility::dieWithError(Label::getLabel('ERR_Invalid_Request'));
            }
            $frm->fill($record);
        }
        $this->set('frm', $frm);
        $this->_template->render(false, false, 'forum/tag-requests/form.php');
    }

    /**
     * Approval Request for a new tag
     *
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $keyword = ForumTag::sanitizeName($post['ftagreq_name']);
        if (!CommonHelper::sanitizeInput([$keyword])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SCRIPT_TAG_NOT_ALLOWED_IN_FIELDS') . ' ' . implode(', ', CommonHelper::getCaptionsForFields($frm, ['ftagreq_name'])));
        }
        $id = $post['ftagreq_id'];
        $langId = $post['ftagreq_language_id'];
        $tag = ForumTag::getTagByName($keyword, $langId, -1);
        if (!empty($tag)) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Forum_tag_already_available'));
        }
        $tagReq = new ForumTagRequest($this->siteUserId, $langId, $id);
        if (!$tagReq->validateRequest($keyword)) {
            FatUtility::dieJsonError($tagReq->getError());
        }
        if (!$tagReq->saveRequest($keyword)) {
            FatUtility::dieWithError($tagReq->getError());
        }
        if (1 > $id) {
            $data['tag_name'] = $keyword;
            $data['user_full_name'] = $this->siteUser['user_first_name'] . ' ' . $this->siteUser['user_last_name'] ?? '';
            $data['user_email'] = $this->siteUser['user_email'];
            $data['user_lang_id'] = $this->siteUser['user_lang_id'];
            $tagReq->sendRequestNotifications($data);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('ERR_Forum_tag_request_saved_Successfully'));
    }

    /**
     * form HTML for a new custom tag request
     *
     * return Form object
     */
    private function getForm()
    {
        $frm = new Form('flashcardFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox(Label::getLabel('LBL_Tag'), 'ftagreq_name', '', ['id' => 'ftagreq_name', 'autocomplete' => 'off']);
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(2, 50);
        $fld->requirements()->setLength(2, 50);
        $languages = Language::getAllNames();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Language'), 'ftagreq_language_id', $languages, '', array(), '');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addHiddenField('', 'ftagreq_id', 0);
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Request_Tag'), ['class' => 'btn btn--primary']);
        return $frm;
    }

}
