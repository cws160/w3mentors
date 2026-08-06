<?php

/**
 * This class is used to handle Subscription Plan
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class SubscriptionPlan extends MyAppModel
{

    const DB_TBL = 'tbl_subscription_plans';
    const DB_TBL_PREFIX = 'subplan_';
    const DB_TBL_LANG = 'tbl_subscription_plans_lang';
    const DB_TBL_LANG_PREFIX = 'subplang_';



    /**
     * Initialize subscripiton plan
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'subplan_id', $id);
        if (!SubscriptionPlan::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
    }



    /**
     * Save Subscription plans
     * @param array $record (subscription post data)
     * @return array
     */
    public function saveRecord(array $record): bool
    {
        if ($this->getMainTableRecordId() == 0) {
            $record['subplan_created'] = date('Y-m-d H:i:s');
        }
        $record['subplan_updated'] = date('Y-m-d H:i:s');
        $this->assignValues($record);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }



    /**
     * Get All Subscription plans
     * 
     * @param int $langId
     * @return array
     */
    public static function getByIds(int $langId = 0, array $ids = [], $active = true, $forHomePage = false): array
    {
        $srch = new SearchBase(static::DB_TBL, 'sp');
        $srch->addMultipleFields(['sp.*']);
        if ($langId > 0) {
            $srch->addFld('IFNULL(splang.subplang_subplan_title, sp.subplan_title) AS plan_name');
            $srch->addFld('splang.subplang_subplan_title as lang_name');
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'splang.subplang_subplan_id = sp.subplan_id AND splang.subplang_lang_id = ' . $langId, 'splang');
        }
        if (!empty($ids)) {
            $srch->addCondition('sp.subplan_id', 'IN', $ids);
        }
        if ($active) {
            $srch->addCondition('sp.subplan_active', '=', AppConstant::YES);
        }
        $srch->addOrder('subplan_order', 'ASC');
        if($forHomePage) {
            $srch->setPageSize(4);
        }
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Add/Edit Subscription Plan Lang Data
     *
     * @param array $data
     * @return bool
     */
    public function setupLangData($data): bool
    {
        $assignValues = [
            'subplang_subplan_id' => $this->getMainTableRecordId(),
            'subplang_lang_id' => $data['subplang_lang_id'],
            'subplang_subplan_title' => $data['subplang_subplan_title']
        ];
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_LANG, $assignValues, false, [], $assignValues)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Sort Order
     * 
     * @param array $order
     * @return bool
     */
    public function updateOrder(array $order): bool
    {
        if (empty($order)) {
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        foreach ($order as $i => $id) {
            if (FatUtility::int($id) < 1) {
                continue;
            }
            if (!$db->updateFromArray(static::DB_TBL, ['subplan_order' => $i], ['smt' => 'subplan_id = ?', 'vals' => [$id]])) {
                $db->rollbackTransaction();
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        $db->commitTransaction();
        return true;
    }


    /**
     * Check if susbcripition is enabled or not
     */
    public static function isEnabled(): Bool
    {
        $status = FatApp::getConfig('CONF_ENABLE_SUBSCRIPTION_PLAN');
        return (bool) $status;
    }

    public function sendInactivePlanNotification($limit, $page)
    {
        $users =  OrderSubscriptionPlan::getByPlanId($this->getMainTableRecordId(), $limit, $page);
        if (!empty($users)) {
            $langData = static::getAllLangs();
            foreach ($users as $user) {
                $planName = $langData[$this->getMainTableRecordId()][$user['user_lang_id']] ?? '';
                $notifiVar = ['{plan_name}' => $planName];
                $notifi = new Notification($user['user_id'], Notification::TYPE_SUB_PLAN_INACTIVE);
                $notifi->sendNotification($notifiVar);
                $vars = [
                    '{learner_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
                    '{plan_name}' => $planName,
                    '{subscriptions_link}' => MyUtility::generateFullUrl('SubscriptionPlans', '', [], CONF_WEBROOT_FRONTEND)
                ];
                $mail = new FatMailer($user['user_lang_id'], 'subscription_plan_inactive_mail_to_learner');
                $mail->setVariables($vars);
                if (!$mail->sendMail([$user['user_email']])) {
                    $this->error = $mail->getError();
                    return false;
                }
            }
            $page = $page + 1;
            $this->sendInactivePlanNotification($limit, $page);
        }
        return true;
    }

    public static function getAllLangs()
    {
        $srch = new SearchBase(static::DB_TBL, 'subplan');
        $srch->addMultipleFields(['subplan.subplan_id', 'subplan.subplan_title', 'IFNULL(splang.subplang_subplan_title, subplan.subplan_title) AS plan_name', 'splang.subplang_subplan_id', 'splang.subplang_lang_id']);
        $srch->addFld('IFNULL(splang.subplang_subplan_title, subplan.subplan_title) AS plan_name');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'splang.subplang_subplan_id = subplan.subplan_id', 'splang');
        $srch->doNotCalculateRecords();
        $records =  FatApp::getDb()->fetchAll($srch->getResultSet());
        $mappedData = array();
        $identifierData = array();
        foreach ($records as $key => $value) {
            if ($value['subplang_lang_id']) {
                $mappedData[$value['subplan_id']][$value['subplang_lang_id']] = $value['plan_name'];
            }
            $identifierData[$value['subplan_id']] = $value['plan_name'];
        }
        $allLangs = Language::getAllNames(true);
        foreach ($identifierData as $planId => $value) {
            foreach ($allLangs as $langId => $value) {
                if (!isset($mappedData[$planId][$langId])) {
                    $mappedData[$planId][$langId] = $identifierData[$planId];
                }
            }
        }
        return $mappedData;
    }

    public static function activeLearnerPlans()
    {
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $cond = $srch->addCondition('ordsplan.ordsplan_status', '=', OrderSubscriptionPlan::ACTIVE);
        $cond->attachCondition('ordsplan.ordsplan_status', '=', OrderSubscriptionPlan::EXPIRED, 'OR');
        $srch->doNotCalculateRecords();
        $srch->addFld('DISTINCT ordsplan_plan_id');
        $activePlans =  FatApp::getDb()->fetchAll($srch->getResultSet());
        if($activePlans){
            $activePlans = array_column($activePlans, 'ordsplan_plan_id');
        }
        return $activePlans;
    }
}
