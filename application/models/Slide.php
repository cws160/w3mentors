<?php

/**
 * This class is used to handle Rating Slides
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Slide extends MyAppModel
{

    const DB_TBL = 'tbl_slides';
    const DB_TBL_PREFIX = 'slide_';

    /**
     * Initialize Slides
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'slide_id', $id);
    }

    /**
     * Get Displays Array
     * 
     * @param int $key
     * @return string|array
     */
    public static function getDisplaysArr(int $key = null)
    {
        $arr = [
            Afile::TYPE_HOME_BANNER_DESKTOP => Label::getLabel('LBL_DESKTOP'),
            /* Afile::TYPE_HOME_BANNER_MOBILE => Label::getLabel('LBL_MOBILE'),
            Afile::TYPE_HOME_BANNER_IPAD => Label::getLabel('LBL_IPAD') */
        ];
        return AppConstant::returArrValue($arr, $key);
    }

      /**
     * Get Slides
     * 
     * @return array
     */
    public static function getSlides(): array
    {
        $srch = new SearchBase(static::DB_TBL, 'sl');
        $srch->addMultipleFields(['slide_id', 'slide_record_id', 'slide_identifier']);
        $srch->addCondition('sl.slide_active', '=', AppConstant::ACTIVE);
        $srch->addOrder('slide_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(FatApp::getConfig('CONF_TOTAL_SLIDES_HOME_PAGE'));
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'slide_id');
    }

     /**
     */
    public static function getSlideImages(array $slideIds, int $langId)
    {
        if (empty($slideIds)) {
            return [];
        }
        $srch = new SearchBase(Afile::DB_TBL);
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'slide_id = file_record_id');
        $srch->doNotCalculateRecords();
        $srch->addCondition('file_type', 'IN', array_keys(Slide::getDisplaysArr()));
        $srch->addCondition('file_lang_id', 'IN', [$langId, 0]);
        $srch->addCondition('file_record_id', 'IN', $slideIds);
        $srch->addCondition('file_path', '!=', '');
        $srch->addOrder('slide_order', 'ASC');
        $srch->addOrder('slide_id', 'ASC');
        $resultSet = $srch->getResultSet();
        $data = [];
        while ($record = FatApp::getDb()->fetch($resultSet)) {
            $data[$record['file_record_id']][$record['file_type']] = $record;
        }
        return $data;
    }

}
