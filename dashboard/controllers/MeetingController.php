<?php

class MeetingController extends DashboardController
{

    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function start(int $recordId, int $recordType)
    {
        $meet = Meeting::getMeeting($this->siteUserId, $recordId, $recordType);
        $settings = [];
        $toolSettings = json_decode($meet['metool_settings'], true);
        foreach ($toolSettings as $toolSetting) {
            foreach ($toolSetting as $key => $val) {
                $settings[$key] = $val['value'];
            }
        }
        $pagejs = [];
        $tooldir = FatUtility::camel2dashed($meet['metool_code']);
        $viewpath = 'plugins/meetings/' . $tooldir . '/start.php';
        $jspath = 'plugins/meetings/' . $tooldir . '/js/';
        foreach (scandir(CONF_INSTALLATION_PATH . $jspath) as $file) {
            if (strlen($file) > 2) {
                array_push($pagejs, CONF_INSTALLATION_PATH . $jspath . $file);
            }
        }
        $this->set('meet', $meet);
        $this->set('pagejs', $pagejs);
        $this->set('settings', $settings);
        $this->set('detail', json_decode($meet['meet_details'], true));
        $this->_template->addjs($pagejs);
        $this->_template->render(false, false, '../../' . $viewpath);
    }

    public function leave(int $recordId, int $recordType)
    {
        $meet = Meeting::getMeeting($this->siteUserId, $recordId, $recordType);
        $tooldir = FatUtility::camel2dashed($meet['metool_code']);
        $viewpath = 'plugins/meetings/' . $tooldir . '/leave.php';
        $this->_template->render(false, false, '../../' . $viewpath);
    }

}
