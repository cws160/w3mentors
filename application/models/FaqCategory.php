<?php

/**
 * This class is used to handle FAQs Category
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class FaqCategory extends MyAppModel
{

    const DB_TBL = 'tbl_faq_categories';
    const DB_LANG_TBL = 'tbl_faq_categories_lang';
    const DB_TBL_PREFIX = 'faqcat_';
    const DB_TBL_LANG_PREFIX = 'faqcatlang_';
    const FAQ_PAGE = 0;
    const SELLER_PAGE = 1;

    /**
     * Initialize FaqCategory
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'faqcat_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $isDeleted
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0, bool $isDeleted = true): SearchBase
    {
        $srch = new SearchBased(static::DB_TBL, 'fc');
        if ($isDeleted == true) {
            $srch->addCondition('fc.faqcat_deleted', '=', AppConstant::NO);
        }
        if ($langId > 0) {
            $srch->joinTable(static::DB_LANG_TBL, 'LEFT JOIN', 'fc_l.faqcatlang_faqcat_id = fc.faqcat_id and fc_l.faqcatlang_lang_id = ' . $langId, 'fc_l');
        }
        $srch->addOrder('fc.faqcat_active', 'DESC');
        return $srch;
    }

    /**
     * Get Category StructureF
     * 
     * @return type
     */
    public function getCategoryStructure()
    {
        $srch = static::getSearchObject();
        $srch->addCondition('fc.faqcat_deleted', '=', AppConstant::NO);
        $srch->addCondition('fc.faqcat_active', '=', AppConstant::YES);
        $srch->addOrder('fc.faqcat_order', 'asc');
        $srch->addOrder('fc.faqcat_identifier', 'asc');
        $categories = FatApp::getDb()->fetchAll($srch->getResultSet(), 'faqcat_id');
        sort($categories);
        return $categories;
    }

    /**
     * Get Max Order
     * 
     * @return int
     */
    public function getMaxOrder(): int
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addFld("MAX(faqcat_order) as max_order");
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($record)) {
            return $record['max_order'] + 1;
        }
        return 1;
    }

}
