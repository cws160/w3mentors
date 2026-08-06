<?php

/**
 * This class is used handle Thread Messages
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class ThreadMessage extends MyAppModel
{

    const DB_TBL = 'tbl_thread_msgs';
    const DB_TBL_PREFIX = 'msg_';

    private $threadId;

    /**
     * Thread Message
     * 
     * @param int $threadId
     * @param int $msgId
     */
    public function __construct(int $threadId, int $msgId = 0)
    {
        $this->threadId = $threadId;
        parent::__construct(static::DB_TBL, 'msg_id', $msgId);
    }

    /**
     * Setup Message
     * 
     * 1. Sender is Thread User
     * 2. Save Thread Message
     * 4. Save Uploaded File
     * 5. Update thread
     * 6. Set Users Thread Read Status
     * 
     * @param int $sender
     * @param string $msg
     * @param array $upload
     * @return bool
     */
    public function setupMessage(int $sender, string $msg, array $upload, $userType = 0): bool
    {
        $userIds = Thread::getUserIds($this->threadId);
        if (!in_array($sender, $userIds)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if ($userType == User::TEACHER) {
            $groupId = Thread::getAttributesById($this->threadId, ['thread_group_id'])['thread_group_id'];
            if ($groupId) {
                $bookedSeats = GroupClass::getAttributesById($groupId, ['grpcls_booked_seats'])['grpcls_booked_seats'];
                if ($bookedSeats == 0) {
                    $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                    return false;
                }
            }
        }

        if (!$this->saveThreadMessage($sender, $msg)) {
            return false;
        }
        if (!$this->saveAttachment($upload)) {
            return false;
        }
        $thread = new Thread($this->threadId);
        if (!$thread->updateThread()) {
            $this->error = $thread->getError();
            return false;
        }
        if (!$thread->setUsersReadStatus($sender)) {
            $this->error = $thread->getError();
            return false;
        }
        if (empty($msg) && !empty($upload)) {
            $msg = Label::getLabel('LBL_ATTACHMENT_RECEIVED');
        }
        foreach ($userIds as $id) {
            if ($sender == $id) {
                continue;
            }
            $notif = new Notification($id, Notification::TYPE_NEW_MSG_RECEIVED);
            $notif->sendMessageNotification($sender, $msg);
        }
        return true;
    }

    /**
     * Save Thread Message
     * 
     * @param int $sender
     * @param string $msg
     * @return bool
     */
    private function saveThreadMessage(int $sender, string $msg): bool
    {
        $this->assignValues([
            'msg_user_id' => $sender,
            'msg_text' => trim($msg),
            'msg_thread_id' => $this->threadId,
            'msg_created' => date('Y-m-d H:i:s')
        ]);
        if (!$this->addNew()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Save Attachment
     * 
     * @param array $upload
     * @return bool
     */
    private function saveAttachment(array $upload): bool
    {
        if (empty($upload['name'])) {
            return true;
        }
        $msgId = $this->getMainTableRecordId();
        $file = new Afile(Afile::TYPE_MESSAGE_ATTACHMENT);
        if (!$file->saveFile($upload, $msgId)) {
            $this->error = $file->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove Attachment
     * 
     * @param int $msgId
     * @return bool
     */
    public function removeAttachment(int $msgId, int $userId): bool
    {
        $userIds = Thread::getUserIds($this->threadId);
        if (!in_array($userId, $userIds)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $srch = new SearchBase(static::DB_TBL, 'msg');
        $srch->addMultipleFields(['msg_created', 'msg_text']);
        $srch->addCondition('msg.msg_user_id', '=', $userId);
        $srch->addCondition('msg_id', '=', $msgId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $msgData = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($msgData)) {
            $this->error = Label::getLabel('MSG_Invalid_Request');
            return false;
        }
        $difference = (new DateTime($msgData['msg_created']))->diff(new DateTime());
        $duration = FatApp::getConfig('CONF_DELETE_ATTACHMENT_ALLOWED_DURATION');
        if ($difference->format('%i') >= $duration) {
            $msg = Label::getLabel('MSG_MESSAGES_OLDER_THAN_{msg-duration}_MINS_CANNOT_BE_REMOVED');
            $msg = str_replace('{msg-duration}', $duration, $msg);
            $this->error = $msg;
            return false;
        }
        /* delete attachment */
        $db = FatApp::getDb();
        $db->startTransaction();
        $file = new Afile(Afile::TYPE_MESSAGE_ATTACHMENT);
        if (!$file->removeFile($msgId, true)) {
            $this->error = $file->getError();
            $db->rollbackTransaction();
            return false;
        }
        $this->mainTableRecordId = $msgId;
        if (empty($msgData['msg_text'])) {
            $this->setFldValue('msg_deleted', date('Y-m-d H:i:s'));
            if (!$this->save()) {
                $db->rollbackTransaction();
                return false;
            }
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Return Unread Message Count of user
     * 
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount(int $userId): int
    {
        $srch = new SearchBase(Thread::DB_TBL_THREAD_MSGS, 'msg');
        $srch->joinTable(Thread::DB_TBL_USERS, 'INNER JOIN', 'msg.msg_thread_id = thusr.thusr_thread_id AND thusr.thusr_user_id = ' . $userId . ' AND ( thusr.thusr_last_read < msg.msg_created  OR thusr.thusr_last_read IS NULL ) AND thusr.thusr_read = ' . Thread::UNREAD, 'thusr');
        $srch->joinTable(Thread::DB_TBL, 'INNER JOIN', 'thread_id = thusr_thread_id AND thread_deleted IS NULL');
        $srch->addMultipleFields(['count(msg_id) AS unread_count']);
        $srch->addDirectCondition('msg.msg_deleted IS NULL');
        $srch->addDirectCondition('thusr.thusr_deleted IS NULL');
        $srch->addCondition('msg.msg_user_id', '!=', $userId);
        $srch->addGroupBy('thusr.thusr_user_id');
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return FatUtility::int($row['unread_count'] ?? 0);
    }

    /**
     * Validate if user has access to download attachment
     *
     * @param $userId
     * @return bool
     */
    public function canDownload(int $userId): bool
    {
        /* validate msg id */
        $srch = new SearchBase(static::DB_TBL, 'msg');
        $srch->addFld('msg.msg_thread_id');
        $srch->addDirectCondition('msg.msg_deleted IS NULL');
        $srch->addCondition('msg.msg_id', '=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $msgData = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($msgData)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $users = Thread::getUserIds($msgData['msg_thread_id']);
        if (!in_array($userId, $users)) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }
        return true;
    }
}
