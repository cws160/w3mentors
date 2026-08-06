<?php

/**
 * Admin Class is used to handle Sent Email
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SentEmail extends MyAppModel
{

    const DB_TBL = 'tbl_email_archives';
    const DB_TBL_PREFIX = 'earch_';

    /**
     * Initialize Sent Email
     * 
     * @param int $adminId
     */
    public function __construct(int $adminId = 0)
    {
        parent::__construct(static::DB_TBL, 'earch_id', $adminId);
        $this->objMainTableRecord->setSensitiveFields(['']);
    }

    /**
     * Get Search Object
     * 
     * @return SearchBase
     */
    public function getSearchObject(): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'm');
        $srch->addOrder('m.earch_added', 'DESC');
        return $srch;
    }

}
