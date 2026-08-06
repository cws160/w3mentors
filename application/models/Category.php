<?php

class Category extends MyAppModel
{
    public const DB_TBL = 'tbl_categories';
    public const DB_TBL_PREFIX = 'cate_';
    public const DB_LANG_TBL = 'tbl_categories_lang';
    public const DB_LANG_TBL_PREFIX = 'catelang_';

    public const TYPE_COURSE = 1;
    public const TYPE_QUESTION = 2;

    private $langId;

    /**
     * Initialize Categories
     *
     * @param int $id
     * @param int $langId
     */
    public function __construct(int $id = 0, int $langId = 0)
    {
        parent::__construct(static::DB_TBL, 'cate_id', $id);
        $this->langId = $langId;
    }

    /**
     * Get Categories Types
     *
     * @param int $key
     * @return string|array
     */
    public static function getCategoriesTypes(int $key = null)
    {
        $arr = [
            static::TYPE_COURSE => Label::getLabel('LBL_COURSE'),
            static::TYPE_QUESTION => Label::getLabel('LBL_QUESTION')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Add/Edit Category
     *
     * @param array $data
     * @return bool
     */
    public function setup($data): bool
    {
        if ($this->mainTableRecordId > 0 && !$category = $this->getDataById()) {
            $this->error = Label::getLabel('LBL_CATEGORY_NOT_FOUND');
            return false;
        }
        $type = ($this->mainTableRecordId > 0) ? $category['cate_type'] : $data['cate_type'];
        $parent = FatUtility::int($data['cate_parent']);
        if (!$this->checkUnique($data['cate_identifier'], $type, $parent)) {
            $this->error = $this->getError();
            return false;
        }
        if ($this->mainTableRecordId > 0) {
            if ($data['cate_status'] == AppConstant::INACTIVE && $category['cate_records'] > 0) {
                if ($category['cate_type'] == Category::TYPE_QUESTION) {
                    $this->error = Label::getLabel('LBL_CATEGORIES_ATTACHED_WITH_THE_QUESTIONS_CANNOT_BE_MARKED_INACTIVE');
                }
                if ($category['cate_type'] == Category::TYPE_COURSE) {
                    $this->error = Label::getLabel('LBL_CATEGORIES_ATTACHED_WITH_THE_COURSES_CANNOT_BE_MARKED_INACTIVE');
                }
                return false;
            }
            unset($data['cate_type']);
        }

        /* save category data */
        $this->assignValues($data);
        if ($this->mainTableRecordId < 1) {
            $this->setFldValue('cate_created', date('Y-m-d H:i:s'));
        }
        $this->setFldValue('cate_updated', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        /* update sub categories count */
        if (!$this->updateSubCatCount()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Add/Edit Categories Lang Data
     *
     * @param array $data
     * @return bool
     */
    public function addUpdateLangData($data): bool
    {
        $assignValues = [
            'catelang_cate_id' => $this->getMainTableRecordId(),
            'catelang_lang_id' => $data['catelang_lang_id'],
            'cate_name' => $data['cate_name'],
            'cate_details' => $data['cate_details'],
            'catelang_id' => $data['catelang_id'],
        ];

        if (!FatApp::getDb()->insertFromArray(static::DB_LANG_TBL, $assignValues, false, [], $assignValues)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function updateStatus(): bool
    {
        if (!$data = $this->getDataById()) {
            $this->error = Label::getLabel('LBL_CATEGORY_NOT_FOUND');
            return false;
        }
        $status = $this->getFldValue('cate_status');
        if ($status == AppConstant::INACTIVE && $data['cate_records'] > 0) {
            if ($data['cate_type'] == Category::TYPE_COURSE) {
                $this->error = Label::getLabel('LBL_CATEGORIES_ATTACHED_WITH_THE_COURSES_CANNOT_BE_MARKED_INACTIVE');
            }
	   if ($data['cate_type'] == Category::TYPE_QUESTION) {
                $this->error = Label::getLabel('LBL_CATEGORIES_ATTACHED_WITH_THE_QUESTIONS_CANNOT_BE_MARKED_INACTIVE');
            }
            return false;
        }
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Delete
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$data = $this->getDataById()) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }

        if ($data['cate_records'] > 0) {
            if ($data['cate_type'] == Category::TYPE_COURSE) {
                $this->error = Label::getLabel('LBL_CATEGORIES_ATTACHED_WITH_THE_COURSES_CANNOT_BE_DELETED.');
            } elseif ($data['cate_type'] == Category::TYPE_QUESTION) {
                $this->error = Label::getLabel('LBL_CATEGORIES_ATTACHED_WITH_THE_QUESTIONS_CANNOT_BE_DELETED.');
            }
            return false;
        }
        if ($data['cate_subcategories'] > 0) {
            $this->error = Label::getLabel('LBL_CATEGORIES_ATTACHED_WITH_THE_SUBCATEGORIES_CANNOT_BE_DELETED.');
            return false;
        }
        $this->setFldValue('cate_deleted', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        /* update sub categories count */
        $this->updateSubCatCount();
        return true;
    }

    /**
     * Function to update sub categories count
     *
     * @return bool
     */
    private function updateSubCatCount(): bool
    {
        if (
            !FatApp::getDb()->query(
                "UPDATE `" . static::DB_TBL . "` cate 
                LEFT JOIN (
                    SELECT  COUNT(cate_id) AS cate_subcategories, cate_parent
                    FROM `" . static::DB_TBL . "` 
                    WHERE cate_deleted IS NULL
                    GROUP BY `cate_parent`
                ) c 
                ON cate.cate_id = c.cate_parent 
                SET cate.cate_subcategories = c.cate_subcategories"
            )
        ) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * get search base class object
     *
     * @return object
     */
    public static function getSearchObject()
    {
        return new SearchBased(self::DB_TBL, 'catg');
    }

    /**
     * Get category data by id
     *
     * @return array|bool
     */
    public function getDataById()
    {
        $srch = static::getSearchObject();
        $srch->addCondition('catg.cate_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('catg.cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addMultipleFields([
            'catg.cate_id', 'catg.cate_type', 'catg.cate_identifier', 'catg.cate_parent',
            'catg.cate_status', 'catg.cate_records', 'catg.cate_subcategories', 'catg.cate_featured'
        ]);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get all parent categories
     *
     * @param int  $langId
     * @param int  $catgId
     * @param int  $type
     * @param bool $havingCourses
     * @param bool $active
     * @return array
     */
    public static function getCategoriesByParentId(int $langId, int $catgId = 0, int $type = Category::TYPE_COURSE, bool $havingCourses = false, bool $active = true)
    {
        $srch = static::getSearchObject();
        $srch->joinTable(self::DB_LANG_TBL, 'LEFT OUTER JOIN', 'catg.cate_id = catg_l.catelang_cate_id AND catg_l.catelang_lang_id = ' . $langId, 'catg_l');
        $srch->addMultipleFields(['cate_id', 'IFNULL(cate_name, cate_identifier) AS cate_name']);
        $srch->addOrder('cate_order');
        $srch->addCondition('cate_parent', '=', $catgId);
        $srch->addCondition('cate_type', '=', $type);
        if ($active == true) {
            $srch->addCondition('cate_status', '=', AppConstant::ACTIVE);
        }
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        if ($havingCourses == true) {
            $srch->addCondition('cate_records', '>', 0);
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Function to get all categories with its sub categories
     *
     * @param int $type
     * @param int $langId
     * @return array
     */
    public static function getAll(int $type, int $langId)
    {
        /* get parent categories list */
        $parentCategories = static::getCategoriesByParentId($langId, 0, $type, true);
        if (count($parentCategories) < 1) {
            return [];
        }
        $list = [];
        foreach ($parentCategories as $catId => $category) {
            $list[$catId]['name'] = $category;
            $subCategories = static::getCategoriesByParentId($langId, $catId, $type, true);
            $subCat = [];
            if (count($subCategories) > 0) {
                foreach ($subCategories as $subCatId => $subCategory) {
                    $subCat[$subCatId] = $subCategory;
                }
            }
            $list[$catId]['sub_categories'] = $subCat;
        }
        return $list;
    }

    /**
     * Check unique category
     *
     * @param string $identifier
     * @param int    $type
     * @param int    $parent
     * @return bool
     */
    public function checkUnique(string $identifier, int $type, int $parent = 0)
    {
        $srch = new SearchBase(static::DB_TBL, 'catg');
        $srch->addCondition('mysql_func_LOWER(cate_identifier)', '=', strtolower(trim($identifier)), 'AND', true);
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('cate_parent', '=', $parent);
        $srch->addCondition('cate_type', '=', $type);
        if ($this->getMainTableRecordId() > 0) {
            $srch->addCondition('cate_id', '!=', $this->getMainTableRecordId());
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $category = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($category)) {
            $this->error = Label::getLabel('LBL_CATEGORY_NAME_ALREADY_IN_USE');
            return false;
        }
        return true;
    }

    /**
     * Get Names
     *
     * @param array $catgIds
     * @param int   $langId
     * @return array
     */
    public static function getNames(array $catgIds, int $langId): array
    {
        $catgIds = array_filter(array_unique($catgIds));
        if (empty($catgIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'cate');
        $srch->joinTable(static::DB_LANG_TBL, 'LEFT JOIN', 'catelang.catelang_cate_id = cate.cate_id and catelang.catelang_lang_id =' . $langId, 'catelang');
        $srch->addMultipleFields(['cate.cate_id', 'IFNULL(cate_name, cate_identifier) as cate_name']);
        $srch->addCondition('cate.cate_id', 'IN', $catgIds);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Update Questions Count In Categories
     *
     * @param array $cateIds
     * @return bool
     */
    public function updateCount(array $cateIds)
    {
        if (count($cateIds) < 1) {
            $this->error = Label::getLabel('LBL_INVALID_DATA_SENT_FOR_QUESTION_COUNT_UPDATE');
            return false;
        }
        $cateIds = array_filter($cateIds);
        $db = FatApp::getDb();
        if (
            !$db->query(
                "UPDATE " . Category::DB_TBL . "
                LEFT JOIN(
                    SELECT ques.ques_cate_id AS catId,
                        COUNT(*) AS totalRecord
                    FROM
                        " . Question::DB_TBL . " AS ques
                    WHERE 
                        ques.ques_cate_id IN (" . implode(',', $cateIds) . ") AND
                        ques.ques_deleted IS NULL
                    GROUP BY
                        ques.ques_cate_id 
                ) mainCat
                ON
                    mainCat.catId = " . Category::DB_TBL . ".cate_id
                LEFT JOIN(
                    SELECT
                        ques1.ques_subcate_id AS catId,
                        COUNT(*) AS totalRecord
                    FROM
                        " . Question::DB_TBL . " AS ques1
                    WHERE ques1.ques_subcate_id IN (" . implode(',', $cateIds) . ") AND
                        ques1.ques_deleted IS NULL
                    GROUP BY
                        ques1.ques_subcate_id
                ) catChild
                ON
                    catChild.catId = cate_id
                SET cate_records = (IFNULL(mainCat.totalRecord, 0) + IFNULL(catChild.totalRecord, 0)) 
                WHERE cate_id IN (" . implode(',', $cateIds) . ")"
            )
        ) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }


    public static function getTopCategories(int $langId)
    {
        $catObj = new SearchBase(Category::DB_TBL, 'cate');
        $catObj->joinTable(
            Category::DB_LANG_TBL,
            'LEFT OUTER JOIN',
            'cate.cate_id = cate_l.catelang_cate_id AND cate_l.catelang_lang_id = ' . $langId,
            'cate_l'
        );
        $catObj->joinTable(Course::DB_TBL, 'LEFT JOIN', 'course.course_cate_id = cate.cate_id', 'course');
        $catObj->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = teacher.user_id', 'teacher');
        $catObj->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'ordcrs');
        $catObj->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcrs.ordcrs_order_id', 'orders');
        $catObj->addMultipleFields(['cate.cate_id', 'COUNT(ordcrs.ordcrs_id) as order_count',
            'IFNULL(cate_l.cate_name, cate.cate_identifier) AS cate_name', 'cate.cate_records as course_count']);
        $catObj->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $catObj->addCondition('cate.cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $catObj->addCondition('cate.cate_status', '=', AppConstant::ACTIVE);
        $catObj->addCondition('order_payment_status', '=', Order::ISPAID);
        $catObj->addCondition('cate.cate_parent', '=', 0);
        $catObj->addOrder('order_count', 'DESC');
        $catObj->addOrder('cate_name', 'ASC');
        $catObj->addGroupBy('cate.cate_id');
        $catObj->setPageSize(10);
        $catObj->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($catObj->getResultSet());
    }

}
