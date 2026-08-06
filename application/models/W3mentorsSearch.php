<?php

/**
 * This is a base Search Model
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class W3mentorsSearch extends SearchBased
{

    public $table;
    public $alias;
    protected $langId;
    protected $userId;
    protected $userType;

    /**
     * Initialize W3Mentors Search
     * 
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId = 0, int $userId = 0, int $userType = 0)
    {
        /**
         * @todo need to change the lang id check 
         */
        $this->langId = $langId;
        if ($this->langId < 1) {
            $this->langId = MyUtility::getSystemLanguage()['language_id'];
        }
        $this->userId = $userId;
        $this->userType = $userType;
        parent::__construct($this->table, $this->alias);
        $this->doNotCalculateRecords();
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
     * Add Search Detail Fields
     * 
     * @return void
     */
    public function addSearchDetailFields(): void
    {
        $fields = static::getDetailFields();
        foreach ($fields as $field => $alias) {
            $this->addFld($field . ' AS ' . $alias);
        }
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        
    }

    /**
     * Apply Search Conditions
     * 
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        
    }

    /**
     * Get Listing Fields
     * 
     * @return array
     */
    public static function getListingFields(): array
    {
        return [];
    }

    /**
     * Get Detail Fields
     * 
     * @return array
     */
    public static function getDetailFields(): array
    {
        return [];
    }

    /**
     * Fetch And Format
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        
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
