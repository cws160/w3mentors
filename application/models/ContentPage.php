<?php

/**
 * This class is used to handle Content Pages
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class ContentPage extends MyAppModel
{

    const DB_TBL = 'tbl_content_pages';
    const DB_TBL_PREFIX = 'cpage_';
    const DB_TBL_LANG = 'tbl_content_pages_lang';
    const DB_TBL_LANG_PREFIX = 'cpagelang_';
    const DB_TBL_CONTENT_PAGES_BLOCK_LANG = 'tbl_content_pages_block_lang';
    const DB_TBL_CONTENT_PAGES_BLOCK_LANG_PREFIX = 'cpblocklang_';
    const CONTENT_PAGE_LAYOUT1_TYPE = 1;
    const CONTENT_PAGE_LAYOUT2_TYPE = 2;
    const CONTENT_PAGE_LAYOUT1_BLOCK_COUNT = 2;
    const CONTENT_PAGE_LAYOUT1_BLOCK_1 = 1;
    const CONTENT_PAGE_LAYOUT1_BLOCK_2 = 2;
    const CONTENT_PAGE_LAYOUT1_BLOCK_3 = 3;
    const CONTENT_PAGE_LAYOUT1_BLOCK_4 = 4;
    const CONTENT_PAGE_LAYOUT1_BLOCK_5 = 5;

    /**
     * Initialize Content Page
     * 
     * @param int $epageId
     */
    public function __construct(int $epageId = 0)
    {
        parent::__construct(static::DB_TBL, 'cpage_id', $epageId);
    }

    /**
     * Get All Attributes By Id
     * 
     * @param int $cPageId
     * @param int $langId
     * @return boolean
     */
    public static function getAllAttributesById(int $cPageId = 0, int $langId = 0)
    {
        $cPageData = static::getAttributesById($cPageId);
        if ($cPageData == false) {
            return false;
        }
        if ($langId > 0) {
            $cPageLangData = static::getAttributesByLangId($langId, $cPageId);
            if ($cPageLangData == false) {
                return $cPageData;
            }
            return array_merge($cPageData, $cPageLangData);
        }
        return array_merge($cPageData);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'p');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'p_l.cpagelang_cpage_id = p.cpage_id and p_l.cpagelang_lang_id = ' . $langId, 'p_l');
        }
        $srch->addCondition('p.cpage_deleted', '=', 0);
        return $srch;
    }

    /**
     * Get Listing Obj
     * 
     * @param int $langId
     * @param string|array $attr
     * @return SearchBase
     */
    public static function getListingObj(int $langId, $attr = null): SearchBase
    {
        $srch = self::getSearchObject($langId);
        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $srch->addMultipleFields(['IFNULL(p_l.cpage_title,p.cpage_identifier) as cpage_title']);
        return $srch;
    }

    /**
     * Get Pages For Select Box
     * 
     * @param int $langId
     * @param int $ignoreCpageId
     * @return array
     */
    public static function getPagesForSelectBox(int $langId, int $ignoreCpageId = 0): array
    {
        $langId = FatUtility::int($langId);
        $ignoreCpageId = FatUtility::int($ignoreCpageId);
        $srch = static::getSearchObject($langId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(['cpage_id', 'IFNULL(cpage_title, cpage_identifier) as cpage_title']);
        if ($ignoreCpageId > 0) {
            $srch->addCondition('cpage_id', '!=', $ignoreCpageId);
        }
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Can Mark Record Deleted
     * 
     * @param int $id
     * @return bool
     */
    public function canRecordMarkDelete(int $id): bool
    {
        $srch = static::getSearchObject();
        $srch->addCondition('p.cpage_id', '=', $id);
        $srch->addFld('p.cpage_id');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($row) && $row['cpage_id'] == $id) {
            return true;
        }
        return false;
    }

    /**
     * Check Record Not Deleted

     * @param int $id
     * @return bool
     */
    public static function isNotDeleted(int $id): bool
    {
        $srch = static::getSearchObject();
        $srch->addCondition('p.cpage_id', '=', $id);
        $srch->addFld('p.cpage_id');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($row) && $row['cpage_id'] == $id) {
            return true;
        }
        return false;
    }

    /**
     * Add Update Content Page Blocks
     * 
     * @param int $langId
     * @param int $cpageId
     * @param array $data
     * @return boolean
     */
    public function addUpdateContentPageBlocks(int $langId, int $cpageId, array $data)
    {
        FatApp::getDb()->startTransaction();
        $assignValues = [
            'cpblocklang_lang_id' => $langId,
            'cpblocklang_cpage_id' => $cpageId,
            'cpblocklang_block_id' => $data['cpblocklang_block_id'],
            'cpblocklang_text' => $data['cpblocklang_text'],
        ];
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_CONTENT_PAGES_BLOCK_LANG, $assignValues, '', [], $assignValues)) {
            $this->error = $this->db->getError();
            FatApp::getDb()->rollbackTransaction();
            return false;
        }
        FatApp::getDb()->commitTransaction();
        return true;
    }

    public static function getPageBlocksContent(int $pageId, int $langId)
    {
        $srch = new searchBase(ContentPage::DB_TBL_CONTENT_PAGES_BLOCK_LANG);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(["cpblocklang_text", 'cpblocklang_block_id']);
        $srch->addCondition('cpblocklang_cpage_id', '=', $pageId);
        $srch->addCondition('cpblocklang_lang_id', '=', $langId);
        $srchRs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($srchRs, 'cpblocklang_block_id');
    }
}
