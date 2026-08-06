<?php

/**
 * This class is used to handle Reward Point Search
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class RewardPointSearch extends SearchBased
{

    protected $userId;

    /**
     * Initialize Reward Point Search
     * 
     * @param int $userId
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
        parent::__construct(RewardPoint::DB_TBL, 'repnt');
        $this->doNotCalculateRecords();
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('repnt.repnt_user_id', '=', $this->userId);
    }

    /**
     * Apply Search Conditions
     * 
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        $comment = trim($post['repnt_comment'] ?? '');
        if (!empty($comment)) {
            $this->addCondition('repnt.repnt_comment', 'LIKE', '%' . $comment . '%');
        }
        if (!empty($post['repnt_type'])) {
            $this->addCondition('repnt.repnt_type', '=', $post['repnt_type']);
        }
    }

    /**
     * Fetch And Format
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($rows) == 0) {
            return [];
        }
        $types = RewardPoint::getTypes();
        foreach ($rows as $key => $row) {
            $row['repnt_label'] = $types[$row['repnt_type']];
            $row['repnt_datetime'] = MyDate::convert($row['repnt_datetime']);
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Add Search Listing Fields
     * 
     * @return void
     */
    public function addSearchListingFields(): void
    {
        $fields = static::getListingFields();
        foreach ($fields as $field => $alias) {
            $this->addFld($field . ' AS ' . $alias);
        }
    }

    /**
     * Get Listing Fields
     * 
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'repnt.repnt_id' => 'repnt_id',
            'repnt.repnt_type' => 'repnt_type',
            'repnt.repnt_points' => 'repnt_points',
            'repnt.repnt_comment' => 'repnt_comment',
            'repnt.repnt_datetime' => 'repnt_datetime'
        ];
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
