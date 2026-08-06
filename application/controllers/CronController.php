<?php

/**
 * Cron Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class CronController extends MyAppController
{

    /**
     * Initialize CRON
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $cron = new Cronjob();
    }

    public function index($id = 0)
    {
        $allCrons = Cron::getAllRecords($id);
        foreach ($allCrons as $row) {
            $cron = new Cron($row['cron_id']);
            $logId = $cron->markStarted();
            if (!$logId) {
                continue;
            }
            $arr = explode('/', $row['cron_command']);
            $cronjob = new Cronjob();
            $action = $arr[0];
            array_shift($arr);
            $success = call_user_func_array([$cronjob, $action], $arr);
            if ($success !== false) {
                $cron->markFinished($logId, 'Response Got: ' . $success);
            } else {
                $cron->markFinished($logId, 'Marked finished with error');
            }
            echo $row['cron_name'] . ' Completed <br/>';
        }
        Cron::clearOldLog();
        if (empty($allCrons)) {
            echo '<br/> Not find any scheduled Cron<br/>';
        }
        exit("End");
    }
}
