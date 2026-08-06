<?php

/**
 * Home Controller is used for handling Basic actions
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class HomeController extends AdminBaseController
{

    /**
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Dashboard 
     */
    public function index()
    {
        $this->_template->addJs(['js/chartist.min.js', 'js/chartist-plugin-tooltip.min.js', 'js/jquery.counterup.js', 'js/slick.min.js']);
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0; rv:11.0') !== false) {
            $this->_template->addCss('css/ie.css');
        }
        $date = FatApp::getConfig('CONF_SALES_REPORT_GENERATED_DATE');
        $timezone = Admin::getAttributesById($this->siteAdminId, ['admin_timezone'])['admin_timezone'];
        $datetime = MyDate::convert($date, $timezone);
        $regendatedtime = str_replace('{datetime}', MyDate::showDate($datetime, true) . ' (' . $timezone . ')', Label::getLabel('LBL_REPORT_GENERATED_ON_{datetime}'));
        $this->sets([
            'objPrivilege' => $this->objPrivilege,
            'stats' => AdminStatistic::getDashboardStats(),
            'regendatedtime' => $regendatedtime,
            'canView' => $this->objPrivilege->canViewAdminDashboard(true),
        ]);
        if ($this->objPrivilege->canViewAdminDashboard(true) == false) {
            $this->sets(['pageText' => '']);
        }
        $this->_template->render();
    }

    /**
     * Dashboard Stat Chart
     */
    public function dashboardStatChart()
    {
        $this->objPrivilege->canViewAdminDashboard();
        $userData = AdminStatistic::getUsersStat(MyDate::TYPE_LAST_12_MONTH);
        $lessonData = AdminStatistic::getAdminLessonEarningStats(MyDate::TYPE_LAST_12_MONTH);
        
        $courseData = [];
        if (Course::isEnabled()) {
            $courseData = AdminStatistic::getAdminCourseEarningStats(MyDate::TYPE_LAST_12_MONTH);
        }
        $classData = [];
        if (GroupClass::isEnabled()) {
            $classData = AdminStatistic::getAdminClassEarningStats(MyDate::TYPE_LAST_12_MONTH);
        }
        FatUtility::dieJsonSuccess([
            'userData' => $userData,
            'lessonData' => $lessonData,
            'classData' => $classData,
            'courseData' => $courseData
        ]);
    }

    /**
     * Dashboard Stats
     */
    public function topClassLanguages()
    {
        $this->objPrivilege->canViewAdminDashboard();
        $interval = FatApp::getPostedData('interval', FatUtility::VAR_INT, MyDate::TYPE_ALL);
        $interval = (!array_key_exists($interval, MyDate::getDurationTypesArr())) ? MyDate::TYPE_ALL : $interval;
        $stats = (GroupClass::isEnabled()) ? AdminStatistic::classTopLanguage($this->siteLangId, $interval, 50) : [];
        $this->set('statsInfo', $stats);
        $this->_template->render(false, false);
    }

    /**
     * Dashboard Stats
     */
    public function topLessonLanguages()
    {
        $this->objPrivilege->canViewAdminDashboard();
        $interval = FatApp::getPostedData('interval', FatUtility::VAR_INT, MyDate::TYPE_ALL);
        $interval = (!array_key_exists($interval, MyDate::getDurationTypesArr())) ? MyDate::TYPE_ALL : $interval;
        $this->set('statsInfo', AdminStatistic::lessonTopLanguage($this->siteLangId, $interval, 50));
        $this->_template->render(false, false);
    }

    /**
     * Dashboard Stats
     */
    public function topCourseCategories()
    {
        $this->objPrivilege->canViewAdminDashboard();
        $interval = FatApp::getPostedData('interval', FatUtility::VAR_INT, MyDate::TYPE_ALL);
        $interval = (!array_key_exists($interval, MyDate::getDurationTypesArr())) ? MyDate::TYPE_ALL : $interval;
        $stats = (Course::isEnabled()) ? AdminStatistic::courseTopCategories($this->siteLangId, $interval, 50) : [];
        $this->set('statsInfo', $stats);
        $this->_template->render(false, false);
    }

    /**
     * Dashboard Stats
     */
    public function googleAnalyticsEvents()
    {
        $this->objPrivilege->canViewAdminDashboard();
        $interval = FatApp::getPostedData('interval', FatUtility::VAR_INT, MyDate::TYPE_ALL);
        $interval = (!array_key_exists($interval, MyDate::getDurationTypesArr())) ? MyDate::TYPE_ALL : $interval;
        $datetime = MyDate::getStartEndDate($interval, NULL, false, 'Y-m-d');
        $days = ($interval == MyDate::TYPE_ALL) ? 0 : 1;
        $datetime['endDate'] = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
        $analytics = new GoogleAnalytics();
        $data = $analytics->getEventMeasurements($datetime['startDate'], $datetime['endDate']);
        if (FatApp::getConfig('CONF_ENABLE_COURSES') == AppConstant::NO) {
            if (isset($data['book_course'])) {
                unset($data['book_course']);
            }
        }
        if (!GroupClass::isEnabled()) {
            if (isset($data['book_class'])) {
                unset($data['book_class']);
            }
        }
        if ($data === false) {
            $this->set('error', true);
            $this->set('errorMsg', $analytics->getError());
        }
        $this->set('statsInfo', $data);
        $this->_template->render(false, false);
    }

    /**
     * Dashboard Stats
     */
    public function googleAnalyticsTrafficAcquitions()
    {
        $this->objPrivilege->canViewAdminDashboard();
        $interval = FatApp::getPostedData('interval', FatUtility::VAR_INT, MyDate::TYPE_ALL);
        $interval = (!array_key_exists($interval, MyDate::getDurationTypesArr())) ? MyDate::TYPE_ALL : $interval;
        $datetime = MyDate::getStartEndDate($interval, NULL, false, 'Y-m-d');
        $days = ($interval == MyDate::TYPE_ALL) ? 0 : 1;
        $datetime['endDate'] = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
        $analytics = new GoogleAnalytics();
        $data = $analytics->getTrafficMeasurements($datetime['startDate'], $datetime['endDate']);
        if ($data === false) {
            $this->set('error', true);
            $this->set('errorMsg', $analytics->getError());
        }
        $this->set('statsInfo', $data);
        $this->_template->render(false, false);
    }

    /**
     * Clear Cache
     */
    public function clearCache()
    {
        FatCache::clearAll();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_CACHE_HAS_BEEN_CLEARED'));
    }

    /**
     * Set Admin Language
     * 
     * @param int $langId
     */
    public function setLanguage($langId = 0)
    {
        FatCache::clearAll();
        $langId = FatUtility::int($langId);
        if ($langId > 0) {
            $language = Language::getData($langId);
            if (empty($language)) {
                FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
            }
            MyUtility::setCookie('CONF_SITE_LANGUAGE', $langId);
            FatUtility::dieJsonSuccess(Label::getLabel('MSG_LANGUAGE_UPDATE_SUCCESSFULLY'));
        }
        FatUtility::dieJsonError(Label::getLabel('MSG_PLEASE_SELECT_ANY_LANGUAGE'));
    }
}
