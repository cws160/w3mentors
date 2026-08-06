<?php

class Question extends MyAppModel
{
    public const DB_TBL = 'tbl_questions';
    public const DB_TBL_PREFIX = 'ques_';
    public const DB_TBL_OPTIONS = 'tbl_question_options';

    public const TYPE_SINGLE = 1;
    public const TYPE_MULTIPLE = 2;
    public const TYPE_TEXT = 3;

    private $userId;

    /**
     * Initialize Questions
     *
     * @param int $id
     * @param int $userId
     */
    public function __construct(int $id = 0, int $userId = 0)
    {
        $this->userId = $userId;
        parent::__construct(static::DB_TBL, 'ques_id', $id);
    }

    /**
     * Get Question Types
     *
     * @param int $key
     * @return string|array
     */
    public static function getTypes(int $key = null)
    {
        $arr = [
            static::TYPE_SINGLE => Label::getLabel('LBL_SINGLE_CHOICE'),
            static::TYPE_MULTIPLE => Label::getLabel('LBL_MULTIPLE_CHOICE'),
            static::TYPE_TEXT => Label::getLabel('LBL_TEXT'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Question Status List
     *
     * @param integer $key
     * @return string|array
     */
    public static function getStatuses(int $key = null)
    {
        $arr = [
            AppConstant::ACTIVE => Label::getLabel('LBL_ACTIVE'),
            AppConstant::INACTIVE => Label::getLabel('LBL_INACTIVE'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get question by id
     *
     * @param int $id
     * @return array
     */
    public static function getById(int $id)
    {
        $srch = new SearchBase(self::DB_TBL, 'ques');
        $srch->addCondition('ques_id', '=', $id);
        $srch->addCondition('ques_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Question Options Details
     *
     * @return array
     */
    public function getOptions()
    {
        $srch = new SearchBase(self::DB_TBL_OPTIONS);
        $srch->addMultipleFields(['queopt_id', 'queopt_title', 'queopt_order', 'queopt_detail']);
        $srch->addCondition('queopt_ques_id', '=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        $srch->addOrder('queopt_order', 'ASC');
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'queopt_id');
    }

    /**
     * Delete
     *
     * @return bool
     */
    public function remove(): bool
    {
        if (!$question = static::getById($this->getMainTableRecordId())) {
            $this->error = Label::getLabel('LBL_QUESTION_NOT_FOUND');
            return false;
        }
        if ($this->userId != $question['ques_user_id']) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }

        if (static::isAttachedWithQuiz($this->getMainTableRecordId(), $this->userId)) {
            $this->error = Label::getLabel('LBL_QUESTIONS_ATTACHED_WITH_QUIZZES_CANNOT_BE_DELETED');
            return false;
        }

        $db = FatApp::getDb();
        $db->startTransaction();
        $this->setFldValue('ques_deleted', date('Y-m-d H:i:s'));
        if (!$this->saveData()) {
            return false;
        }
        $category = new Category();
        if (!$category->updateCount([$question['ques_cate_id'], $question['ques_subcate_id']])) {
            $this->error = $category->getError();
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Save data
     *
     * @return bool
     */
    public function saveData()
    {
        if ($this->getMainTableRecordId() < 1) {
            $this->setFldValue('ques_created', date('Y-m-d H:i:s'));
        } else {
            $this->setFldValue('ques_updated', date('Y-m-d H:i:s'));
        }
        return $this->save();
    }

    /**
     * Setup Questions
     *
     * @param array $data
     * @return bool
     */
    public function setup(array $data)
    {
        $categories = [];
        $quesId = $this->getMainTableRecordId();
        if ($quesId > 0) {
            if (!$question = static::getById($this->getMainTableRecordId())) {
                $this->error = Label::getLabel('LBL_QUESTION_NOT_FOUND');
                return false;
            }
            if ($this->userId != $question['ques_user_id']) {
                $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
                return false;
            }
            if (
                ($data['ques_type'] == Question::TYPE_TEXT && $question['ques_type'] != Question::TYPE_TEXT) ||
                ($data['ques_type'] != Question::TYPE_TEXT && $question['ques_type'] == Question::TYPE_TEXT)
            ) {
                if (static::isAttachedWithQuiz($quesId, $this->userId)) {
                    $this->error = Label::getLabel('LBL_TYPE_FOR_QUESTIONS_ATTACHED_WITH_QUIZZES_CANNOT_BE_UPDATED');
                    return false;
                }
            }
            $categories = [$question['ques_cate_id'], $question['ques_subcate_id']];
        }
        if (!$this->validate($data)) {
            return false;
        }
        $this->setFldValue('ques_user_id', $this->userId);
        if ($quesId < 1) {
            $this->setFldValue('ques_status', AppConstant::ACTIVE);
        }
        if ($data['ques_type'] == Question::TYPE_TEXT) {
            $data['ques_options_count'] = 0;
        } else {
            if ($data['ques_options_count'] != count($data['queopt_title'])) {
                $this->error = Label::getLabel('LBL_OPTION_COUNT_&_N0._OF_OPTIONS_SUBMITTED_DOES_NOT_MATCH');
                return false;
            }
        }
        $this->assignValues($data);
        
        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$this->saveData()) {
            return false;
        }
        if (!$this->setupOptions($data)) {
            $db->rollbackTransaction();
            return false;
        }
        $categories = array_merge($categories, [$data['ques_cate_id'], $data['ques_subcate_id']]);
        $category = new Category();
        if (!$category->updateCount($categories)) {
            $this->error = $category->getError();
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Setup question options
     *
     * @param array $data
     * @return bool
     */
    private function setupOptions(array $data): bool
    {
        $quesId = $this->getMainTableRecordId();
        $db = FatApp::getDb();

        /* delete old questions */
        if (!$db->deleteRecords(static::DB_TBL_OPTIONS,['smt' => 'queopt_ques_id = ?', 'vals' => [$quesId]])) {
            $this->error = $db->getError();
            return false;
        }
        $ques_answers = [];
        if ($data['ques_type'] == Question::TYPE_TEXT) {
            return true;
        }
        $i = 1;
        foreach ($data['queopt_title'] as $key => $value) {
            $queopt = new TableRecord(Question::DB_TBL_OPTIONS);
            $queopt->assignValues([
                'queopt_ques_id' => $quesId,
                'queopt_title'   => $value,
                'queopt_order'   => $i,
            ]);
            if (!$queopt->addNew()) {
                $this->error = $queopt->getError();
                return false;
            }
            if (in_array($key, $data['answers'])) {
                $ques_answers[] = $queopt->getId();
            }
            $i++;
        }
        $this->setFldValue('ques_id', $quesId);
        $this->assignValues(['ques_answer' => json_encode($ques_answers)]);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Validate Categories
     *
     * @param array $data
     * @return bool
     */
    private function validate(array $data)
    {
        $categories = [$data['ques_cate_id'], $data['ques_subcate_id']];
        $srch = Category::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['cate_id', 'cate_parent']);
        $srch->addCondition('cate_id', 'IN', $categories);
        $srch->addCondition('cate_status', '=', AppConstant::ACTIVE);
        $srch->addCondition('cate_type', '=', Category::TYPE_QUESTION);
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $categories = FatApp::getDb()->fetchAll($srch->getResultSet(), 'cate_id');
        if (!array_key_exists($data['ques_cate_id'], $categories)) {
            $this->error = Label::getLabel('LBL_CATEGORY_NOT_AVAILABLE');
            return false;
        }
        if ($data['ques_subcate_id'] > 0) {
            if (!array_key_exists($data['ques_subcate_id'], $categories)) {
                $this->error = Label::getLabel('LBL_SUBCATEGORY_NOT_AVAILABLE');
                return false;
            }
            if ($categories[$data['ques_subcate_id']]['cate_parent'] != $data['ques_cate_id']) {
                $this->error = Label::getLabel('LBL_INVALID_SUBCATEGORY');
                return false;
            }
        }
        return true;
    }

    /**
     * Check if question is attached with any quiz
     *
     * @param int $questionId
     * @param int $userId
     * @return bool
     */
    private static function isAttachedWithQuiz(int $questionId, int $userId) : bool
    {
        $srch = new QuizQuestionSearch(0, 0, 0);
        $srch->addCondition('quiz_user_id', '=', $userId);
        $srch->addCondition('quique_ques_id', '=', $questionId);
        $srch->addCondition('quiz_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        if ($srch->recordCount() > 0) {
            return true;
        }
        return false;
    }
}
