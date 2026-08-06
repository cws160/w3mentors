<?php

/**
 * This class is used to handle Hours Taught Stats
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class HoursTaughtStat extends FatModel
{
    const DB_TBL = 'tbl_hours_taught_stats';

    /**
     * Initialize Hours Taught Stats
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return Search Object
     */
    public function getSearchObject()
    {
        $srch = new SearchBased(static::DB_TBL, 'hts');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'hts.hts_teacher_id = user.user_id', 'user');
        $srch->addCondition('user.user_is_teacher', '=', AppConstant::YES);
        $srch->addMultipleFields([
            'CONCAT(user.user_first_name, " ", user.user_last_name) as user_name',
            'SUM(IFNULL(hts.hts_lesson_duration,0) + IFNULL(hts.hts_class_duration,0)) AS total_duration',
            'SUM(hts.hts_lesson_duration) as hts_lesson_duration',
            'SUM(hts.hts_class_duration) as hts_class_duration', 'user_id'
        ]);
        $srch->addGroupBy('hts_teacher_id');
        $srch->addOrder('user.user_first_name', 'ASC');
        $srch->addOrder('user.user_id', 'ASC');
        return $srch;
    }

    /**
     * regenerate the Hours Taught report
     *
     * @return bool
     */
    public function regenerate($date = null): bool
    {
        $date = empty($date) ? FatApp::getConfig('CONF_SALES_REPORT_GENERATED_DATE') : $date;
        $date = date('Y-m-d', strtotime($date));
        $stats = [];
        $stats = $this->generateLessonsReport($date, $stats);
        $stats = $this->generateClassesReport($date, $stats);
        foreach ($stats as $rows) {
            foreach ($rows as $row) {
                $record = new TableRecord(static::DB_TBL);
                $record->assignValues($row);
                if (!$record->addNew([], $row)) {
                    $this->error = $record->getError();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Fetch and Return stats as per Minutes of Lessons Taught
     * 
     * @param string $date
     * @param array $stats
     * @return array
     */
    private function generateLessonsReport(string $date, array $stats): array
    {
        $srch = new SearchBase(Order::DB_TBL_LESSON, 'ordles');
        $srch->addCondition('ordles.ordles_status', '=', Lesson::COMPLETED);
        $srch->addCondition('mysql_func_DATE(ordles_lesson_starttime)', ">=", $date, 'AND', true);
        $srch->addDirectCondition('DATE(ordles_teacher_starttime) IS NOT NULL');
        $srch->addMultipleFields([
            'DATE(ordles_lesson_starttime) AS hts_date',
            'ordles_teacher_id As hts_teacher_id',
            'SUM(ordles.ordles_duration) as hts_lesson_duration'
        ]);
        $srch->addGroupBy('DATE(ordles_lesson_starttime), ordles_teacher_id');
        $srch->addOrder('ordles_teacher_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $stats[$row['hts_date']][$row['hts_teacher_id']]['hts_date'] = $row['hts_date'] ?? 0;
            $stats[$row['hts_date']][$row['hts_teacher_id']]['hts_teacher_id'] = $row['hts_teacher_id'] ?? 0;
            $stats[$row['hts_date']][$row['hts_teacher_id']]['hts_lesson_duration'] = $row['hts_lesson_duration'] ?? 0;
        }
        return $stats;
    }

    /**
     * Fetch and Return stats as per Minutes of Classes Taught
     * 
     * @param string $date
     * @param array $stats
     * @return array
     */
    private function generateClassesReport(string $date, array $stats): array
    {
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->addCondition('grpcls.grpcls_status', '=', GroupClass::COMPLETED);
        $srch->addCondition('grpcls.grpcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->addCondition('mysql_func_DATE(grpcls_start_datetime)', ">=", $date, 'AND', true);
        $srch->addDirectCondition('DATE(grpcls_teacher_starttime) IS NOT NULL');
        $srch->addMultipleFields([
            'DATE(grpcls_start_datetime) AS hts_date',
            'grpcls_teacher_id As hts_teacher_id',
            'SUM(grpcls.grpcls_duration) as hts_class_duration'
        ]);
        $srch->addGroupBy('DATE(grpcls_start_datetime), grpcls_teacher_id');
        $srch->addOrder('grpcls_teacher_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $stats[$row['hts_date']][$row['hts_teacher_id']]['hts_date'] = $row['hts_date'] ?? 0;
            $stats[$row['hts_date']][$row['hts_teacher_id']]['hts_teacher_id'] = $row['hts_teacher_id'] ?? 0;
            $stats[$row['hts_date']][$row['hts_teacher_id']]['hts_class_duration'] = $row['hts_class_duration'] ?? 0;
        }
        return $stats;
    }
}