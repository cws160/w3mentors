<?php

/**
 * This class is used to handle reward point Search
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminEarningSearch extends SearchBased
{

    /**
     * Initialize Admin Transaction Search
     * 
     */
    public function __construct()
    {
        parent::__construct('tbl_admin_transactions', 'admtxn');
        $this->doNotCalculateRecords();
    }

    /**
     * Apply Search Conditions
     * 
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (isset($post['admtxn_record_type']) && $post['admtxn_record_type'] !== '') {
            $this->addCondition('admtxn.admtxn_record_type', '=', $post['admtxn_record_type']);
        }
        if (!empty($post['admtxn_date_from'])) {
            $start = $post['admtxn_date_from'] . ' 00:00:00';
            $this->addCondition('admtxn_datetime', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['admtxn_date_to'])) {
            $end = $post['admtxn_date_to'] . ' 23:59:59';
            $this->addCondition('admtxn_datetime', '<=', MyDate::formatToSystemTimezone($end));
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
        foreach ($rows as $key => $row) {
            $row['admtxn_datetime'] = MyDate::formatDate($row['admtxn_datetime']);
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
            'admtxn.admtxn_id' => 'admtxn_id',
            'admtxn.admtxn_amount' => 'admtxn_amount',
            'admtxn.admtxn_record_id' => 'admtxn_record_id',
            'admtxn.admtxn_record_type' => 'admtxn_record_type',
            'admtxn.admtxn_comment' => 'admtxn_comment',
            'admtxn.admtxn_datetime' => 'admtxn_datetime'
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
