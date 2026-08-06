<?php

/**
 * Restore System Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class RestoreSystemController extends MyAppController
{

    const CONF_FILE = 'public/settings.php';

    private $db;
    private $restoredDb;

    /**
     * Initializing the database and its connection
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (empty(MyUtility::isDemoUrl()) || empty(FatApp::getConfig('CONF_AUTO_RESTORE_ON'))) {
            FatUtility::dieJsonError('Auto restore disabled by admin!');
        }
        /* Get the previously restored database */
        $this->restoredDb = (CONF_DB_NAME == Restore::DATABASE_FIRST) ? Restore::DATABASE_SECOND : Restore::DATABASE_FIRST;

        /* create a new connection */
        $this->db = new Database(CONF_DB_SERVER, CONF_DB_USER, CONF_DB_PASS, $this->restoredDb, true);
    }

    /**
     * Function which initiates and execute the restoration process
     *
     * @return void
     */
    public function index()
    {
        /* display error if url is executed manually and is not an ajax call */
        if (!FatUtility::isAjaxCall()) {
            FatUtility::dieWithError('Unauthorized Access!!');
        }
        if ($this->isRestoredSuccessfully()) {
            if (strtotime(FatApp::getConfig('CONF_RESTORE_SCHEDULE_TIME')) > strtotime(date('Y-m-d H:i:s'))) {
                FatUtility::dieJsonSuccess('');
            }
            $currentDb = CONF_DB_NAME;
            $this->db->startTransaction();
            /* Re-write the config settings to connect previously restored db */
            if (!$this->writeSettings(CONF_DB_SERVER, CONF_DB_USER, CONF_DB_PASS, $this->restoredDb)) {
                $this->db->rollbackTransaction();
                FatUtility::dieJsonError('Unable to restore data');
            }
            /* set next restoration time for the previously restored or to be connected db */
            if (!$this->resetRestoreTime($this->restoredDb)) {
                $this->db->rollbackTransaction();
                FatUtility::dieJsonError('Unable to update restoration time');
            }
            if (!$this->resetRestorationFlag($currentDb)) {
                $this->db->rollbackTransaction();
                FatUtility::dieJsonError('Unable to update restoration status');
            }
            $this->db->commitTransaction();
        } else {
            /* set next restoration time for the previously restored or to be connected db */
            $this->resetRestoreTime(CONF_DB_NAME);
            /* @TODO : Send an email notification */
        }
        FatCache::clearAll();
        FatUtility::dieJsonSuccess('Restored Successfully!');
    }

    /**
     * Function to check if db restoration was successful or not
     *
     * @return boolean
     */
    private function isRestoredSuccessfully()
    {
        $query = $this->db->query("SELECT * FROM `tbl_configurations` WHERE `conf_name` = 'CONF_RESTORED_SUCCESSFULLY'");
        $row = $this->db->fetch($query);
        if (!$row) {
            return false;
        }

        if ($row['conf_val'] > 0) {
            return true;
        }
        return false;
    }

    /**
     * Function to reset the next restoration time
     * Set time to current if force restoration is initiated
     * Otherwise set time according to the default defined hours
     *
     * @param string $db             database can be current or to be connected
     * @param bool   $doForceRestore status to check if forceful restoration is initiated
     *
     * @return void
     */
    private function resetRestoreTime($db, $doForceRestore = false)
    {
        /* if force restore initiated */
        if ($doForceRestore == true) {
            /* set restoration time to the current timestamp */
            $date = date('Y-m-d H:i:s');
        } else {
            /* set restoration time according to the defined interval */
            $date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +' . Restore::RESTORE_TIME_INTERVAL_HOURS . ' hours'));
        }
        /* update next restoration time */
        if (!$this->db->query("UPDATE `" . $db . "`.`tbl_configurations` set `conf_val` = '" . $date . "' where `conf_name` = 'CONF_RESTORE_SCHEDULE_TIME'")) {
            return false;
        }

        return true;
    }

    /**
     * Re-writing the db connection settings file content
     * Setting up connection to the recently restored db
     *
     * @param string $hostName
     * @param string $userName
     * @param string $password
     * @param string $database
     * @return void
     */
    private function writeSettings($hostName, $userName, $password, $database)
    {
        $admin = 'admin/';
        $settings_file = CONF_INSTALLATION_PATH . static::CONF_FILE;
        $output = '<?php' . "\n";
        $output .= '// DB' . "\n";
        $output .= 'define(\'CONF_WEBROOT_FRONTEND\', \'' . addslashes(CONF_WEBROOT_URL) . '\');' . "\n";
        $output .= 'define(\'CONF_WEBROOT_BACKEND\', \'' . addslashes(CONF_WEBROOT_URL) . $admin . '\');' . "\n";
        $output .= 'define(\'CONF_WEBROOT_DASHBOARD\', \'' . addslashes(CONF_WEBROOT_URL) . 'dashboard/\');' . "\n";
        $output .= 'define(\'CONF_DB_SERVER\', \'' . addslashes($hostName) . '\');' . "\n";
        $output .= 'define(\'CONF_DB_USER\', \'' . addslashes($userName) . '\');' . "\n";
        $output .= 'define(\'CONF_DB_PASS\', \'' . addslashes(html_entity_decode($password, ENT_QUOTES, 'UTF-8')) . '\');' . "\n";
        $output .= 'define(\'CONF_DB_NAME\', \'' . addslashes($database) . '\');';
        $file = fopen($settings_file, 'w');
        if (!fwrite($file, $output)) {
            return false;
        }
        fclose($file);

        return true;
    }

    /**
     * Function to update restoration successful status
     *
     * @param string $database can be current or to be connected
     *
     * @return void
     */
    private function resetRestorationFlag($database)
    {
        if (!$this->db->query("UPDATE `" . $database . "`.`tbl_configurations` SET `conf_val` = '0' WHERE `conf_name` = 'CONF_RESTORED_SUCCESSFULLY';")) {
            return false;
        }

        return true;
    }

    /**
     * Function to allow restoration forcefully
     * It sets the restoration time to the current timestamp to execute restoration immediately 
     *
     * @return void
     */
    public function forceRestore()
    {
        /* update restore time in the currently connected db */
        $this->resetRestoreTime(CONF_DB_NAME, true);
        FatApp::redirectUser(MyUtility::makeUrl('Home'));
    }

}
