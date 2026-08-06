<?php

/**
 * This class is used to handle Course resources
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ResourceSearch extends W3mentorsSearch
{

    /**
     * Initialize Course Resources
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = Resource::DB_TBL;
        $this->alias = 'resrc';
        
        parent::__construct($langId, $userId, $userType);
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
     * Get Listing FFields
     *
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'resrc.resrc_id' => 'resrc_id',
            'resrc.resrc_type' => 'resrc_type',
            'resrc.resrc_size' => 'resrc_size',
            'resrc.resrc_name' => 'resrc_name',
            'resrc.resrc_path' => 'resrc_path',
            'resrc.resrc_created' => 'resrc_created',
        ];
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (isset($post['user_id']) && $post['user_id'] > 0) {
            $this->addCondition('resrc.resrc_user_id', '=', $post['user_id']);
        }
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $this->addCondition('resrc.resrc_name', 'LIKE', '%' . $keyword . '%');
        }
    }

    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('resrc.resrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
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
            $rows[$key]['resrc_created'] = MyDate::convert($row['resrc_created']);
        }
        return $rows;
    }
}
