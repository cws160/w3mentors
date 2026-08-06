<?php

/**
 * This class is used to handle Cron
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Cron extends MyAppModel
{

    const DB_TBL = 'tbl_cron_schedules';
    const DB_TBL_PREFIX = 'cron_';
    const DB_TBL_LOG = 'tbl_cron_log';

    /**
     * Initialize Cron Model
     * 
     * @param int $cronId
     */
    public function __construct(int $cronId = 0)
    {
        parent::__construct(static::DB_TBL, 'cron_id', $cronId);
    }

    /**
     * Clear Old Logs
     * 
     * @return void
     */
    public static function clearOldLog(): void
    {
        FatApp::getDb()->deleteRecords(static::DB_TBL_LOG, [
            'smt' => 'cronlog_started_at < ?',
            'vals' => [date('Y-m-d', strtotime("-3 Day"))]
        ]);
    }

    /**
     * Get All Records
     * 
     * @param int $id
     * @param bool $activeOnly
     * @return array
     */
    public static function getAllRecords(int $id = 0, bool $activeOnly = true): array
    {
        $srch = new SearchBase(static::DB_TBL);
        if ($activeOnly) {
            $srch->addCondition('cron_active', '=', AppConstant::ACTIVE);
        }
        if ($id > 0) {
            $srch->addCondition('cron_id', '=', FatUtility::int($id));
        }
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'cron_id');
    }

    /**
     * Mark Started
     * 
     * @return bool|int
     */
    public function markStarted()
    {
        if (!$this->canStart()) {
            return false;
        }
        FatApp::getDb()->insertFromArray(static::DB_TBL_LOG, [
            'cronlog_cron_id' => $this->getMainTableRecordId(),
            'cronlog_started_at' => date('Y-m-d H:i:s')
        ]);
        return FatApp::getDb()->getInsertId();
    }

    /**
     * Mark Finished
     * 
     * @param int $logId
     * @param string $message
     */
    public function markFinished(int $logId, string $message)
    {
        $db = FatApp::getDb();
        $db->updateFromArray(static::DB_TBL_LOG, [
            'cronlog_ended_at' => date('Y-m-d H:i:s'),
            'cronlog_details' => "mysql_func_CONCAT(cronlog_details, '\n ', " . $db->quoteVariable($message) . ")"
                ], ['smt' => 'cronlog_id = ?', 'vals' => [$logId]], true);
    }

    /**
     * Can Start Cron
     * 
     * @return bool
     */
    private function canStart(): bool
    {
        $cronId = $this->getMainTableRecordId();
        $db = FatApp::getDb();
        $srch = new SearchBase(static::DB_TBL_LOG);
        $srch->addCondition('cronlog_cron_id', '=', $cronId);
        $srch->addOrder('cronlog_started_at', 'DESC');
        $srch->setPageSize(1);
        if (!$row = $db->fetch($srch->getResultSet())) {
            return true;
        }
        $cronDuration = static::getAttributesById($cronId, 'cron_duration');
        $diff = (time() - strtotime($row['cronlog_started_at'])) / 60;
        if ($diff < $cronDuration || $diff < 1) {
            return false;
        }
        if (is_null($row['cronlog_ended_at']) || $row['cronlog_ended_at'] < '1972-01-01' || $row['cronlog_ended_at'] == "0000-00-00 00:00:00") {
            if ($diff > $cronDuration * 2) {
                $this->markFinished($row['cronlog_id'], 'Marked Ended by cronjob manager at ' . date('Y-m-d H:i:s'));
                return true;
            }
            return false;
        }
        return true;
    }

}
