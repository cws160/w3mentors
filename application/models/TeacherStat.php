<?php

/**
 * This class is used to handle Teacher Stats
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeacherStat extends FatModel
{

    const DB_TBL = 'tbl_teacher_stats';

    private $userId;

    /**
     * Initialize TeacherStat
     * 
     * @param int $userId
     */
    function __construct(int $userId)
    {
        parent::__construct();
        $this->userId = $userId;
    }

    /**
     * Set Rating Review Count
     * 
     * @return bool
     */
    public function setRatingReviewCount(): bool
    {
        $srch = new SearchBase(RatingReview::DB_TBL, 'ratrev');
        $srch->addMultipleFields(['COUNT(*) as reviews', 'ROUND(AVG(ratrev.ratrev_overall), 2) as ratings']);
        $srch->addCondition('ratrev.ratrev_status', '=', RatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_teacher_id', '=', $this->userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $data = ['testat_ratings' => 0, 'testat_reviewes' => 0];
        if (!empty($row)) {
            $data = ['testat_ratings' => FatUtility::float($row['ratings']), 'testat_reviewes' => $row['reviews']];
        }
        $record = new TableRecord('tbl_teacher_stats');
        $record->setFldValue('testat_user_id', $this->userId);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Lesson And Class Count
     * 
     * @return bool
     */
    public function setLessonAndClassCount(): bool
    {
        $srch = new SearchBase(OfferPrice::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('IFNULL(SUM(offpri_lessons),0) AS testat_lessons');
        $srch->addFld('IFNULL(SUM(offpri_classes),0) AS testat_classes');
        $srch->addFld('COUNT(offpri_learner_id) AS testat_students');
        $srch->addCondition('offpri_teacher_id', '=', $this->userId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('testat_user_id', $this->userId);
        $record->assignValues($row);
        if (!$record->addNew([], $row)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Teach Lang Prices
     * 
     * @return bool
     */
    public function setTeachLangPrices(): bool
    {
        $userSlots = UserSetting::getSettings($this->userId, ['user_slots']);
        $slots = json_decode($userSlots['user_slots'] ?? '') ?? [];
        $srch = new SearchBase(UserTeachLanguage::DB_TBL, 'utlang');
        $srch->addMultipleFields(['utlang_tlang_id', 'utlang_price']);
        $srch->addCondition('utlang_user_id', '=', $this->userId);
        $srch->doNotCalculateRecords();
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($slots) && empty($rows)) {
            return true;
        }
        $priceSets = [];
        foreach ($rows as $row) {
            foreach ($slots as $slot) {
                array_push($priceSets, MyUtility::slotPrice(FatUtility::float($row['utlang_price']), $slot));
            }
        }
        $data = [
            'testat_teachlang' => (count($rows) > 0) ? 1 : 0,
            'testat_minprice' => FatUtility::float(count($priceSets) > 0 ? min($priceSets) : 0),
            'testat_maxprice' => FatUtility::float(count($priceSets) > 0 ? max($priceSets) : 0)
        ];
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('testat_user_id', $this->userId);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Speak Language
     * 
     * @return bool
     */
    public function setSpeakLang(): bool
    {
        $srch = new SearchBase(UserSpeakLanguage::DB_TBL);
        $srch->addCondition('uslang_user_id', '=', $this->userId);
        $srch->setPageSize(1);
        $srch->getResultSet();
        $speaklang = $srch->recordCount() > 0 ? 1 : 0;
        $data = ['testat_speaklang' => $speaklang];
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('testat_user_id', $this->userId);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Qualification
     * 
     * @return bool
     */
    public function setQualification(): bool
    {
        $srch = new SearchBase(UserQualification::DB_TBL);
        $srch->addCondition('uqualification_user_id', '=', $this->userId);
        $srch->setPageSize(1);
        $srch->getResultSet();
        $qualification = $srch->recordCount() > 0 ? 1 : 0;
        $data = ['testat_qualification' => $qualification];
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('testat_user_id', $this->userId);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Preference
     * 
     * @param int $preference
     * @return bool
     */
    public function setPreference(int $preference): bool
    {
        $data = ['testat_preference' => $preference];
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('testat_user_id', $this->userId);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Availability
     * 
     * @param int $available
     * @return bool
     */
    public function setAvailability(int $available): bool
    {
        $data = ['testat_availability' => $available];
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('testat_user_id', $this->userId);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * update lesson count
     * 
     * @param int $qty
     * @return bool
     */
    public function updateLessonCount(int $qty): bool
    {
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, ['testat_lessons' => 'mysql_func_testat_lessons + ' . $qty],
                        ['smt' => 'testat_user_id = ?', 'vals' => [$this->userId]], true)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * update classes count
     * 
     * @param int $qty
     * @return bool
     */
    public function updateClassesCount(int $qty): bool
    {
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, ['testat_classes' => 'mysql_func_testat_classes + ' . $qty],
                        ['smt' => 'testat_user_id = ?', 'vals' => [$this->userId]], true)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * update student count
     * 
     * @param int $qty
     * @return bool
     */
    public function updateStudentCount(int $qty): bool
    {
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, ['testat_students' => 'mysql_func_testat_students + ' . $qty],
                        ['smt' => 'testat_user_id = ?', 'vals' => [$this->userId]], true)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Teach Lang Prices Bulk
     */
    public function setTeachLangPricesBulk(): void
    {
        FatApp::getDb()->query('UPDATE ' . TeacherStat::DB_TBL . ' as t1 LEFT JOIN (SELECT MIN(innerTable.min_val*innerTable.utlang_price/60) as min_price , MAX(innerTable.max_val*innerTable.utlang_price/60) as max_price ,innerTable.`utlang_user_id` FROM (SELECT `utl`.utlang_user_id,`utl`.utlang_price,JSON_UNQUOTE(JSON_EXTRACT(`user_slots`,CONCAT("$[",JSON_LENGTH(`user_slots`)-1,"]"))) as max_val,JSON_UNQUOTE(JSON_EXTRACT(`user_slots`,"$[0]")) as min_val FROM ' . UserTeachLanguage::DB_TBL . ' as `utl` INNER JOIN ' . UserSetting::DB_TBL . ' as `us` ON `us`.`user_id` = `utl`.`utlang_user_id` GROUP BY `utl`.`utlang_user_id`, `utl`.`utlang_user_id`,`utlang_tlang_id`) AS innerTable GROUP BY innerTable.utlang_user_id) as t2 ON t1.testat_user_id = t2.utlang_user_id set t1.`testat_minprice` = IFNULL(t2.min_price ,0), t1.`testat_maxprice` = IFNULL(t2.max_price ,0), t1.`testat_teachlang` = if(t2.utlang_user_id IS NULL,0,1)');
    }

    /**
     * Set Preference Bulk
     */
    public function setPreferenceBulk(): void
    {
        FatApp::getDb()->query("UPDATE " . TeacherStat::DB_TBL . " LEFT JOIN (SELECT userPre.`uprefer_user_id` AS tPreUserId,  IF(COUNT(userPre.`uprefer_user_id`) > 0, 1, 0) AS userPreCount FROM " . Preference::DB_TBL_USER_PREF . " AS userPre INNER JOIN " . Preference::DB_TBL . " AS prefer ON prefer.prefer_id = userPre.uprefer_prefer_id GROUP BY userPre.`uprefer_user_id`) teacherPre ON teacherPre.tPreUserId = `testat_user_id` SET `testat_preference` = IFNULL(teacherPre.userPreCount,0)");
    }

    /**
     * Set Speak Lang Bulk
     */
    public function setSpeakLangBulk(): void
    {
        FatApp::getDb()->query("UPDATE " . TeacherStat::DB_TBL . " LEFT JOIN (SELECT userSpokenLang.`uslang_user_id` AS spokenUserId,  IF(COUNT(userSpokenLang.`uslang_user_id`) > 0, 1, 0) AS userSpokenCount FROM " . UserSpeakLanguage::DB_TBL . " AS userSpokenLang INNER JOIN " . SpeakLanguage::DB_TBL . " AS slanguage ON slanguage.slang_id = userSpokenLang.uslang_slang_id GROUP BY userSpokenLang.`uslang_user_id`) teacherSpoken ON teacherSpoken.spokenUserId = `testat_user_id` SET `testat_speaklang` = IFNULL(teacherSpoken.userSpokenCount,0)");
    }

    /**
     * Set sold courses count
     *
     * @return bool
     */
    public function setCoursesCount(): bool
    {
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcrs.ordcrs_order_id', 'orders');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'course');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = teacher.user_id', 'teacher');
        $srch->addCondition('course_user_id', '=', $this->userId);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addFld('COUNT(course_id) AS testat_courses');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        /* update courses count */
        $stats = new TableRecord(TeacherStat::DB_TBL);
        $stats->assignValues($row);
        $where = ['smt' => 'testat_user_id = ?', 'vals' => [$this->userId]];
        if (!$stats->update($where)) {
            $this->error = $stats->getError();
            return false;
        }
        return true;
    }

}
