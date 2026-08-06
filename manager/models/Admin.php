<?php

/**
 * Admin Class is used to handle Admin things
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Admin extends MyAppModel
{

    const DB_TBL = 'tbl_admin';
    const DB_TBL_PREFIX = 'admin_';

    /**
     * Initialize Admin
     * 
     * @param int $userId
     */
    public function __construct(int $userId = 0)
    {
        parent::__construct(static:: DB_TBL, 'admin_id', $userId);
    }

}
