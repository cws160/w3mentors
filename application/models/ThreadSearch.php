<?php

/**
 * This class is used Search Thread
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class ThreadSearch extends SearchBase
{

    protected $userId;

    /**
     * setup thread
     * fetch group threads
     * fetch thread messages
     * 
     */

    /**
     * Initialize Thread Search
     * 
     * @param int $userId
     */
    public function __construct(int $userId, int $langId = 0)
    {
        $this->userId = $userId;
        parent::__construct(Thread::DB_TBL_USERS, 'thusr');
        $this->joinTable(Thread::DB_TBL, 'INNER JOIN', 'thread.thread_id = thusr.thusr_thread_id', 'thread');
        $this->joinTable(GroupClass::DB_TBL, 'LEFT JOIN', 'grpcls.grpcls_id = thread.thread_group_id', 'grpcls');
        $this->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = grpclsLang.gclang_grpcls_id AND grpclsLang.gclang_lang_id = ' . $langId, 'grpclsLang');
        $this->joinTable(Thread::DB_TBL_USERS, 'LEFT JOIN', 'thusr2.thusr_thread_id = thusr.thusr_thread_id AND thusr2.thusr_user_id !=' . $this->userId, 'thusr2');
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'ouser.user_id = thusr2.thusr_user_id', 'ouser');
        $this->addGroupBy('thread.thread_id');
        $this->doNotCalculateRecords();
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addDirectCondition('thusr.thusr_deleted IS NULL');
        $this->addCondition('thusr.thusr_user_id', '=', $this->userId);
        $this->addDirectCondition('thread.thread_deleted IS NULL');
        if (API_CALL) {
            $cond = "thread.thread_type = '" . Thread::PRIVATE ."'";
            if (FatApp::getConfig('CONF_GROUP_CLASSES_DISABLED') == AppConstant::YES) {
                $cond .= " OR (thread.thread_type = '" . Thread::GROUP . "' AND grpcls.grpcls_booked_seats > 0)";
            }
            $this->addDirectCondition("($cond)");
        } else {
            $this->addDirectCondition('(thread.thread_type = "' . Thread::PRIVATE .'" OR (thread.thread_type = "' . Thread::GROUP . '" AND grpcls.grpcls_booked_seats > 0))');
        }
    }

    /**
     * Apply Search Conditions
     * 
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        /* Read/unread filter */
        if ($post['status'] !== '') {
            $this->addCondition('thusr.thusr_read', '=', $post['status']);
        }
        /* Keyword */
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $this->addDirectCondition('(grpcls.grpcls_title LIKE "%' . $keyword . '%" OR ' .  'grpclsLang.grpcls_title LIKE "%' . $keyword . '%" OR '.
                    ' CONCAT(ouser.user_first_name, " ", ouser.user_last_name) LIKE "%' . $keyword . '%")');
        }
    }

    /**
     * Add Search Listing Fields
     * 
     * @return void
     */
    public function addSearchListingFields(): void
    {
        $fields = $this->getListingFields();
        foreach ($fields as $field => $alias) {
            $this->addFld($field . ' AS ' . $alias);
        }
    }

    /**
     * Fetch and format
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $this->setPageNumber(1);
        $this->setPageSize(10000);
        $records = FatApp::getDb()->fetchAll($this->getResultSet());
        if (empty($records)) {
            return [];
        }
        $threadIds = array_column($records, 'thread_id');
        $messages = $this->getLastMessages($threadIds);
        $unreadCount = $this->getUnreadCountThreadUser($threadIds);
        foreach ($records as $key => $record) {
            $threadId = FatUtility::int($record['thread_id']);
            if ($record['thread_type'] == Thread::PRIVATE) {
                $record['thread_title'] = $record['ouser_name'] ?? '';
                $record['thread_record_id'] = $record['ouser_id'] ?? '';
            } elseif ($record['thread_type'] == Thread::GROUP) {
                $record['thread_title'] = $record['grpcls_title'];
                $record['thread_record_id'] = $record['grpcls_parent'] ?? $record['grpcls_id'];
            }
            $record['thread_unread'] = $unreadCount[$threadId] ?? 0;
            $record['thread_updated'] = MyDate::convert($record['thread_updated']);
            $records[$key] = array_merge($record, $messages[$threadId] ?? []);
        }
        return $records;
    }

    /**
     * Get Listing Fields
     * 
     * @return array
     */
    public function getListingFields(): array
    {
        return [
            'thread.thread_id' => 'thread_id',
            'thread.thread_type' => 'thread_type',
            'thread.thread_updated' => 'thread_updated',
            'thusr.thusr_read' => 'thread_read',
            'thusr.thusr_color' => 'thusr_color',
            'grpcls.grpcls_id' => 'grpcls_id',
            'IFNULL(grpclsLang.grpcls_title, grpcls.grpcls_title)' => 'grpcls_title',
            'grpcls.grpcls_parent' => 'grpcls_parent',
            'CONCAT(ouser.user_first_name, " ", ouser.user_last_name)' => 'ouser_name',
            'ouser.user_id' => 'ouser_id'
        ];
    }

    /**
     * Returns last message for a thread
     * 
     * @param array $threadIds
     * @return array
     */
    private function getLastMessages(array $threadIds): array
    {
        $srch = new SearchBase(Thread::DB_TBL_THREAD_MSGS, 'msg');
        $on = 'msg.msg_deleted IS NULL AND msg_temp.msg_deleted IS NULL AND ';
        $on .= 'msg_temp.msg_id > msg.msg_id AND msg_temp.msg_thread_id = msg.msg_thread_id';
        $srch->joinTable(Thread::DB_TBL_THREAD_MSGS, 'LEFT JOIN', $on, 'msg_temp');
        $srch->joinTable(Thread::DB_TBL_USERS, 'LEFT JOIN', 'thusr.thusr_user_id = msg.msg_user_id', 'thusr');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'musr.user_id = msg.msg_user_id', 'musr');
        $srch->addMultipleFields(['msg.msg_id', 'msg.msg_thread_id as thread_id',
            'msg.msg_text', 'msg.msg_user_id', 'msg.msg_created', 'thusr.thusr_color as msg_user_color', 'CONCAT(musr.user_first_name, " ", musr.user_last_name) as msg_user_name']);
        $srch->addDirectCondition('msg_temp.msg_id IS NULL');
        $srch->addDirectCondition('msg.msg_deleted IS NULL');
        $threadIds = implode(",", FatUtility::int($threadIds));
        $srch->addDirectCondition('msg.msg_thread_id IN (' . $threadIds . ')');
        $srch->addGroupBy('msg.msg_thread_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet(), 'thread_id');
        if (!empty($rows)) {
            $files = MessageSearch::getAttachments(array_column($rows, 'msg_id'));
            foreach ($rows as $key => $row) {
                $filename = $files[$row['msg_id']]['file_name'] ?? $row['msg_text'];
                $row['msg_text'] = empty($row['msg_text']) ? $filename : $row['msg_text'];
                $row['msg_created'] = MyDate::convert($row['msg_created']);;
                $rows[$key] = $row;
            }
        }
        return $rows;
    }

    /**
     * Returns  unread count for a thread
     * 
     * @param array $threadIds
     * @return array
     */
    private function getUnreadCountThreadUser(array $threadIds): array
    {
        $srch = new SearchBase(Thread::DB_TBL_THREAD_MSGS, 'msg');
        $srch->joinTable(Thread::DB_TBL_USERS, 'INNER JOIN', 'msg.msg_thread_id = thusr.thusr_thread_id AND thusr.thusr_user_id = ' . $this->userId . ' AND ( thusr.thusr_last_read < msg.msg_created  OR thusr.thusr_last_read IS NULL ) AND thusr.thusr_read = ' . Thread::UNREAD, 'thusr');
        $srch->addMultipleFields(['msg.msg_thread_id as thread_id', 'count(msg_id) AS unread_count']);
        $srch->addDirectCondition('msg.msg_deleted IS NULL');
        $srch->addCondition('msg.msg_thread_id', 'IN', $threadIds);
        $srch->addCondition('msg.msg_user_id', '!=', $this->userId);
        $srch->addGroupBy('msg.msg_thread_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Record Count
     * 
     * @return int
     */
    public function recordCount(): int
    {
        $db = FatApp::getDb();
        $order = $this->order;
        $page = $this->page;
        $pageSize = $this->pageSize;
        $this->limitRecords = false;
        $this->order = [];
        $qry = $this->getQuery() . ' LIMIT ' . SEARCH_MAX_COUNT . ', 1';
        if ($db->totalRecords($db->query($qry)) > 0) {
            $recordCount = SEARCH_MAX_COUNT;
        } else {
            if (empty($this->groupby) && empty($this->havings)) {
                $this->addFld('COUNT(*) AS total');
                $rs = $db->query($this->getQuery());
            } else {
                $rs = $db->query('SELECT COUNT(*) AS total FROM (' . $this->getQuery() . ') t');
            }
            $recordCount = FatUtility::int($db->fetch($rs)['total'] ?? 0);
        }
        $this->order = $order;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->limitRecords = true;
        return $recordCount;
    }

}
