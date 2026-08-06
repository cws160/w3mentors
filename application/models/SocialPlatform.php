<?php

/**
 * This class is used to handle Social Platforms
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SocialPlatform extends MyAppModel
{

    const DB_TBL = 'tbl_social_platforms';
    const DB_TBL_PREFIX = 'splatform_';

    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'splatform_id', $id);
    }

    /**
     * Get All Social Platform
     * 
     * @param bool $active
     * @return array
     */
    public static function getAll(bool $active = true): array
    {
        $srch = new SearchBase(static::DB_TBL);
        if ($active) {
            $srch->addCondition('splatform_active', '=', AppConstant::YES);
        }
        $srch->addMultipleFields(['LOWER(splatform_identifier) as name', 'splatform_url as url']);
        $srch->addOrder('splatform_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

}
