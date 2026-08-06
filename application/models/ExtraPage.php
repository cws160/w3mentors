<?php

/**
 * This class is used to handle Extra Page
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class ExtraPage extends MyAppModel
{

    const DB_TBL = 'tbl_extra_pages';
    const DB_TBL_PREFIX = 'epage_';
    const DB_TBL_LANG = 'tbl_extra_pages_lang';
    const DB_TBL_LANG_PREFIX = 'epagelang_';
    
    /* User profile */
    const BLOCK_PROFILE_INFO_BAR = 1;
    /* Contact us */
    const BLOCK_CONTACT_BANNER_SECTION = 4;
    const BLOCK_CONTACT_LEFT_SECTION = 5;
    const BLOCK_APPLY_TO_TEACH_FAQ_CONTACT = 26;
    /* Apply to teach */
    const BLOCK_APPLY_TO_TEACH_BENEFITS_SECTION = 6;
    const BLOCK_APPLY_TO_TEACH_FEATURES_SECTION = 7;
    const BLOCK_APPLY_TO_TEACH_BECOME_A_TUTOR_SECTION = 8;
    const BLOCK_APPLY_TO_TEACH_STATIC_BANNER = 9;
    /* Home */
    const BLOCK_HOME_WHY_US = 2;
    const BLOCK_HOME_BROWSE_TUTOR = 3;
    const BLOCK_SERVICES_WE_OFFERING = 10;
    const BLOCK_HOME_CLASSES = 12;
    const BLOCK_HOME_ONLINE_COURSES = 13;
    const BLOCK_HOME_TOP_RATED_TEACHERS = 14;
    const BLOCK_HOME_TESTIMONIALS = 15;
    const BLOCK_HOME_LATEST_BLOGS = 16;
    const BLOCK_HOME_COURSE_CATEGORIES = 17;
    const BLOCK_HOME_HOW_TO_START_LEARNING = 18;
    const BLOCK_HOME_FEATURED_LANGUAGES = 20;
    const BLOCK_HOME_BROWSE_COURSES = 21;
    const BLOCK_HOME_CREATING_COMMUNITY = 22;
    const BLOCK_HOME_COURSE_WITH_CATEGORIES = 23;
    const BLOCK_HOME_JOIN_NOW_SECTION = 24;
    const BLOCK_HOME_SUBSCRIPTION_PLANS = 25;
    /* Affiliate */
    const BLOCK_AFFILIATE_REGISTRATION_BANNER = 19;
    
    const TYPE_HOMEPAGE = 1;
    const TYPE_APPLY_TO_TEACH = 2;
    const TYPE_CONTACT_US = 3;
    const TYPE_AVAILABILITY = 5;
    const TYPE_AFFLIATE_REGISTRATION = 6;

    private $pageType;

    /**
     * Initialize Extra Page
     * 
     * @param int $epageId
     * @param type $pageType
     */
    public function __construct(int $epageId = 0, $pageType = '')
    {
        $this->pageType = $pageType;
        parent::__construct(static::DB_TBL, 'epage_id', $epageId);
    }

    public static function getSearchObject($langId = 0, $isActive = true)
    {
        $srch = new SearchBase(static::DB_TBL, 'ep');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'ep_l.epagelang_epage_id = ep.epage_id and ep_l.epagelang_lang_id = ' . $langId, 'ep_l');
        }
        if ($isActive) {
            $srch->addCondition('epage_active', '=', AppConstant::ACTIVE);
        }
        return $srch;
    }

    /**
     * Get Block Content
     * 
     * @param int $pageType
     * @param int $langId
     * @return string
     */
    public static function getBlockContent(int $pageType, int $langId): string
    {
        $srch = self::getSearchObject($langId);
        $srch->addCondition('ep.epage_block_type', '=', $pageType);
        $srch->addMultipleFields([
            'epage_id', 'IFNULL(epage_label, epage_identifier) as epage_label',
            'epage_type', 'epage_block_type', 'IFNULL(epage_content,"") as epage_content', 'epage_default_content'
        ]);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $resultData = FatApp::getDb()->fetch($rs);
        if (empty($resultData['epage_content'])) {
            return "";
        }
        return $resultData['epage_content'];
    }


    /**
     * Get Block Content
     * 
     * @param int $pageType
     * @param int $langId
     * @return array
     */
    public static function getPageBlocks(int $type, int $langId): array
    {
        $srch = self::getSearchObject($langId);
        $srch->addCondition('ep.epage_type', '=', $type);
        $srch->addMultipleFields([
            'epage_id', 'IFNULL(epage_label, epage_identifier) as epage_label',
            'epage_type', 'epage_block_type', 'epage_editable', 'IFNULL(epage_content,"") as epage_content', 'epage_default_content'
        ]);
        $srch->addOrder('epage_order');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'epage_block_type');
    }


    /**
     * Update Order
     * 
     * @param array $order
     * @return bool
     */
    public function updateOrder(array $order): bool
    {
        if (empty($order)) {
            return false;
        }
        foreach ($order as $i => $id) {
            if (FatUtility::int($id) < 1) {
                continue;
            }
            if (!FatApp::getDb()->updateFromArray(static::DB_TBL,
                            [static::DB_TBL_PREFIX . 'order' => $i],
                            ['smt' => static::DB_TBL_PREFIX . 'id = ?', 'vals' => [$id]])) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        return true;
    }


    /**
     * Get Block Types
     * 
     * @param string $key
     * @return string|array
     */
    public static function getTypes(string $key = null) 
    {
        $arr = [
            static::TYPE_HOMEPAGE => Label::getLabel('LBL_HOMEPAGE'),
            static::TYPE_APPLY_TO_TEACH => Label::getLabel('LBL_APPLY_TO_TEACH'),
            static::TYPE_CONTACT_US => Label::getLabel('LBL_CONTACT_US'),
            static::TYPE_AVAILABILITY => Label::getLabel('LBL_AVAILABILITY'),
            static::TYPE_AFFLIATE_REGISTRATION => Label::getLabel('LBL_AFFILIATE_REGISTRATION'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }


}
