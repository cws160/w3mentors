<?php

/**
 * This class is used to handle Issue Search
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class IssueSearch extends W3mentorsSearch
{

    /**
     * Initialize Issue Search
     * 
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = 'tbl_reported_issues';
        $this->alias = 'repiss';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = repiss.repiss_reported_by', 'learner');
        $this->joinTable(Order::DB_TBL_LESSON, 'LEFT JOIN', 'repiss.repiss_record_type = ' . Issue::TYPE_LESSON . ' AND repiss.repiss_record_id = ordles.ordles_id', 'ordles');
        $this->joinTable(OrderClass::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_type = ' . Issue::TYPE_GCLASS . ' AND repiss.repiss_record_id = ordcls.ordcls_id', 'ordcls');
        $this->joinTable(GroupClass::DB_TBL, 'LEFT JOIN', 'ordcls.ordcls_grpcls_id = grpcls.grpcls_id', 'grpcls');
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_type = ' . Issue::TYPE_LESSON . ' AND ordlestch.user_id = ordles.ordles_teacher_id', 'ordlestch');
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_type = ' . Issue::TYPE_GCLASS . ' AND ordclstch.user_id = grpcls.grpcls_teacher_id', 'ordclstch');
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        if ($this->userType === User::LEARNER) {
            $this->addCondition('repiss.repiss_reported_by', '=', $this->userId);
        } elseif ($this->userType === User::TEACHER) {
            $cond = $this->addCondition('ordles.ordles_teacher_id', '=', $this->userId);
            $cond->attachCondition('grpcls.grpcls_teacher_id', '=', $this->userId);
        }
        if (!GroupClass::isEnabled()) {
            $this->addCondition('repiss.repiss_record_type', '=', AppConstant::LESSON);
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
        if (!empty($post['keyword'])) {
            $keyword = trim($post['keyword']);
            if ($this->userType === User::LEARNER) {
                $classTeacher = 'mysql_func_CONCAT(ordclstch.user_first_name, " ", ordclstch.user_last_name)';
                $lessonTeacher = 'mysql_func_CONCAT(ordlestch.user_first_name, " ", ordlestch.user_last_name)';
                $cond = $this->addCondition($classTeacher, 'LIKE', '%' . $keyword . '%', 'AND', true);
                $cond->attachCondition($lessonTeacher, 'LIKE', '%' . $keyword . '%', 'OR', true);
            } elseif ($this->userType === User::TEACHER) {
                $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
                $this->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
            }
        }
        if (isset($post['class_type'])) {
            if (AppConstant::LESSON == $post['class_type']) {
                $this->addCondition('repiss.repiss_record_type', '=', AppConstant::LESSON);
            } elseif (AppConstant::GCLASS == $post['class_type']) {
                $this->addCondition('repiss.repiss_record_type', '=', AppConstant::GCLASS);
            }
        }
        if (!empty($post['repiss_status'])) {
            $this->addCondition('repiss.repiss_status', '=', $post['repiss_status']);
        }
        if (!empty($post['grpcls_id'])) {
            $this->addCondition('grpcls.grpcls_id', '=', $post['grpcls_id']);
        }
        if (!empty($post['order_id'])) {
            $orderId = FatUtility::int(str_replace('O', '', $post['order_id']));
            $condition = $this->addCondition('ordcls.ordcls_order_id', '=', $orderId);
            $condition->attachCondition('ordles.ordles_order_id', '=', $orderId);
        }
        if (!empty($post['repiss_record_id'])) {
            $this->addCondition('repiss.repiss_record_id', '=', $post['repiss_record_id']);
        }
        if (!empty($post['repiss_record_type'])) {
            $this->addCondition('repiss.repiss_record_type', '=', $post['repiss_record_type']);
        }
        if (!empty($post['learner_id'])) {
            $this->addCondition('learner.user_id', '=', $post['learner_id']);
        } elseif (!empty($post['learner'])) {
            $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
            $this->addCondition($fullName, 'LIKE', '%' . trim($post['learner']) . '%', 'AND', true);
        }
        if (!empty($post['teacher_id'])) {
            $cond = $this->addCondition('ordles.ordles_teacher_id', '=', $post['teacher_id']);
            $cond->attachCondition('grpcls.grpcls_teacher_id', '=', $post['teacher_id']);
        } elseif (!empty($post['teacher'])) {
            $classTeacher = 'mysql_func_CONCAT(ordclstch.user_first_name, " ", ordclstch.user_last_name)';
            $lessonTeacher = 'mysql_func_CONCAT(ordlestch.user_first_name, " ", ordlestch.user_last_name)';
            $cond = $this->addCondition($classTeacher, 'LIKE', '%' . trim($post['teacher']) . '%', 'AND', true);
            $cond->attachCondition($lessonTeacher, 'LIKE', '%' . trim($post['teacher']) . '%', 'OR', true);
        }
    }

    /**
     * Fetch And Format
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($rows) == 0) {
            return [];
        }

        $countryIds = array_merge(
            array_column($rows, 'learner_country_id'),
            array_column($rows, 'ordlestch_teacher_country_id'),
            array_column($rows, 'ordclstch_teacher_country_id')
        );
        $countries = Country::getNames($this->langId, $countryIds);
        $teachLangIds = array_merge(array_column($rows, 'grpcls_tlang_id'), array_column($rows, 'ordles_tlang_id'));
        $teachLangs = TeachLanguage::getTeachLanguages($this->langId, false, ['tlang_ids' => $teachLangIds]);
        foreach ($rows as $key => $row) {
            $row = array_merge($row, [
                'canEscalateIssue' => $this->canEscalateIssue($row),
                'learner_country_name' => $countries[$row['learner_country_id']] ?? '',
                'learner_full_name' => $row['learner_first_name'] . ' ' . $row['learner_last_name'],
            ]);
            if (AppConstant::LESSON == $row['repiss_record_type']) {
                $tlangName = $teachLangs[$row['ordles_tlang_id']]['tlang_name'] ?? '';
                if (!empty($row['ordles_ordsplan_id'])) {
                    $row['ordles_amount'] = OrderSubscriptionPlan::getAttributesById($row['ordles_ordsplan_id'], 'ordsplan_lesson_amount');
                }
                $row = array_merge($row, [
                    'ordles_tlang_name' => $tlangName,
                    'teacher_country_name' => $countries[$row['ordlestch_teacher_country_id']] ?? '',
                    'teacher_first_name' => $row['ordlestch_teacher_first_name'],
                    'teacher_last_name' => $row['ordlestch_teacher_last_name'],
                    'teacher_username' => $row['ordlestch_teacher_username'],
                    'teacher_full_name' => $row['ordlestch_teacher_first_name'] . ' ' . $row['ordlestch_teacher_last_name'],
                    'commission' => $row['ordles_commission'],
                    'teacher_id' => $row['ordles_teacher_id'],
                    'ordles_title' => $tlangName . ', ' . $row['ordles_duration'] . ' ' . Label::getLabel('LBL_MINUTES'),
                ]);
            } elseif (AppConstant::GCLASS == $row['repiss_record_type']) {
                $row = array_merge($row, [
                    'ordles_id' => $row['ordcls_id'],
                    'grpcls_id' => $row['grpcls_id'],
                    'ordles_order_id' => $row['ordcls_order_id'],
                    'ordles_teacher_id' => $row['grpcls_teacher_id'],
                    'ordles_tlang_id' => $row['grpcls_tlang_id'],
                    'ordles_lesson_starttime' => $row['grpcls_start_datetime'],
                    'ordles_lesson_endtime' => $row['grpcls_end_datetime'],
                    'ordles_teacher_starttime' => $row['grpcls_teacher_starttime'] ?? '',
                    'ordles_teacher_endtime' => $row['grpcls_teacher_endtime'] ?? '',
                    'ordles_student_starttime' => $row['ordcls_starttime'],
                    'ordles_student_endtime' => $row['ordcls_endtime'],
                    'ordles_ended_by' => $row['ordcls_ended_by'],
                    'commission' => $row['ordcls_commission'],
                    'teacher_id' => $row['grpcls_teacher_id'],
                    'ordles_duration' => $row['ordles_duration'] ?? '',
                    'ordles_amount' => $row['ordcls_amount'],
                    'ordles_refund' => $row['ordcls_refund'],
                    'ordles_discount' => $row['ordcls_discount'],
                    'ordles_reward_discount' => $row['ordcls_reward_discount'],
                    'ordles_status' => $row['ordcls_status'],
                    'ordles_tlang_name' => $teachLangs[$row['grpcls_tlang_id']]['tlang_name'] ?? '',
                    'teacher_country_name' => $countries[$row['ordclstch_teacher_country_id']] ?? '',
                    'teacher_first_name' => $row['ordclstch_teacher_first_name'],
                    'teacher_last_name' => $row['ordclstch_teacher_last_name'],
                    'teacher_username' => $row['ordclstch_teacher_username'],
                    'teacher_full_name' => $row['ordclstch_teacher_first_name'] . ' ' . $row['ordclstch_teacher_last_name'],
                    'ordles_title' => ($row['grpcls_title']),
                ]);
            }
            $row['repiss_reported_on'] = MyDate::formatDate($row['repiss_reported_on']);
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Can Escalate IssueF
     * 
     * Note: Please send the repiss_updated_on in system timezone 
     * @param array $issue
     * @return boolean
     */
    private function canEscalateIssue(array $issue): bool
    {
        if ($this->userType != User::LEARNER || empty($issue['repiss_updated_on'])) {
            return false;
        }
        $issueObj = new Issue($issue['repiss_id']);
        $logs = $issueObj->getLogs();
        $log = end($logs);
        $addedByType = $log['reislo_added_by_type'] ?? 0;
        $escalateHours = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
        $escalateDate = strtotime($issue['repiss_updated_on'] . " +" . $escalateHours . " hour");
        return ($escalateDate > time() && $issue['repiss_status'] == Issue::STATUS_RESOLVED && $addedByType == Issue::USER_TYPE_TEACHER);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    public static function getSearchForm(): Form
    {
        $frm = new Form('frmSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addSelectBox(Label::getLabel('LBL_CLASS_TYPE'), 'class_type', AppConstant::getClassTypes(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_ISSUE_STATUS'), 'repiss_status', Issue::getStatusArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'grpcls_id');
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'view', 1)->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear'));
        return $frm;
    }

    /**
     * Get Listing FFields
     * 
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'repiss.repiss_id' => 'repiss_id',
            'repiss.repiss_title' => 'repiss_title',
            'repiss.repiss_record_id' => 'repiss_record_id',
            'repiss.repiss_record_type' => 'repiss_record_type',
            'repiss.repiss_reported_on' => 'repiss_reported_on',
            'repiss.repiss_reported_by' => 'repiss_reported_by',
            'repiss.repiss_status' => 'repiss_status',
            'repiss.repiss_comment' => 'repiss_comment',
            'repiss.repiss_updated_on' => 'repiss_updated_on',
            'ordles.ordles_id' => 'ordles_id',
            'ordles.ordles_order_id' => 'ordles_order_id',
            'ordles.ordles_teacher_id' => 'ordles_teacher_id',
            'ordles.ordles_tlang_id' => 'ordles_tlang_id',
            'ordles.ordles_lesson_starttime' => 'ordles_lesson_starttime',
            'ordles.ordles_lesson_endtime' => 'ordles_lesson_endtime',
            'ordles.ordles_teacher_starttime' => 'ordles_teacher_starttime',
            'ordles.ordles_teacher_endtime' => 'ordles_teacher_endtime',
            'ordles.ordles_student_starttime' => 'ordles_student_starttime',
            'ordles.ordles_student_endtime' => 'ordles_student_endtime',
            'ordles.ordles_commission' => 'ordles_commission',
            'ordles.ordles_duration' => 'ordles_duration',
            'ordles.ordles_amount' => 'ordles_amount',
            'ordles.ordles_refund' => 'ordles_refund',
            'ordles.ordles_discount' => 'ordles_discount',
            'ordles.ordles_reward_discount' => 'ordles_reward_discount',
            'IFNULL(ordles.ordles_ordsplan_id, 0)' => 'ordles_ordsplan_id',
            'ordles.ordles_status' => 'ordles_status',
            'ordles.ordles_ended_by' => 'ordles_ended_by',
            'ordcls.ordcls_id' => 'ordcls_id',
            'ordcls.ordcls_order_id' => 'ordcls_order_id',
            'ordcls.ordcls_starttime' => 'ordcls_starttime',
            'ordcls.ordcls_endtime' => 'ordcls_endtime',
            'ordcls.ordcls_starttime' => 'ordcls_starttime',
            'ordcls.ordcls_endtime' => 'ordcls_endtime',
            'ordcls.ordcls_commission' => 'ordcls_commission',
            'ordcls.ordcls_amount' => 'ordcls_amount',
            'ordcls.ordcls_refund' => 'ordcls_refund',
            'ordcls.ordcls_discount' => 'ordcls_discount',
            'ordcls.ordcls_reward_discount' => 'ordcls_reward_discount',
            'ordcls.ordcls_status' => 'ordcls_status',
            'ordcls.ordcls_ended_by' => 'ordcls_ended_by',
            'grpcls.grpcls_id' => 'grpcls_id',
            'grpcls.grpcls_teacher_id' => 'grpcls_teacher_id',
            'grpcls.grpcls_tlang_id' => 'grpcls_tlang_id',
            'grpcls.grpcls_start_datetime' => 'grpcls_start_datetime',
            'grpcls.grpcls_end_datetime' => 'grpcls_end_datetime',
            'grpcls.grpcls_teacher_starttime' => 'grpcls_teacher_starttime',
            'grpcls.grpcls_teacher_endtime' => 'grpcls_teacher_endtime',
            'grpcls.grpcls_total_seats' => 'grpcls_total_seats',
            'grpcls.grpcls_entry_fee' => 'grpcls_entry_fee',
            'grpcls.grpcls_added_on' => 'grpcls_added_on',
            'grpcls.grpcls_status' => 'grpcls_status',
            'learner.user_country_id' => 'learner_country_id',
            'learner.user_id' => 'learner_id',
            'learner.user_username' => 'learner_username',
            'learner.user_first_name' => 'learner_first_name',
            'learner.user_last_name' => 'learner_last_name',
            'ordlestch.user_country_id' => 'ordlestch_teacher_country_id',
            'ordlestch.user_username' => 'ordlestch_teacher_username',
            'ordlestch.user_first_name' => 'ordlestch_teacher_first_name',
            'ordlestch.user_last_name' => 'ordlestch_teacher_last_name',
            'ordclstch.user_country_id' => 'ordclstch_teacher_country_id',
            'ordclstch.user_username' => 'ordclstch_teacher_username',
            'ordclstch.user_first_name' => 'ordclstch_teacher_first_name',
            'ordclstch.user_last_name' => 'ordclstch_teacher_last_name',
        ];
    }
}
