<?php

/**
 * This class is used to handle Question Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class QuestionSearch extends W3mentorsSearch
{

    /* Initialize Question Search
     *
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = 'tbl_questions';
        $this->alias = 'ques';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'ques.ques_user_id = teacher.user_id', 'teacher');
        $this->joinTable(Category::DB_TBL, 'LEFT JOIN', 'ques.ques_cate_id = cate.cate_id', 'cate');
    }

    
    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('ques.ques_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $this->addCondition('cate.cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $this->addCondition('cate.cate_status', '=', AppConstant::ACTIVE);
        if (0 < $this->userId) {
            $this->addCondition('ques_user_id', '=', $this->userId);
        }
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     */
    public function applySearchConditions(array $post): void
    {
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $this->addCondition('ques_title', 'LIKE', '%' . $keyword . '%');
        }
        if (isset($post['ques_cate_id']) && $post['ques_cate_id'] > 0) {
            $this->addCondition('ques_cate_id', '=', $post['ques_cate_id']);
        }
        if (isset($post['ques_subcate_id']) && $post['ques_subcate_id'] > 0) {
            $this->addCondition('ques_subcate_id', '=', $post['ques_subcate_id']);
        }
        if (isset($post['ques_type']) && $post['ques_type'] > 0) {
            $this->addCondition('ques.ques_type', '=', $post['ques_type']);
        }
        if (isset($post['questions']) && count($post['questions']) > 0) {
            $this->addDirectCondition('ques.ques_id NOT IN (' . implode(',', $post['questions']) .')');
        }
        if (isset($post['quiz_id']) && $post['quiz_id'] > 0) {
            $this->joinTable(Quiz::DB_TBL_QUIZ_QUESTIONS, 'INNER JOIN', 'ques_id = quique_ques_id');
            $this->joinTable(Quiz::DB_TBL, 'INNER JOIN', 'quique_quiz_id = quiz_id AND quiz_deleted IS NULL');
            $this->addCondition('quiz_id', '=', $post['quiz_id']);
        }
        if (isset($post['teacher_id']) && $post['teacher_id'] > 0) {
            $this->addCondition('ques.ques_user_id', '=', $post['teacher_id']);
        } elseif (!empty($post['teacher'])) {
            $teacher = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $this->addCondition($teacher, 'LIKE', '%' . trim($post['teacher']) . '%', 'AND', true);
        }
        if (isset($post['type'])) {
            if ($post['type'] == Quiz::TYPE_AUTO_GRADED) {
                $this->addCondition('ques_type', '!=', Question::TYPE_TEXT);
            } else {
                $this->addCondition('ques_type', '=', Question::TYPE_TEXT);
            }
        }
    }

    /**
     * Fetch & Format Questions
     *
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'ques_id');
        if (count($rows) == 0) {
            return [];
        }

        /* get categories list */
        $categoryIds = [];
        array_map(function ($val) use (&$categoryIds) {
            $categoryIds = array_merge($categoryIds, [$val['ques_cate_id'], $val['ques_subcate_id']]);
        }, $rows);
        $categoryIds = array_unique($categoryIds);
        $categories = $this->getCategoryNames($this->langId, $categoryIds);

        foreach ($rows as $key => $row) {
            $row['ques_cate_name'] = isset($categories[$row['ques_cate_id']]) ? $categories[$row['ques_cate_id']] : '';
            $row['ques_subcate_name'] = $categories[$row['ques_subcate_id']] ?? '';
            if (isset($row['ques_created'])) {
                $row['ques_created'] = MyDate::formatDate($row['ques_created']);
            }
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Add Search Listing Fields
     */
    public function addSearchListingFields(): void
    {
        $fields = static::getListingFields();
        foreach ($fields as $field => $alias) {
            $this->addFld($field . ' AS ' . $alias);
        }
    }


    /**
     * Get Categories Name
     *
     * @param int   $langId
     * @param array $categoryIds
     * @return array
     */
    public static function getCategoryNames(int $langId, array $categoryIds)
    {
        if (count($categoryIds) == 0) {
            return [];
        }
        $srch = new SearchBase(Category::DB_TBL);
        $srch->joinTable(
            Category::DB_LANG_TBL,
            'LEFT JOIN',
            'cate_id = catelang_cate_id AND catelang_lang_id = ' . $langId
        );
        $srch->addMultipleFields(['cate_id', 'IFNULL(cate_name, cate_identifier) AS cate_name']);
        $srch->addCondition('cate_id', 'IN', $categoryIds);
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('cate_status', '=', AppConstant::ACTIVE);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Listing FFields
     *
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'ques.ques_id' => 'ques_id',
            'ques.ques_type' => 'ques_type',
            'ques.ques_title' => 'ques_title',
            'ques.ques_detail' => 'ques_detail',
            'ques.ques_cate_id' => 'ques_cate_id',
            'ques.ques_subcate_id' => 'ques_subcate_id',
            'ques.ques_user_id' => 'ques_user_id',
            'ques.ques_clang_id' => 'ques_clang_id',
            'ques.ques_answer' => 'ques_answer',
            'ques.ques_hint' => 'ques_hint',
            'ques.ques_status' => 'ques_status',
            'ques.ques_marks' => 'ques_marks',
            'ques.ques_created' => 'ques_created',
            'cate.cate_type' => 'ques_cate_type',
            'teacher.user_id' => 'teacher_id',
            'teacher.user_first_name' => 'teacher_first_name',
            'teacher.user_last_name' => 'teacher_last_name',
            'teacher.user_username' => 'teacher_username',
        ];
    }


    /**
     * Get Search Form
     *
     * @param int $langId
     */
    public static function getSearchForm(int $langId): Form
    {
        $categoryList = Category::getCategoriesByParentId($langId, 0, Category::TYPE_QUESTION, false);
        $frm = new Form('frmQuesSearch');
        $frm->addTextBox(Label::getLabel('LBL_TITLE'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_CATEGORY'), 'ques_cate_id', $categoryList, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_SUBCATEGORY'), 'ques_subcate_id', [], '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'ques_type', Question::getTypes(), '', [], Label::getLabel('LBL_SELECT'));
        $fld = $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', AppConstant::PAGESIZE);
        $fld->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }
}
