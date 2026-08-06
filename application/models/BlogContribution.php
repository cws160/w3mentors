<?php

/**
 * This class is used to handle Blog Comments
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class BlogContribution extends MyAppModel
{

    const DB_TBL = 'tbl_blog_contributions';
    const DB_TBL_PREFIX = 'bcontributions_';

    /**
     * Initialize Blog Contribution
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'bcontributions_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @return SearchBase
     */
    public static function getSearchObject(): SearchBase
    {
        return new SearchBased(static::DB_TBL);
    }

}
