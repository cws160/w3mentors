<?php

/**
 * This class is used to handle Themes
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Theme extends MyAppModel
{

    const DB_TBL = 'tbl_themes';
    const DB_TBL_PREFIX = 'theme_';

    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'theme_id', $id);
    }

    public static function getActive()
    {
        $srch = new SearchBase(static::DB_TBL);
        $id = FatApp::getConfig('CONF_ACTIVE_THEME');
        $srch->addCondition('theme_id', '=', $id);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

}
