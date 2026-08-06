<?php

/**
 * Admin Class is used to handle System Configuration
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class Configurations extends FatModel
{

    const DB_TBL = 'tbl_configurations';
    const DB_TBL_PREFIX = 'conf_';
    const FORM_GENERAL_SETTINGS = 1;
    const FORM_MEDIA_AND_LOGOS = 2;
    const FORM_THIRD_PARTY_APIS = 3;
    const FORM_COMMON_SETTINGS = 4;
    const FORM_EMAIL_AND_SMTPS = 5;
    const FORM_DASHBOARD_LESSONS = 6;
    const FORM_DASHBOARD_CLASSES = 7;
    const FORM_DISCUSSION_FORUM = 8;
    const FORM_SEO_AND_GOOGLE_TAGS = 9;
    const FORM_MAINTAINANCE_AND_SSL = 10;
    const FORM_REMEMBER_ME_SECURITY = 11;
    const FORM_PWA_SETTINGS = 12;
    const FORM_DASHBOARD_COURSES = 13;
    const FORM_REFERRAL_SETTINGS = 14;
    const FORM_OFFLINE_SESSIONS_SETTINGS = 15;
    const FORM_AFFILIATE_SETTINGS = 16;
    const MODERATE = 0;
    const HIGH = 1;

    /**
     * Initialize Configurations
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get Lang Type Form
     * 
     * @return array
     */
    public static function getLangTypeForms(): array
    {
        return [
            Configurations::FORM_GENERAL_SETTINGS,
            Configurations::FORM_MEDIA_AND_LOGOS,
        ];
    }

    /**
     * Get Security Settings
     * 
     * @return array
     */
    public static function getSecuritySettings(): array
    {
        return [
            Configurations::MODERATE => Label::getLabel('LBL_MODERATE'),
            Configurations::HIGH => Label::getLabel('LBL_High')
        ];
    }

    /**
     * Get Setting Tabs
     * 
     * @return array
     */
    public static function getTabs(): array
    {
        $configurationArr = [
            Configurations::FORM_GENERAL_SETTINGS => Label::getLabel('MSG_GENERAL_SETTINGS'),
            Configurations::FORM_MEDIA_AND_LOGOS => Label::getLabel('MSG_MEDIA_&_LOGOS'),
            Configurations::FORM_THIRD_PARTY_APIS => Label::getLabel('MSG_THIRD_PARTY_APIS'),
            Configurations::FORM_COMMON_SETTINGS => Label::getLabel('MSG_COMMON_SETTINGS'),
            Configurations::FORM_EMAIL_AND_SMTPS => Label::getLabel('MSG_EMAIL_AND_SMTP'),
            Configurations::FORM_DASHBOARD_LESSONS => Label::getLabel('MSG_DASHBOARD_LESSONS'),
            Configurations::FORM_DASHBOARD_CLASSES => Label::getLabel('MSG_DASHBOARD_CLASSES'),
            Configurations::FORM_DASHBOARD_COURSES => Label::getLabel('MSG_DASHBOARD_COURSES'),
            Configurations::FORM_DISCUSSION_FORUM => Label::getLabel('MSG_DISCUSSION_FORUM'),
            Configurations::FORM_SEO_AND_GOOGLE_TAGS => Label::getLabel('MSG_SEO_&_TAG_MANAGER'),
            Configurations::FORM_MAINTAINANCE_AND_SSL => Label::getLabel('MSG_MAINTAINANCE_&_SSL'),
            Configurations::FORM_REMEMBER_ME_SECURITY => Label::getLabel('MSG_REMEMBER_ME'),
            Configurations::FORM_PWA_SETTINGS => Label::getLabel('MSG_PWA_SETTINGS'),
            Configurations::FORM_REFERRAL_SETTINGS => Label::getLabel('MSG_REFERRAL_SETTINGS'),
            Configurations::FORM_OFFLINE_SESSIONS_SETTINGS => Label::getLabel('MSG_OFFLINE_SESSIONS'),
            Configurations::FORM_AFFILIATE_SETTINGS => Label::getLabel('MSG_AFFILIATE_SETTINGS'),
        ];
        if (!Course::isEnabled()) {
            unset($configurationArr[Configurations::FORM_DASHBOARD_COURSES]);
        }
        if (!GroupClass::isEnabled()) {
            unset($configurationArr[Configurations::FORM_DASHBOARD_CLASSES]);
        }
        return $configurationArr;
    }

    /**
     * Get Configurations
     * 
     * @param array $configs
     * @return array
     */
    public static function getConfigurations(array $configs = []): array
    {
        $srch = new SearchBase(static::DB_TBL, 'conf');
        if (count($configs) > 0) {
            $srch->addCondition('conf_name', 'IN', $configs);
        }
        $srch->addMultipleFields(['UPPER(conf_name) conf_name', 'conf_val']);
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Update Configurations
     * 
     * @param array $data
     * @return bool
     */
    public function update(array $data): bool
    {
        foreach ($data as $key => $val) {
            $assignValues = ['conf_name' => $key, 'conf_val' => $val];
            FatApp::getDb()->insertFromArray(static::DB_TBL, $assignValues, false, [], $assignValues);
        }
        return true;
    }

    /**
     * Update Configurations
     * 
     * @param string $key
     * @param type $value
     * @return bool
     */
    public function updateConf(string $key, $value): bool
    {
        $assignValues = ['conf_name' => $key, 'conf_val' => $value];
        if (!FatApp::getDb()->insertFromArray(Configurations::DB_TBL, $assignValues, false, [], $assignValues)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Check if course can be disabled
     *
     * @return bool
     */
    public function getCoursesStats()
    {
        $stats = [];
        /* get courses and teachers count */
        $srch = new SearchBase(Course::DB_TBL);
        $srch->addMultipleFields(['course_id', 'course_user_id']);
        $srch->addCondition('course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        if ($courses = FatApp::getDb()->fetchAll($srch->getResultSet())) {
            $stats['teachers'] = count(array_unique(array_column($courses, 'course_user_id')));
            $stats['courses'] = count($courses);
        }

        /* get order count and amount */
        $srch = new SearchBase(Order::DB_TBL);
        $srch->addMultipleFields(['COUNT(order_id) as orders', 'IFNULL(SUM(order_net_amount), 0) as amount']);
        $srch->addCondition('order_type', '=', Order::TYPE_COURSE);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if ($orders = FatApp::getDb()->fetch($srch->getResultSet())) {
            $stats['orders'] = $orders['orders'];
            $stats['amount'] = $orders['amount'];
        }
        return $stats;
    }

    /**
     * Check if Affiliate can be disabled
     *
     * @return bool
     */
    public function getAffiliateStats()
    {
        $stats = [];
        /* get total active affiliates */
        $srch = new SearchBase(User::DB_TBL);
        $srch->addFld('COUNT(user_id) as affiliates');
        $srch->addCondition('user_is_affiliate', '=', AppConstant::YES);
        $srch->addCondition('user_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $stats['affiliates'] = FatApp::getDb()->fetch($srch->getResultSet())['affiliates'];

        /* get total affiliate revenue */
        $srch = new SearchBase(User::DB_TBL_AFFILIATE_STAT);
        $srch->addMultipleFields(['IFNULL(SUM(afstat_signup_revenue + afstat_order_revenue), 0) as revenue']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $stats['revenue'] = FatApp::getDb()->fetch($srch->getResultSet())['revenue'];
        return $stats;
    }
}
