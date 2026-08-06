<?php

/**
 * This class is used to handle quiz Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class QuizSearch extends W3mentorsSearch
{

    /* Initialize Quiz Search
     *
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = 'tbl_quizzes';
        $this->alias = 'quiz';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'quiz.quiz_user_id = teacher.user_id', 'teacher');
    }


    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('quiz.quiz_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        if ($this->userType == User::TEACHER) {
            $this->addCondition('quiz_user_id', '=', $this->userId);
        }
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $this->addCondition('quiz_title', 'LIKE', '%' . $keyword . '%');
        }
        if (isset($post['quiz_type']) && $post['quiz_type'] > 0) {
            $this->addCondition('quiz.quiz_type', '=', $post['quiz_type']);
        }
        if (isset($post['quiz_status']) && $post['quiz_status'] > 0) {
            $this->addCondition('quiz.quiz_status', '=', $post['quiz_status']);
        }
        if (isset($post['quiz_active']) && $post['quiz_active'] != '') {
            $this->addCondition('quiz.quiz_active', '=', $post['quiz_active']);
        }
        if (isset($post['teacher_id']) && $post['teacher_id'] > 0) {
            $this->addCondition('teacher.user_id', '=', $post['teacher_id']);
        } elseif (!empty($post['teacher'])) {
            $teacher = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $this->addCondition($teacher, 'LIKE', '%' . trim($post['teacher']) . '%', 'AND', true);
        }
    }

    /**
     * Fetch & Format quiztions
     *
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'quiz_id');
        foreach ($rows as $key => $row) {
            $row['quiz_created'] = MyDate::formatDate($row['quiz_created']);
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
     * Get Listing FFields
     *
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'quiz.quiz_id' => 'quiz_id',
            'quiz.quiz_type' => 'quiz_type',
            'quiz.quiz_title' => 'quiz_title',
            'quiz.quiz_detail' => 'quiz_detail',
            'quiz.quiz_user_id' => 'quiz_user_id',
            'quiz.quiz_duration' => 'quiz_duration',
            'quiz.quiz_attempts' => 'quiz_attempts',
            'quiz.quiz_marks' => 'quiz_marks',
            'quiz.quiz_passmark' => 'quiz_passmark',
            'quiz.quiz_validity' => 'quiz_validity',
            'quiz.quiz_certificate' => 'quiz_certificate',
            'quiz.quiz_questions' => 'quiz_questions',
            'quiz.quiz_passmsg' => 'quiz_passmsg',
            'quiz.quiz_failmsg' => 'quiz_failmsg',
            'quiz.quiz_active' => 'quiz_active',
            'quiz.quiz_status' => 'quiz_status',
            'quiz.quiz_created' => 'quiz_created',
            'teacher.user_first_name' => 'teacher_first_name',
            'teacher.user_last_name' => 'teacher_last_name',
            'teacher.user_username' => 'teacher_username',
        ];
    }

    /**
     * Get Search Form
     */
    public static function getSearchForm()
    {
        $frm = new Form('srchForm');
        $frm->addTextBox(Label::getLabel('LBL_TITLE'), 'keyword', '');
        $frm->addTextBox(Label::getLabel('LBL_TEACHER'), 'teacher', '', ['autocomplete' => 'off']);
        $frm->addHiddenField('', 'teacher_id');
        $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'quiz_type', Quiz::getTypes());
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'quiz_status', Quiz::getStatuses());
        $frm->addSelectBox(Label::getLabel('LBL_ACTIVE'), 'quiz_active', AppConstant::getYesNoArr());
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', AppConstant::PAGESIZE)
            ->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addHiddenField('', 'record_id');
        $frm->addHiddenField('', 'record_type');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Get quiz form
     */
    public static function getQuizForm()
    {
        $frm = new Form('frmQuizLink');
        $quesFld = $frm->addCheckBoxes('', 'quilin_quiz_id', []);
        $quesFld->requirements()->setRequired();
        $quesFld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PLEASE_SELECT_QUIZ(ZES)'));
        $fld = $frm->addHiddenField('', 'quilin_record_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addHiddenField('', 'quilin_record_type');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        $fld = $frm->addHiddenField('', 'quilin_user_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setInt();
        return $frm;
    }
}
