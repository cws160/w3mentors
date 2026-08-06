<?php

/**
 * This class is used to handle Faq
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Faq extends MyAppModel
{

    const DB_TBL = 'tbl_faq';
    const DB_TBL_PREFIX = 'faq_';
    const DB_TBL_LANG = 'tbl_faq_lang';
    const CATEGORY_GENERAL_QUERIES = 1;
    const CATEGORY_APPLICATION = 2;
    const CATEGORY_PAYMENTS = 3;
    const CATEGORY_APPLY_TO_TEACH = 4;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, 'faq_id', $id);
    }

    /**
     * Get FAQ Category Array
     * 
     * @param int $langid
     * @return array
     */
    public static function getFaqCategoryArr(int $langid) : array
    {
        $srch = FaqCategory::getSearchObject($langid);
        $srch->addCondition('faqcat_active', '=', AppConstant::ACTIVE);
        $srch->addOrder('faqcat_order', 'ASC');
        $catList = FatApp::getDb()->fetchAll($srch->getResultSet());
        $catOptions = [];
        if (!empty($catList)) {
            foreach ($catList as $cat) {
                if (isset($cat['faqcat_name']) && $cat['faqcat_name'] != '') {
                    $name = $cat['faqcat_name'];
                } else {
                    $name = $cat['faqcat_identifier'];
                }
                $catOptions[$cat['faqcat_id']] = $name;
            }
        }
        return $catOptions;
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $active
     * @return SearchBase
     */
    public static function getSearchObject(int $langId, bool $active = true): SearchBase
    {
        $srch = new SearchBased(static::DB_TBL, 't');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 't_l.faqlang_faq_id = t.faq_id AND faqlang_lang_id = ' . $langId, 't_l');
        }
        if ($active == true) {
            $srch->addCondition('t.faq_active', '=', AppConstant::ACTIVE);
        }
        return $srch;
    }

}
