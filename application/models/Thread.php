<?php

/**
 * This class is used to handle Message Threads
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Thread extends MyAppModel
{

    const DB_TBL = 'tbl_threads';
    const DB_TBL_PREFIX = 'thread_';
    const DB_TBL_USERS = 'tbl_thread_users';
    const DB_TBL_THREAD_MSGS = 'tbl_thread_msgs';

    /* Thread Types */
    const PRIVATE = 1;
    const GROUP = 2;
    const READ = 1;
    const UNREAD = 0;
    const HEADINGIMAGE = [
        self::PRIVATE => Afile::TYPE_USER_PROFILE_IMAGE,
        self::GROUP => Afile::TYPE_GROUP_CLASS_BANNER
    ];

    private $type;

    public function __construct(int $id)
    {
        parent::__construct(static::DB_TBL, 'thread_id', $id);
    }

    /**
     * Setup Private Thread
     * 
     * @param int $sender
     * @param int $receiver
     * @return bool
     */
    public function setupPrivate(int $sender, int $receiver): bool
    {
        $this->type = static::PRIVATE;
        if (!$this->validatePrivate($sender, $receiver)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if ($this->threadExist($sender, $receiver)) {
            return true;
        }
        if (!$this->createThread($sender)) {
            return false;
        }
        if (!$this->addThreadUsers([$sender, $receiver])) {
            return false;
        }
        return true;
    }

    /**
     * Setup Group Thread
     * 
     * @return bool
     */
    public function setupGroup(int $sender, int $groupId): bool
    {
        $this->type = static::GROUP;
        $users = OrderClass::getLearners($groupId);
        $receivers = array_column($users, 'user_id');
        $receivers = [...$receivers, $sender];
        if (!$this->validateGroup($sender, $groupId)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if ($this->groupThreadExist($groupId)) {
            return true;
        }
        if (!$this->createThread($sender, $groupId)) {
            return false;
        }
        if (!$this->addThreadUsers($receivers)) {
            return false;
        }
        return true;
    }

    /**
     * Validate Private Thread
     * 
     * @param int $sender
     * @param int $receiver
     * @return bool
     */
    private function validatePrivate(int $sender, int $receiver): bool
    {
        $srch = new SearchBase(User::DB_TBL);
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->addDirectCondition('user_verified IS NOT NULL');
        $srch->addCondition('user_active', '=', AppConstant::YES);
        $srch->addCondition('user_id', 'IN', [$sender, $receiver]);
        $srch->addFld('user_is_teacher');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (count($records) != 2 || !array_sum(array_column($records, 'user_is_teacher'))) {
            return false;
        }
        return true;
    }

    /**
     * Validate Group Thread
     * 
     * @param int $sender
     * @param int $groupId
     * @param array $receivers
     * @return bool
     */
    private function validateGroup(int $sender, int $groupId): bool
    {
        $srch = new SearchBase(GroupClass::DB_TBL);
        $srch->addCondition('grpcls_id', '=', $groupId);
        $srch->addCondition('grpcls_teacher_id', '=', $sender);
        $srch->setPageSize(1);
        $srch->getResultSet();
        return $srch->recordCount() > 0 ? true : false;
    }

    /**
     * Check Thread Exist
     * 
     * @param int $sender
     * @param int $receiver
     * @return int 
     */
    public function threadExist(int $sender, int $receiver): bool
    {
        $srch = new SearchBase(static::DB_TBL_USERS, 'thusr');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'thread.thread_id = thusr.thusr_thread_id', 'thread');
        $srch->addHaving('mysql_func_count(distinct thusr.thusr_user_id)', '>', 1, 'AND', true);
        $srch->addCondition('thusr.thusr_user_id', 'IN', [$sender, $receiver]);
        $srch->addCondition('thread.thread_type', '=', static::PRIVATE);
        $srch->addGroupBy('thusr.thusr_thread_id');
        $srch->addFld('thusr.thusr_thread_id');
        $srch->setPageSize(1);
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if ($record) {
            $this->mainTableRecordId = $record['thusr_thread_id'];
            return true;
        }
        return false;
    }

    /**
     * Check Group Thread Exist
     * 
     * @param int $groupId
     * @return bool 
     */
    public function groupThreadExist(int $groupId): bool
    {
        $srch = new SearchBase(static::DB_TBL, 'thread');
        $srch->addCondition('thread_group_id', '=', $groupId);
        $srch->setPageSize(1);
        $srch->getResultSet();
        $srch->addFld('thread_id');
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if ($record) {
            $this->mainTableRecordId = $record['thread_id'];
            return true;
        }
        return false;
    }

    /**
     * Get thread id from group
     * @param int $groupId
     * @return int
     */
    public static function getIdByGroupId(int $groupId): int
    {
        $srch = new SearchBase(static::DB_TBL, 'thread');
        $srch->addCondition('thread_group_id', '=', $groupId);
        $srch->addFld('thread_id');
        $srch->setPageSize(1);
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return FatUtility::int($record['thread_id'] ?? 0);
    }

    /**
     * Create New Thread
     * 
     * @param int $sender
     * @return bool
     */
    private function createThread(int $sender, int $groupId = 0): bool
    {
        $this->assignValues([
            'thread_type' => $this->type,
            'thread_user_id' => $sender,
            'thread_group_id' => $groupId,
            'thread_created' => date('Y-m-d H:i'),
            'thread_updated' => date('Y-m-d H:i')
        ]);
        if (!$this->addNew()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Add Thread Users
     * 
     * @param array $userIds
     * @return bool
     */
    public function addThreadUsers(array $userIds): bool
    {
        $colors = static::getUserColors($userIds);
        $threadId = $this->getMainTableRecordId();
        foreach ($userIds as $userId) {
            $record = new TableRecord(static::DB_TBL_USERS);
            $data = [
                'thusr_user_id' => $userId,
                'thusr_thread_id' => $threadId,
                'thusr_color' => $colors[$userId],
                'thusr_deleted' => NULL,
                'thusr_read' => AppConstant::YES
            ];
            $record->assignValues($data);
            if (!$record->addNew([], $data)) {
                $this->error = $record->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Get User Colors
     * 
     * @param array $userIds
     * @return array
     */
    private static function getUserColors(array $userIds): array
    {
        $colors = [
            '#FF0000',
            '#b021a2',
            '#12126b',
            '#3b3b80',
            '#694069',
            '#024D02',
            '#630463',
            '#42806b',
            '#808000',
            '#1b471b',
            '#b81111',
            '#7d1445',
            '#996405',
            '#734444',
            '#808080',
            '#737305',
            '#08c787',
            '#cc6918',
            '#174352',
            '#590C0C',
            '#294a4a',
            '#0000FF',
            '#00008B',
            '#A602A6',
            '#069106',
            '#910991',
            '#6e8a12',
            '#6b6b37',
            '#008000',
            '#800000',
            '#9c1654',
            '#cc8504',
            '#594444',
            '#4a3b3b',
            '#306c80',
            '#31a37d',
            '#875b62',
            '#488da3',
            '#5d1cad',
            '#08cccc',
            '#4d4d85',
            '#171740',
            '#800080',
            '#04E004',
            '#f00af0',
            '#80800a',
            '#5c5c1b',
            '#306330',
            '#8c2e2e',
            '#e6056e',
            '#FFA500',
            '#000000',
            '#635b5b',
            '#4a0f0f',
            '#356957',
            '#cf7934'
        ];
        shuffle($colors);
        return array_combine(array_intersect_key($userIds, $colors), array_intersect_key($colors, $userIds));
    }

    /**
     * Get User Ids
     * 
     * @param int $threadId
     * @return array $userIds = [];
     */
    public static function getUserIds(int $threadId): array
    {
        $srch = new SearchBase(static::DB_TBL_USERS);
        $srch->addCondition('thusr_thread_id', '=', $threadId);
        $srch->addDirectCondition('thusr_deleted IS NULL');
        $srch->addFld('thusr_user_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        return array_column($rows, 'thusr_user_id');
    }

    /**
     * Get Receiver
     * 
     * @param int $threadId
     * @param int $senderId
     * @return array
     */
    public static function getReceiver(int $threadId, int $senderId): array
    {
        $srch = new SearchBase(static::DB_TBL_USERS, 'thusr');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'thusr.thusr_user_id = users.user_id', 'users');
        $srch->addCondition('thusr_thread_id', '=', $threadId);
        $srch->addCondition('thusr_user_id', '!=', $senderId);
        $srch->addMultipleFields(['CONCAT(users.user_first_name, " ", users.user_last_name) as user_name', 'users.user_id']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Mark thread and messages read for a user
     * 
     * @param int $userId
     * @return bool
     */
    public function markRead(int $userId): bool
    {
        if (UserAuth::getAdminLoggedIn()) {
            return true;
        }
        $threadId = $this->getMainTableRecordId();
        $db = FatApp::getDb();
        $db->startTransaction();
        $record = new TableRecord(static::DB_TBL_USERS);
        $record->assignValues(['thusr_read' => static::READ, 'thusr_last_read' => date('Y-m-d H:i:s')]);
        if (!$record->update(['smt' => 'thusr_thread_id = ? AND thusr_user_id = ?', 'vals' => [$threadId, $userId]])) {
            $db->rollbackTransaction();
            $this->error = $record->getError();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Update thread
     * 
     * @return bool
     */
    public function updateThread(): bool
    {
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues(['thread_updated' => date('Y-m-d H:i:s')]);
        if (!$record->update(['smt' => 'thread_id = ?', 'vals' => [$this->getMainTableRecordId()]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Sets thread status read for users
     * 
     * @return bool
     */
    public function setUsersReadStatus($userId): bool
    {
        $record = new TableRecord(static::DB_TBL_USERS);
        $record->assignValues(['thusr_read' => static::UNREAD, 'thusr_reminder' => NULL]);
        if (!$record->update(['smt' => 'thusr_thread_id = ? AND thusr_user_id != ?', 'vals' => [$this->getMainTableRecordId(), $userId]])) {
            $this->error = $record->getError();
            return false;
        }
        if (UserAuth::getAdminLoggedIn()) {
            return true;
        }
        $record->assignValues(['thusr_read' => static::READ, 'thusr_last_read' => date('Y-m-d H:i')]);
        if (!$record->update(['smt' => 'thusr_thread_id = ? AND thusr_user_id = ?', 'vals' => [$this->getMainTableRecordId(), $userId]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Deletes a user from thread
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteThreadUser(int $userId): bool
    {
        $record = new TableRecord(static::DB_TBL_USERS);
        $record->assignValues(['thusr_deleted' => date('Y-m-d H:i:s')]);
        if (!$record->update(['smt' => 'thusr_thread_id = ? AND thusr_user_id = ?', 'vals' => [$this->getMainTableRecordId(), $userId]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Get User Threads
     * 
     * @param int $userId
     * @param int $type
     * @param array $post
     * @return array
     */
    public static function getUserThreads(int $userId, array $post): array
    {
        $user = User::getAttributesById($userId, ['user_lang_id']);
        $srch = new ThreadSearch($userId, $user['user_lang_id']);
        $srch->applyPrimaryConditions();
        $srch->applySearchConditions($post);
        $srch->addSearchListingFields();
        $srch->addOrder('thread_updated', 'DESC');
        return $srch->fetchAndFormat();
    }

    /**
     * Email notifications to users for their unread Messages
     * Unread duration can be configured in admin panel
     *
     * @return bool
     */
    public function sendUnreadMsgsNotifications()
    {
        if (FatApp::getConfig('CONF_ENABLE_UNREAD_MSG_NOTIFICATION') == AppConstant::NO) {
            return true;
        }
        $srch = new SearchBase(Thread::DB_TBL_USERS, 'thusr');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'thread.thread_id = thusr.thusr_thread_id', 'thread');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'thusr.thusr_user_id = usr.user_id AND usr.user_deleted IS NULL', 'usr');
        $srch->addCondition('thusr.thusr_read', '=', 0);
        $srch->addDirectCondition('thusr.thusr_deleted IS NULL');
        $srch->addDirectCondition('thusr.thusr_reminder IS NULL');
        $srch->addDirectCondition('(thusr.thusr_last_read <= "' . date('Y-m-d H:i:s', strtotime('-' . FatApp::getConfig('CONF_UNREAD_MSG_NOTIFICATION_DURATION') . ' minutes')) . '" OR thusr.thusr_last_read IS NULL)');
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $srch->addFld('thusr.thusr_user_id');
        $srch->addGroupBy('thusr.thusr_user_id');
        $unreadThreads = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($unreadThreads as $unreadThread) {
            $totalUnreadThreads = static::getUserThreads($unreadThread['thusr_user_id'],  ['status' => 0, 'keyword' => '']);
            $user = User::getAttributesById($unreadThread['thusr_user_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id']);
            $threadsContent = $this->getThreadContent($totalUnreadThreads, $user['user_lang_id']);
            $totalUnreadCount = ThreadMessage::getUnreadCount($unreadThread['thusr_user_id']);
            if ($totalUnreadCount < 1) {
                continue;
            }
            $vars = [
                '{user_full_name}' => ucwords($user['user_first_name'] . ' ' . $user['user_last_name']),
                '{unread_messages_count}' => ($totalUnreadCount > 99) ? '99+' : $totalUnreadCount,
                '{messages_detail}' => $threadsContent,
            ];
            /* prepare email content and send notification */
            $mail = new FatMailer($user['user_lang_id'], 'unread_messages_email');
            $mail->setVariables($vars);
            if ($mail->sendMail([$user['user_email']])) {
                /* update the email sent status for message */
                $record = new TableRecord(static::DB_TBL_USERS);
                $record->assignValues(['thusr_reminder' => date('Y-m-d H:i:s')]);
                if (!$record->update(['smt' => 'thusr_user_id = ?', 'vals' => [$unreadThread['thusr_user_id']]])) {
                    $this->error = $record->getError();
                    return false;
                }
            }
        }
        return true;
    }

    private function getThreadContent($unreadThread, $langId)
    {
        $table = new HtmlElement('table', ['style' => 'border:1px solid #ddd;', 'cellspacing' => '0', 'cellpadding' => '0', 'border' => '0']);
        $tbody = $table->appendElement('tbody');
        foreach ($unreadThread as $thread) {
            if (!isset($thread['msg_id']) || $thread['msg_id'] < 1) {
                continue;
            }
            $unread_count = isset($thread['thread_unread']) ? $thread['thread_unread'] : 0;
            if ($unread_count < 1) {
                continue;
            }
            $tr = $tbody->appendElement('tr');
            $td = $tr->appendElement('td', ['style' => "padding:10px; font-size:13px; color:#333; border-bottom:1px solid #ddd; width:70px"]);
            if ($thread['thread_type'] == Thread::PRIVATE) {
                $str = '<div style="background-color: #333;border-radius: 100px;font-size: 13px;width: 50px;height:50px;text-align: center;vertical-align: middle;display: block;overflow: hidden;"><img style="width:100%;height:100%;object-fit: cover;" src="' . MyUtility::makeFullUrl('Image', 'show', [static::HEADINGIMAGE[$thread['thread_type']], $thread['thread_record_id'], 'SIZE_SMALL'], CONF_WEBROOT_FRONTEND) . '"></div>';
            } else {
                $str = '<div style="background-color: #333;border-radius: 100px;font-size: 13px;width: 50px;height:50px;text-align: center;vertical-align: middle;display: inherit;overflow: hidden;"><img src="' . MyUtility::makeFullUrl('Images', 'group.png', [], CONF_WEBROOT_FRONTEND) . '"></div>';
            }
            $td->appendElement('plaintext', [], $str, true);
            $td = $tr->appendElement('td', ['style' => "padding:10px; font-size:13px; color:#333; border-bottom:1px solid #ddd; width:153"]);
            $msg = '<b>' . ucfirst($thread['thread_title']) . '</b><br>';
            if (!empty($attachment)) {
                if (isset($thread['msg_text']) && !empty($thread['msg_text'])) {
                    $msg = (strlen($thread['msg_text']) > 40) ? mb_substr($thread['msg_text'], 0, 40, 'utf-8') . '...' : $thread['msg_text'] . '<br>';
                }
                $msg .= '<svg style="width: 16px;float: left;height: 16px;padding: 6px 0 0 0;" class="icon icon--arrow icon--small color-white"">
                        <use xlink:href="' . CONF_WEBROOT_URL . 'images/sprite.svg#attach"></use>
                        </svg><span style="float: left;width: 100px;padding: 6px;">' . Label::getLabel('LBL_Attachment', $langId) . '<span></span></span>';
            } else {
                $msg .= (strlen($thread['msg_text']) > 60) ? mb_substr($thread['msg_text'], 0, 60, 'utf-8') . '...' : $thread['msg_text'];
            }
            $td->appendElement('plaintext', [], $msg, true);
            $td = $tr->appendElement('td', ["style" => "padding:10px; font-size:13px; color:#333; border-bottom:1px solid #ddd; width:153"]);
            $label = Label::getLabel('LBL_{msg-count}_Message(s)', $langId);
            $count = ($unread_count > 99) ? '99+' : $unread_count;
            $td->appendElement('plaintext', [], str_replace('{msg-count}', $count, $label), true);
            $td = $tr->appendElement('td', ["style" => "padding:10px; font-size:13px; color:#333; border-bottom:1px solid #ddd; width:153"]);
            $str = '<a target="_blank" href="' . MyUtility::makeFullUrl('Chats', 'index', [$thread['thread_id']], CONF_WEBROOT_DASHBOARD) . '" style="background:{secondary-color}; color:{secondary-inverse-color}; text-decoration:none;font-size:16px; font-weight:500;padding:10px 30px;display:inline-block;border-radius:3px;">' . Label::getLabel('LBL_VIEW', $langId) . '</a>';
            $td->appendElement('plaintext', [], $str, true);
        }
        return $table->getHtml();
    }

    /**
     * Delete Threads
     * 
     * @return bool
     */
    public function markDelete(): bool
    {
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues(['thread_deleted' => date('Y-m-d H:i:s')]);
        if (!$record->update(['smt' => 'thread_id = ?', 'vals' => [$this->getMainTableRecordId()]])) {
            $this->error = $record->getError();
            return false;
        }
        $record = new TableRecord(static::DB_TBL_USERS);
        $record->assignValues(['thusr_deleted' => date('Y-m-d H:i:s')]);
        if (!$record->update(['smt' => 'thusr_thread_id = ?', 'vals' => [$this->getMainTableRecordId()]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public static function validateById($id)
    {
        $srch = new SearchBase(static::DB_TBL, 'threads');
        $srch->addCondition('threads.thread_id', '=', $id);
        $srch->addDirectCondition('threads.thread_deleted IS NULL');
        $srch->setPageSize(1);
        $srch->getResultSet();
        return $srch->recordCount() > 0 ? true : false;
    }
}
