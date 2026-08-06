<?php

/**
 * This class is used to handle Blog Post Category
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class BlogPostCategory extends MyAppModel
{

    const DB_TBL = 'tbl_blog_post_categories';
    const DB_TBL_PREFIX = 'bpcategory_';
    const DB_TBL_LANG = 'tbl_blog_post_categories_lang';
    const DB_LANG_TBL_PREFIX = 'bpcategorylang_';
    const REWRITE_URL_PREFIX = 'blog/category/';

    /**
     * Initialize Blog Post Category
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'bpcategory_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param bool $includeChildCount
     * @param int $langId
     * @param bool $bpcategory_active
     * @return SearchBase
     */
    public static function getSearchObject(bool $includeChildCount = false, int $langId = 0, bool $bpcategory_active = true): SearchBase
    {
        $langId = FatUtility::int($langId);
        $srch = new SearchBased(static::DB_TBL, 'bpc');
        $srch->addOrder('bpc.bpcategory_active', 'DESC');
        if ($includeChildCount) {
            $childSrchbase = new SearchBase(static::DB_TBL);
            $childSrchbase->addCondition('bpcategory_deleted', '=', 0);
            $childSrchbase->doNotCalculateRecords();
            $childSrchbase->doNotLimitRecords();
            $srch->joinTable('(' . $childSrchbase->getQuery() . ')', 'LEFT OUTER JOIN', 's.bpcategory_parent = bpc.bpcategory_id', 's');
            $srch->addGroupBy('bpc.bpcategory_id');
            $srch->addFld('COUNT(s.bpcategory_id) AS child_count');
        }
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'bpc_l.bpcategorylang_bpcategory_id = bpc.bpcategory_id and bpc_l.bpcategorylang_lang_id = ' . $langId, 'bpc_l');
        }
        if ($bpcategory_active) {
            $srch->addCondition('bpc.bpcategory_active', '=', AppConstant::ACTIVE);
        }
        $srch->addCondition('bpc.bpcategory_deleted', '=', AppConstant::NO);
        return $srch;
    }

    /**
     * Get Max Order
     * 
     * @param int $parent
     * @return int
     */
    public function getMaxOrder(int $parent = 0): int
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addFld("MAX(bpcategory_order) as max_order");
        if ($parent > 0) {
            $srch->addCondition('bpcategory_parent', '=', $parent);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);
        if (!empty($record)) {
            return $record['max_order'] + 1;
        }
        return 1;
    }

    /**
     * Get Category Structure
     * 
     * @param int $bpcategory_id
     * @param array $category_tree_array
     * @return array
     */
    public function getCategoryStructure(int $bpcategory_id, array $category_tree_array = [])
    {
        $srch = static::getSearchObject();
        $srch->addCondition('bpc.bpcategory_deleted', '=', AppConstant::NO);
        $srch->addCondition('bpc.bpcategory_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('bpc.bpcategory_id', '=', $bpcategory_id);
        $srch->addOrder('bpc.bpcategory_order', 'asc');
        $srch->addOrder('bpc.bpcategory_identifier', 'asc');
        $rs = $srch->getResultSet();
        while ($categories = FatApp::getDb()->fetch($rs)) {
            $category_tree_array[] = $categories;
            $category_tree_array = $this->getCategoryStructure($categories['bpcategory_parent'], $category_tree_array);
        }
        sort($category_tree_array);
        return $category_tree_array;
    }

    /**
     * Add Update Blog Post Category Lang
     * 
     * @param array $data
     * @param int $lang_id
     * @param int $bpcategory_id
     * @return bool|int
     */
    public function addUpdateBlogPostCatLang(array $data, int $lang_id, int $bpcategory_id)
    {
        $tbl = new TableRecord(static::DB_TBL_LANG);
        $data['bpcategorylang_bpcategory_id'] = FatUtility::int($bpcategory_id);
        $tbl->assignValues($data);
        if ($this->isExistBlogPostCatLang($lang_id, $bpcategory_id)) {
            if (!$tbl->update(['smt' => 'bpcategorylang_bpcategory_id = ? and bpcategorylang_lang_id = ? ', 'vals' => [$bpcategory_id, $lang_id]])) {
                $this->error = $tbl->getError();
                return false;
            }
            return $bpcategory_id;
        }
        if (!$tbl->addNew()) {
            $this->error = $tbl->getError();
            return false;
        }
        return true;
    }

    /**
     * Exist Blog Post Category Language
     * 
     * @param int $lang_id
     * @param int $bpcategory_id
     * @return bool
     */
    public function isExistBlogPostCatLang(int $lang_id, int $bpcategory_id): bool
    {
        $srch = new SearchBase(static::DB_TBL_LANG);
        $srch->addCondition('bpcategorylang_bpcategory_id', '=', $bpcategory_id);
        $srch->addCondition('bpcategorylang_lang_id', '=', $lang_id);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($row)) {
            return true;
        }
        return false;
    }

    /**
     * Get Categories For Select Box
     * 
     * @param int $langId
     * @param int $catId
     * @return array
     */
    public function getForSelectBox(int $langId, int $catId = 0)
    {
        $cats = [];
        $rows = static::getParentChilds($langId);
        foreach ($rows as $row) {
            if ($catId == $row['bpcategory_id']) {
                continue;
            }
            $cats[$row['bpcategory_id']] = $row['bpcategory_name'];
        }
        return $cats;
    }

    /**
     * Get Blog Post Category Tree Structure
     * 
     * @param int $parent_id
     * @param string $keywords
     * @param int $level
     * @param string $name_prefix
     * @return array
     */
    public function getBlogPostCatTreeStructure(int $parent_id = 0, string $keywords = '', int $level = 0, string $name_prefix = ''): array
    {
        $srch = static::getSearchObject(false, MyUtility::getSiteLangId());
        $srch->addFld('bpc.bpcategory_id, IFNULL(bpc_l.bpcategory_name, bpc.bpcategory_identifier) as bpcategory_identifier');
        $srch->addCondition('bpc.bpcategory_deleted', '=', AppConstant::NO);
        $srch->addCondition('bpc.bpcategory_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('bpc.bpcategory_parent', '=', FatUtility::int($parent_id));
        if (!empty($keywords)) {
            $srch->addCondition('bpc.bpcategory_identifier', 'like', '%' . $keywords . '%');
        }
        $srch->addOrder('bpc.bpcategory_order', 'asc');
        $srch->addOrder('bpcategory_identifier', 'asc');
        $records = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        $return = [];
        $seprator = '';
        if ($level > 0) {
            $seprator = '&nbsp;&nbsp;&raquo;&raquo;&nbsp;&nbsp;';
            $seprator = CommonHelper::renderHtml($seprator);
        }
        foreach ($records as $bpcategory_id => $bpcategory_identifier) {
            $name = $name_prefix . $seprator . $bpcategory_identifier;
            $return[$bpcategory_id] = $name;
            $return += $this->getBlogPostCatTreeStructure($bpcategory_id, $keywords, $level + 1, $name);
        }
        return $return;
    }

    /**
     * Get Blog Post Category Parent Child Wise Arr
     * 
     * @param int $langId
     * @param int $parentId
     * @param bool $includeChildCat
     * @param bool $forSelectBox
     * @return type
     */
    public static function getParentChilds(int $langId, int $parentId = 0)
    {
        $srch = new SearchBase(BlogPostCategory::DB_TBL, 'bpc');
        $srch->joinTable(BlogPostCategory::DB_TBL_LANG, 'LEFT JOIN', 'bpcategorylang_bpcategory_id = bpc.bpcategory_id AND bpcategorylang_lang_id = ' . $langId, 'bpc_l');
        $srch->addMultipleFields(['bpcategory_id', 'IFNULL(bpcategory_name, bpcategory_identifier) as bpcategory_name']);
        $srch->addCondition('bpcategory_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('bpcategory_deleted', '=', AppConstant::NO);
        $srch->addCondition('bpcategory_parent', '=', $parentId);
        $srch->addOrder('bpcategory_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($rows as &$row) {
            $row['children'] = self::getParentChilds($langId, $row['bpcategory_id']);
            $row['countChildBlogPosts'] = BlogPost::getPostsCount($langId, $row['bpcategory_id'], $row['children'], $row['children']);
        }
        return $rows;
    }

    public static function getCategoryName(int $langId, int $catId)
    {
        $srch = new SearchBase(BlogPostCategory::DB_TBL, 'bpc');
        $srch->joinTable(BlogPostCategory::DB_TBL_LANG, 'LEFT JOIN', 'bpcategorylang_bpcategory_id = bpc.bpcategory_id AND bpcategorylang_lang_id = ' . $langId, 'bpc_l');
        $srch->addMultipleFields(['bpcategory_parent', 'IFNULL(bpcategory_name, bpcategory_identifier) as bpcategory_name']);
        $srch->addCondition('bpcategory_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('bpcategory_deleted', '=', AppConstant::NO);
        $srch->addCondition('bpcategory_id', '=', $catId);
        $srch->addOrder('bpcategory_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($row['bpcategory_parent'] ?? '')) {
            return static::getCategoryName($langId, $row['bpcategory_parent']) . ' » ' . $row['bpcategory_name'];
        }
        return $row['bpcategory_name'] ?? '';
    }

    public static function getRootCategoryId(int $catId)
    {
        $parent = static::getAttributesById($catId, 'bpcategory_parent');
        return empty($parent) ? $catId : $parent;
    }

    public static function getSubIds(int $categoryId)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('bpcategory_deleted', '=', AppConstant::NO);
        $srch->addCondition('bpcategory_parent', '=', $categoryId);
        $srch->addFld('bpcategory_id');
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        return array_column($rows, 'bpcategory_id');
    }

}
