<?php

/**
 * Chat controller is used to handle Private and Group Chat
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class ChatsController extends DashboardController
{

    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index(int $threadId = 0)
    {
        $threadId = FatUtility::int(FatApp::getPostedData()['thread_id'] ?? $threadId);
        $frmSrch = $this->getThreadSearchForm();
        $this->set('frmSrch', $frmSrch);
        $this->set('threadId', $threadId);
        $this->set('isAdminLoggedIn', FatUtility::int(UserAuth::getAdminLoggedIn()));
        $this->_template->render();
    }

    /**
     * Thread Search
     */
    public function threadSearch()
    {
        $post = FatApp::getPostedData();
        $frm = $this->getThreadSearchForm();
        $post = $frm->getFormDataFromArray($post);
        $srch = new ThreadSearch($this->siteUserId, $this->siteLangId);
        $srch->applyPrimaryConditions();
        $srch->applySearchConditions($post);
        $srch->addSearchListingFields();
        $srch->addOrder('thread_updated', 'DESC');
        $srch->addOrder('thread_id', 'DESC');
        $this->set('threads', $srch->fetchAndFormat());
        $this->set('threadId', $post['thread_id']);
        $this->_template->render(false, false);
    }

    /**
     * Message Search
     */
    public function messageSearch()
    {
        $post = FatApp::getPostedData();
        $threadId = FatUtility::int($post['thread_id']);
        if (empty($threadId) || !Thread::validateById($threadId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getMessageForm();
        $frm->fill(['thread_id' => $threadId]);
        $thread = new Thread($threadId);
        $thread->markRead($this->siteUserId);
        $srch = new MessageSearch($this->siteUserId, $threadId);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('msg_id', 'DESC');
        $srch->setPageNumber($post['page']);
        $srch->setPageSize(AppConstant::PAGESIZE);
        if (API_CALL) {
            $records = $srch->fetchAndFormat();
        } else {
            $records = array_reverse($srch->fetchAndFormat());
        }
        $this->sets([
            'frm' => $frm,
            'page' => $post['page'],
            'records' => $records,
            'recordCount' => $srch->recordCount(),
            'heading' => $this->getThreadHeading($threadId),
            'deleteDuration' => FatApp::getConfig('CONF_DELETE_ATTACHMENT_ALLOWED_DURATION')
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Thread heading
     * 
     * @param int $threadId
     * @return array
     */
    private function getThreadHeading(int $threadId): array
    {
        $thread = Thread::getAttributesById($threadId, ['thread_type', 'thread_group_id']);
        if ($thread['thread_type'] == Thread::GROUP) {
            $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
            $srch->addFld('IFNULL(grpclsLang.grpcls_title, grpcls.grpcls_title) AS grpcls_title');
            $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = grpclsLang.gclang_grpcls_id AND grpclsLang.gclang_lang_id = ' . $this->siteLangId, 'grpclsLang');
            $srch->addCondition('grpcls_id', '=', $thread['thread_group_id']);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $group = FatApp::getDb()->fetch($srch->getResultSet());
            $image = '';
            $title = $group['grpcls_title'];
        } else {
            $receiver = Thread::getReceiver($threadId, $this->siteUserId);
            $image = MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $receiver['user_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL);
            $title = $receiver['user_name'];
        }
        return ['title' => $title, 'image' => $image, 'type' => $thread['thread_type']];
    }

    /**
     * Thread form 
     */
    public function threadForm()
    {
        $post = FatApp::getPostedData();
        if (empty($post['thread_type']) && empty($post['receiver'])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $thread = new Thread(0);
        if ($post['thread_type'] == Thread::PRIVATE) {
            $thread->threadExist($post['receiver'], $this->siteUserId);
        } else {
            $thread->groupThreadExist($post['receiver']);
        }
        if ($thread->getMainTableRecordId() > 0) {
            FatUtility::dieJsonSuccess(['threadId' => $thread->getMainTableRecordId()]);
        }
        $frm = $this->getThreadForm();
        $frm->fill($post);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Thread
     * 
     * 1. Validate Thread Form
     * 2. Setup New Thread
     * 3. Setup Thread Message 
     */
    public function threadSetup()
    {
        /* Validate Thread Form */
        $frm = $this->getThreadForm();
        $post = FatApp::getPostedData();
        if (!$post = $frm->getFormDataFromArray($post)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        
        AbusiveWord::validateContent($post['message']);
        
        $db = FatApp::getDb();
        $db->startTransaction();
        /* Setup New Thread */
        $thread = new Thread(0);
        if ($post['thread_type'] == Thread::PRIVATE) {
            $result = $thread->setupPrivate($this->siteUserId, $post['receiver']);
        } else {
            $result = $thread->setupGroup($this->siteUserId, $post['receiver']);
        }
        if (!$result) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError($thread->getError());
        }
        /* Setup Thread Message */
        $upload = empty($_FILES['upload']) ? [] : $_FILES['upload'];
        $threadId = $thread->getMainTableRecordId();
        $message = new ThreadMessage($threadId);
        if (!$message->setupMessage($this->siteUserId, $post['message'], $upload)) {
            $db->rollbackTransaction();
            MyUtility::dieJsonError($message->getError());
        }
        $db->commitTransaction();
        $srch = new MessageSearch($this->siteUserId, $threadId);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('msg_id', 'DESC');
        $srch->setPageSize(1);
        $msg = current($srch->fetchAndFormat());
        $msg['user_photo'] = User::getPhoto($msg['user_id']);
        MyUtility::dieJsonSuccess(['message' => $msg, 'threadId' => $threadId, 'msg' => Label::getLabel('MSG_MESSAGE_SENT!')]);
    }

    /**
     * Message Form
     */
    public function messageForm()
    {
        $frm = $this->getMessageForm();
        $frm->fill(FatApp::getPostedData());
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Message Setup
     */
    public function messageSetup()
    {
        $frm = $this->getMessageForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData() + $_FILES)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }       
        
        AbusiveWord::validateContent($post['message']);

        $db = FatApp::getDb();
        $db->startTransaction();
        $threadId = $post['thread_id'];
        $message = new ThreadMessage($threadId);
        $post['upload'] = empty($post['upload']) ? [] : $post['upload'];
        if (!$message->setupMessage($this->siteUserId, $post['message'], $post['upload'], $this->siteUserType)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($message->getError());
        }
        $db->commitTransaction();
        $recordId = $message->getMainTableRecordId();
        $msg = ThreadMessage::getAttributesById($recordId);
        $msg['user_photo'] = User::getPhoto($msg['msg_user_id']);
        $nameFld = 'CONCAT(user_first_name, " ", user_last_name)';
        $msg['user_name'] = User::getAttributesById($this->siteUserId, $nameFld);
        $colors = MessageSearch::getUserColors([$this->siteUserId], $threadId);
        $msg['user_color'] = $colors[$this->siteUserId] ?? '';
        MyUtility::dieJsonSuccess([
            'message' => $msg,
            'threadId' => $threadId,
            'msg' => Label::getLabel('MSG_MESSAGE_SENT!')
        ]);
    }

    /**
     * Download Attachment
     * 
     * @param $msgId
     */
    public function downloadAttachment(int $msgId)
    {
        $msg = new ThreadMessage(0, $msgId);
        if (!$msg->canDownload($this->siteUserId)) {
            FatUtility::dieWithError($msg->getError());
        }
        $file = new Afile(Afile::TYPE_MESSAGE_ATTACHMENT);
        $file->downloadByRecordId($msgId);
    }

    /**
     * Remove Attachment
     */
    public function removeAttachment()
    {
        $msgId = FatApp::getPostedData('msg_id', FatUtility::VAR_INT, 0);
        $threadId = FatApp::getPostedData('thread_id', FatUtility::VAR_INT, 0);
        if (empty($msgId) || empty($threadId)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        if ($this->siteUserType == User::TEACHER) {
            $groupId = Thread::getAttributesById($threadId, ['thread_group_id'])['thread_group_id'];
            if ($groupId) {
                $bookedSeats = GroupClass::getAttributesById($groupId, ['grpcls_booked_seats'])['grpcls_booked_seats'];
                if ($bookedSeats == 0) {
                    MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                }
            }
        }
        $msg = new ThreadMessage($threadId);
        if (!$msg->removeAttachment($msgId, $this->siteUserId)) {
            MyUtility::dieJsonError($msg->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_ATTACHMENT_REMOVED'));
    }

    /**
     * Thread Search Form
     * @return Form
     */
    private function getThreadSearchForm(): Form
    {
        $frm = new Form('frmThreadSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'thread_id');
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword');
        $options = [1 => Label::getLabel('LBL_READ'), 0 => Label::getLabel('LBL_UNREAD')];
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'status', $options);
        $fldSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fldCancel = $frm->addResetButton("", "btn_clear", Label::getLabel('LBL_Clear'), ['onclick' => 'clearSearch();', 'class' => 'btn--clear']);
        $fldSubmit->attachField($fldCancel);
        return $frm;
    }

    /**
     * Get Thread Form
     * @return Form
     */
    private function getThreadForm(): Form
    {
        $frm = new Form('frmThread');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextarea(Label::getLabel('LBL_MESSAGE'), 'message');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setLength(0, 1000);
        $frm->addFileUpload('', 'upload');
        $frm->addHiddenField('', 'receiver');
        $frm->addHiddenField('', 'thread_type', Thread::PRIVATE);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEND'));
        return $frm;
    }

    /**
     * Get Message Form
     * 
     * @return Form
     */
    private function getMessageForm(): Form
    {
        $frm = new Form('frmMessage');
        $frm = CommonHelper::setFormProperties($frm);
        $msg = $frm->addTextarea(Label::getLabel('LBL_MESSAGE'), 'message');
        $msg->requirements()->setLength(0, 1000);
        $upload = $frm->addFileUpload('', 'upload', []);
        $required = new FormFieldRequirement($msg->getName(), $msg->getCaption());
        $required->setLength(0, 1000);
        $required->setRequired(true);
        $optional = new FormFieldRequirement($msg->getName(), $msg->getCaption());
        $optional->setRequired(false);
        $upload->requirements()->addOnChangerequirementUpdate('', 'eq', $msg->getName(), $required);
        $upload->requirements()->addOnChangerequirementUpdate('', 'ne', $msg->getName(), $optional);
        $fld = $frm->addHiddenField(Label::getLabel('LBL_THREAD'), 'thread_id');
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEND'));
        return $frm;
    }

    /**
     * Get Unread Count OF Messages For a User
     */
    public function getUnreadCount()
    {
        $messCount = ThreadMessage::getUnreadCount($this->siteUserId);
        FatUtility::dieJsonSuccess(['messCount' => $messCount]);
    }

    /**
     * mail template view
     */
    public function getMailTemplate()
    {
        return;
    }
}
