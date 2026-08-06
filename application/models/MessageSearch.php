<?php

/**
 * This class is used Message Search for a thread
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class MessageSearch extends SearchBase
{

    private $userId;
    private $threadId;

    /**
     * Initialize Chat Search
     *
     */
    public function __construct(int $userId, int $threadId)
    {
        $this->userId = $userId;
        $this->threadId = $threadId;
        parent::__construct(Thread::DB_TBL_USERS, 'thusr');
        $this->joinTable(ThreadMessage::DB_TBL, 'INNER JOIN', 'msg.msg_thread_id = thusr.thusr_thread_id', 'msg');
        $this->doNotCalculateRecords();
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addDirectCondition('msg.msg_deleted IS NULL');
        $this->addCondition('thusr.thusr_user_id', '=', $this->userId);
        $this->addCondition('thusr.thusr_thread_id', '=', $this->threadId);
    }

    /**
     * Add Search Listing Fields
     * 
     * @return void
     */
    public function addSearchListingFields(): void
    {
        $fields = [
            'msg.msg_id' => 'msg_id',
            'msg.msg_text' => 'msg_text',
            'msg.msg_user_id' => 'user_id',
            'msg.msg_created' => 'msg_created'
        ];
        foreach ($fields as $field => $alias) {
            $this->addFld($field . ' AS ' . $alias);
        }
    }

    /**
     * Fetch And Format
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $records = FatApp::getDb()->fetchAll($this->getResultSet());
        if (empty($records)) {
            return [];
        }
        $userIds = array_column($records, 'user_id');
        $msgIds = array_column($records, 'msg_id');
        $userNames = static::getUserNames($userIds);
        $attachments = static::getAttachments($msgIds);
        $userColors = static::getUserColors($userIds, $this->threadId);
        foreach ($records as $key => $record) {
            $record['msg_created_utc'] = $record['msg_created'];
            $record['msg_created'] = MyDate::convert($record['msg_created']);
            $record['user_name'] = $userNames[$record['user_id']];
            $record['user_color'] = $userColors[$record['user_id']];
            $records[$key] = array_merge($record, $attachments[$record['msg_id']] ?? []);
        }
        return $records;
    }

    /**
     * Get User Names
     * 
     * @param array $userIds
     * @return array $userNames = [id => full_name]
     */
    public static function getUserNames(array $userIds): array
    {
        $userIds = FatUtility::int($userIds);
        $srch = new SearchBase(User::DB_TBL);
        $srch->addDirectCondition('user_id IN (' . implode(',', $userIds) . ')');
        $srch->addMultipleFields(['user_id', 'CONCAT(user_first_name, " ", user_last_name) as user_name']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get User Colors
     * 
     * @param array $userIds
     * @param int $threadId
     * @return array $userColors = [id => color]
     */
    public static function getUserColors(array $userIds, int $threadId): array
    {
        $userIds = FatUtility::int($userIds);
        $srch = new SearchBase(Thread::DB_TBL_USERS);
        $srch->addDirectCondition('thusr_user_id IN (' . implode(',', $userIds) . ')');
        $srch->addCondition('thusr_thread_id', '=', $threadId);
        $srch->addMultipleFields(['thusr_user_id', 'thusr_color']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Attachments
     * 
     * @param array $msgIds
     * @return array $attachments = [msg_id => $file['file_record_id','file_id', 'file_name']]
     */
    public static function getAttachments(array $msgIds): array
    {
        $msgIds = FatUtility::int($msgIds);
        $srch = new SearchBase(Afile::DB_TBL);
        $srch->addMultipleFields(['file_record_id', 'file_id', 'file_name']);
        $srch->addCondition('file_type', '=', Afile::TYPE_MESSAGE_ATTACHMENT);
        $srch->addDirectCondition('file_record_id IN (' . implode(',', $msgIds) . ')');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'file_record_id');
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
