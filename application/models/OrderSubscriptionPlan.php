<?php

/**
 * This class is used to handle Subscription Orders
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderSubscriptionPlan extends MyAppModel
{

    const DB_TBL = 'tbl_order_subscription_plans';
    const DB_TBL_PREFIX = 'ordsplan_';

    /* Subscription statuses */
    const PENDING = 1;
    const ACTIVE = 2;
    const CANCELLED = 3;
    const COMPLETED = 4;
    const EXPIRED = 5;

    public $userId;


    /**
     * Initialize Order Subscription Class
     *
     * @param int $id
     * @param int $userId
     * @param int $userType
     * @param int $langId
     */
    public function __construct(int $id = 0, int $userId = 0)
    {
        $this->userId = $userId;
        parent::__construct(static::DB_TBL, 'ordsplan_id', $id);
    }

    /**
     * Get Statuses
     *
     * @param int $key
     * @return string|array
     */
    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::PENDING => Label::getLabel('LBL_PENDING'),
            static::ACTIVE => Label::getLabel('LBL_ACTIVE'),
            static::CANCELLED => Label::getLabel('LBL_CANCELLED'),
            static::EXPIRED => Label::getLabel('LBL_ACTIVE'),
            static::COMPLETED => Label::getLabel('LBL_COMPLETED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public static function getActivePlan($userId)
    {

        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'subplan.subplan_id = ordsplan.ordsplan_plan_id', 'subplan');
        $srch->addCondition('ordsplan.ordsplan_user_id', '=', $userId);
        $cond = $srch->addCondition('ordsplan.ordsplan_status', '=', OrderSubscriptionPlan::ACTIVE);
        $cond->attachCondition('ordsplan.ordsplan_status', '=', OrderSubscriptionPlan::EXPIRED, 'OR');
        $srch->addCondition('ordsplan.ordsplan_end_date', '>', date('Y-m-d H:i:s'));
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row;
    }

    public function getSubOrdByIds(array $subOrdIds, $langId = 0)
    {
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        if ($langId) {
            $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'subplan.subplan_id = ordsplan.ordsplan_plan_id', 'subplan');
            $srch->joinTable(SubscriptionPlan::DB_TBL_LANG, 'LEFT JOIN', 'subplang.subplang_subplan_id = subplan.subplan_id and subplang.subplang_lang_id = ' . $langId, 'subplang');
            $srch->addMultipleFields([
                'ordsplan.ordsplan_id',
                'ordsplan.ordsplan_start_date',
                'ordsplan.ordsplan_end_date',
                'ordsplan.ordsplan_validity',
                'ordsplan.ordsplan_duration',
                'ordsplan.ordsplan_used_lesson_count',
                'ordsplan.ordsplan_created',
                'ordsplan.ordsplan_updated',
                'ordsplan.ordsplan_status',
                'ordsplan.ordsplan_reward_discount',
                'ordsplan.ordsplan_discount',
                'IFNULL(subplang.subplang_subplan_title, subplan.subplan_title) AS plan_name',
                'ordsplan_start_date', 'ordsplan_end_date'
            ]);
        }
        $srch->addFld('ordsplan.ordsplan_lessons');
        $srch->addFld('ordsplan.ordsplan_amount');
        if (!empty($subOrdIds)) {
            $srch->addCondition('ordsplan.ordsplan_id', 'IN', $subOrdIds);
        }
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public function updateLessonCount($lesCount = 1): bool
    {
        $query = "UPDATE " . static::DB_TBL . " SET `ordsplan_used_lesson_count` = `ordsplan_used_lesson_count` + 
        " . $lesCount . " WHERE `ordsplan_id` = " .  $this->getMainTableRecordId();
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function validateSubscription($status = OrderSubscriptionPlan::ACTIVE)
    {
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $srch->addCondition('ordsplan_user_id', '=', $this->userId);
        $srch->addCondition('ordsplan_status', '=', $status);
        $srch->addCondition('ordsplan_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ordsplan_end_date', '>', date('Y-m-d H:i:s'));
        $srch->doNotLimitRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return !empty($row) ? $row : false;
    }

    public function getRefundableLessons()
    {
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->addCondition('ordles.ordles_status', 'IN', [Lesson::UNSCHEDULED, Lesson::SCHEDULED]);
        $srch->addCondition('ordles.ordles_ordsplan_id', '=', $this->getMainTableRecordId());
        $cond = $srch->addCondition('ordles.ordles_lesson_starttime', '>', date('Y-m-d H:i:s'));
        $cond->attachCondition('ordles.ordles_lesson_starttime', 'IS', 'mysql_func_NULL', 'OR', true);
        $srch->addFld('ordles.ordles_id');
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public function cancelLessons(array $lessonIds)
    {
        $status = true;
        foreach ($lessonIds as $key => $lessonId) {
            $tabelRecord = new TableRecord(Lesson::DB_TBL);
            $data = ['ordles_id' => $lessonId, 'ordles_status' => Lesson::CANCELLED, 'ordles_updated' => date('Y-m-d H:i:s')];
            $tabelRecord->assignValues($data);
            if (!$tabelRecord->addNew(['HIGH_PRIORITY'], $data)) {
                $this->error = $tabelRecord->getError();
                $status = false;
                break;
            }
        }
        return $status;
    }

    public function refundToLearner(array $subscrp, int $langId)
    {
        if (!$subscrp) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $lessons =  $this->getRefundableLessons();
        $lessonIds = array_column($lessons, 'ordles_id');
        if (!$this->cancelLessons($lessonIds)) {
            return false;
        }
        $totalLesRefund = count($lessonIds) + ($subscrp['ordsplan_lessons'] - $subscrp['ordsplan_used_lesson_count']);
        $refundAmt = round($totalLesRefund * $this->getLessonPrice($subscrp), 2);

        $this->assignValues([
            'ordsplan_refund' => $refundAmt,
            'ordsplan_updated' => date('Y-m-d H:i:s')
        ]);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        if ($refundAmt > 0) {
            $txn = new Transaction($this->userId, Transaction::TYPE_SUBPLAN_REFUND);
            $comment = Label::getLabel('LBL_SUBSCRIPTION_PLAN_REFUNDED_{order-id}', $langId);
            $comment = str_replace('{order-id}', Order::formatOrderId($subscrp['ordsplan_order_id']), $comment);
            if (!$txn->credit($refundAmt, $comment)) {
                $this->error = $txn->getError();
                return false;
            }
            $txn->sendEmail();
            $notifiVar = ['{amount}' => MyUtility::formatMoney(abs($refundAmt)), '{reason}' => Label::getLabel('LBL_WALLET_ORDER_PAYMENT', $langId)];
            $notifi = new Notification($this->userId, Notification::TYPE_WALLET_CREDIT);
            $notifi->sendNotification($notifiVar);
        }
        return true;
    }

    /* 
    1. calculate count of refundable lessons
    2. cancel lesson scheduled (time to come) , unscheduled 
    3. refund to learner
    3. cancel subscription
     */
    public function cancel($subscrp)
    {
        $db = FatApp::getDb();
        $db->startTransaction();

        $this->assignValues(['ordsplan_status' => static::CANCELLED, 'ordsplan_updated' => date('Y-m-d H:i:s')]);
        if (!$this->save()) {
            $db->rollbackTransaction();
            return false;
        }
        $user = User::getAttributesById($subscrp['ordsplan_user_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id', 'user_timezone', 'user_id']);
        if (!$this->refundToLearner($subscrp, $user['user_lang_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        $this->sendCancelledNotificationToLearner($user);
        $this->sendCancelledNotificationToAdmin($user);
        $db->commitTransaction();
        return true;
    }

    public function getCompletedLessonCount($userId)
    {
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->addCondition('ordles.ordles_ordsplan_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ordles.ordles_status', '=', Lesson::COMPLETED);
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->getResultSet();
        return $srch->recordCount();
    }


    public function markExpired(array $subscrp)
    {
        $this->assignValues(['ordsplan_status' => static::EXPIRED, 'ordsplan_updated' => date('Y-m-d H:i:s')]);
        if (!$this->save()) {
            return false;
        }
        $user = User::getAttributesById($subscrp['ordsplan_user_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id', 'user_timezone', 'user_id']);
        if (!$this->sendExhaustedNotificationToLearner($user)) {
            return false;
        }
        return true;
    }

    public function markCompleted()
    {
        $this->assignValues(['ordsplan_status' => static::COMPLETED, 'ordsplan_updated' => date('Y-m-d H:i:s')]);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /*
        1. get EXPIRED subscription order
        2. cancel EXPIRED subscription order
        3. check wallet balance
        4. create new subscrtipion order
     */
    public function renew(array $subscrp): bool
    {
        $walletPay = PaymentMethod::getByCode(WalletPay::KEY);
        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$this->markCompleted()) {
            $db->rollbackTransaction();
            return false;
        }
        
        $subscrpNew = [
            'order_item_count' => 1,
            'order_net_amount' => $subscrp['ordsplan_amount'],
            'order_pmethod_id' => $walletPay['pmethod_id'],
            'ordsplan_user_id' => $subscrp['ordsplan_user_id'],
            'ordsplan_duration' => $subscrp['ordsplan_duration'],
            'ordsplan_validity' => $subscrp['ordsplan_validity'],
            'ordsplan_amount' => $subscrp['ordsplan_amount'],
            'ordsplan_plan_id' => $subscrp['ordsplan_plan_id'],
            'ordsplan_lessons' => $subscrp['ordsplan_lessons'],
            'ordsplan_payment' => AppConstant::UNPAID,
            'ordsplan_start_date' => $subscrp['ordsplan_start_date'],
            'ordsplan_end_date' => $subscrp['ordsplan_end_date']
        ];
        $order = new Order(0, $subscrp['ordsplan_user_id']);
        if (!$order->renewSubscriptionPlan($subscrpNew)) {
            $db->rollbackTransaction();
            $this->error = $order->getError();
            return false;
        }
        $orderId = $order->getMainTableRecordId();
        $orderData = Order::getAttributesById($orderId, ['order_id', 'order_type', 'order_user_id', 'order_net_amount']);
        $walletPayObj = new WalletPay($orderData);
        if (!$data = $walletPayObj->getChargeData()) {
            $db->rollbackTransaction();
            $this->error =  $walletPayObj->getError();
            return false;
        }
        $res = $walletPayObj->callbackHandler($data);
        if ($res['status'] == AppConstant::NO) {
            $db->rollbackTransaction();
            $this->error =  $walletPayObj->getError();
            return false;
        }

        $user = User::getAttributesById($subscrp['ordsplan_user_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id', 'user_timezone', 'user_id']);
        if (!$this->sendRenewNotificationToLearner($user, $subscrpNew)) {
            $db->rollbackTransaction();
            return false;
        }

        $db->commitTransaction();
        return true;
    }

    public function getLessonPrice($subscrp = [])
    {
        if ($subscrp) {
            $amt =  $subscrp['ordsplan_amount'] - ($subscrp['ordsplan_discount'] + $subscrp['ordsplan_reward_discount']);
        } else {
            $subscrp = current($this->getSubOrdByIds([$this->getMainTableRecordId()]));
            $amt = $subscrp['ordsplan_amount'];
        }
        return  $amt / $subscrp['ordsplan_lessons'];
    }

    /**
     * Send Cancel subscription system notification and email to learner 
     * 
     * @param array $subscription
     * @return bool
     */
    private function sendCancelledNotificationToLearner(array $user): bool
    {
        $subPlan = current($this->getSubOrdByIds([$this->getMainTableRecordId()], $user['user_lang_id']));
        $notifiVar = ['{plan_name}' => $subPlan['plan_name']];
        $notifi = new Notification($user['user_id'], Notification::TYPE_SUB_PLAN_CANCELLED);
        $notifi->sendNotification($notifiVar);
        $vars = [
            '{learner_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
            '{plan_name}' => $subPlan['plan_name']
        ];
        $mail = new FatMailer($user['user_lang_id'], 'subscription_plan_cancelled_mail_to_learner');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$user['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Send Cancel subscription system notification and email to ADMIn 
     * 
     * @param array $subscription
     * @return bool
     */
    private function sendCancelledNotificationToAdmin(array $user): bool
    {
        $langId = FatApp::getConfig("CONF_DEFAULT_LANG");
        $subPlan = current($this->getSubOrdByIds([$this->getMainTableRecordId()], $langId));
        $adminTimeZone = MyUtility::getSuperAdminTimeZone();
        $startDate = MyDate::formatDate($subPlan['ordsplan_start_date'], 'Y-m-d H:i:s', $adminTimeZone);
        $startDate = MyDate::showDate($startDate, true) . ' (' . ($adminTimeZone ?? MyUtility::getSiteTimezone()) . ')';
        $endDate = MyDate::formatDate($subPlan['ordsplan_end_date'], 'Y-m-d H:i:s', $adminTimeZone);
        $endDate = MyDate::showDate($endDate, true) . ' (' . ($adminTimeZone ?? MyUtility::getSiteTimezone()) . ')';
        $vars = [
            '{learner_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
            '{plan_name}' => $subPlan['plan_name'],
            '{start_date}' => $startDate,
            '{end_date}' => $endDate
        ];
        $mail = new FatMailer(FatApp::getConfig("CONF_DEFAULT_LANG"), 'subscription_plan_cancelled_mail_to_admin');
        $mail->setVariables($vars);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Send Cancel subscription system notification and email to learner 
     * 
     * @param array $subscription
     * @return bool
     */
    private function sendRenewNotificationToLearner(array $user, array $subs): bool
    {
        $subPlan = current($this->getSubOrdByIds([$this->getMainTableRecordId()], $user['user_lang_id']));
        $notifiVar = ['{plan_name}' => $subPlan['plan_name']];
        $notifi = new Notification($user['user_id'], Notification::TYPE_SUB_PLAN_RENEWED);
        $notifi->sendNotification($notifiVar);
        $subPlan['ordsplan_start_date'] = MyDate::convert($subs['ordsplan_start_date'], $user['user_timezone']);
        $subPlan['ordsplan_end_date'] = MyDate::convert($subs['ordsplan_end_date'], $user['user_timezone']);
        $vars = [
            '{learner_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
            '{plan_name}' => $subPlan['plan_name'],
            '{start_date}' => MyDate::showDate($subPlan['ordsplan_start_date'], true, $user['user_lang_id']),
            '{end_date}' => MyDate::showDate($subPlan['ordsplan_end_date'], true, $user['user_lang_id']),
            '{amount}' => MyUtility::formatMoney($subPlan['ordsplan_amount'])
        ];
        $mail = new FatMailer($user['user_lang_id'], 'subscription_plan_renewed_mail_to_learner');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$user['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Send plan exhausted email to learner 
     * 
     * @param array $subscription
     * @return bool
     */
    private function sendExhaustedNotificationToLearner(array $user): bool
    {
        $subPlan = current($this->getSubOrdByIds([$this->getMainTableRecordId()], $user['user_lang_id']));
        $notifiVar = ['{plan_name}' => $subPlan['plan_name']];
        $notifi = new Notification($user['user_id'], Notification::TYPE_SUB_PLAN_EXPIRED);
        $notifi->sendNotification($notifiVar);
        $subPlan['ordsplan_start_date'] = MyDate::convert($subPlan['ordsplan_start_date'], $user['user_timezone']);
        $subPlan['ordsplan_end_date'] = MyDate::convert($subPlan['ordsplan_end_date'], $user['user_timezone']);
        $vars = [
            '{learner_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
            '{plan_name}' => $subPlan['plan_name'],
            '{start_date}' => MyDate::showDate($subPlan['ordsplan_start_date'], true, $user['user_lang_id']),
            '{end_date}' => MyDate::showDate($subPlan['ordsplan_end_date'], true, $user['user_lang_id']),
            '{amount}' => MyUtility::formatMoney($subPlan['ordsplan_amount'])
        ];
        $mail = new FatMailer($user['user_lang_id'], 'subscription_plan_exhausted_mail_to_learner');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$user['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    public static function getByPlanId($planID, $limit, $offset)
    {
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_user_id = user.user_id', 'user');
        $cond = $srch->addCondition('ordsplan_status', '=', OrderSubscriptionPlan::ACTIVE);
        $cond->attachCondition('ordsplan_status', '=', OrderSubscriptionPlan::EXPIRED, 'OR');
        $srch->addCondition('user.user_active', '=',  AppConstant::ACTIVE);
        $srch->addCondition('ordsplan_plan_id', '=',  $planID);
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->doNotCalculateRecords();
        $srch->setPageSize($limit);
        $srch->setPageNumber($offset);
        $srch->addMultipleFields([
            'user.user_first_name',
            'user.user_last_name',
            'user.user_email',
            'user.user_lang_id',
            'user.user_id'
        ]);
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Unpaid Subscription Plans
     * 
     * @param int $userId
     * @return null|array
     */
    public static function getUnpaidOrders(int $userId)
    {
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_order_id = orders.order_id', 'orders');
        $srch->addMultipleFields(['order_id', 'order_type', 'order_user_id', 'order_reward_value']);
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addCondition('orders.order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('orders.order_type', '=', Order::TYPE_SUBPLAN);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_INPROCESS);
        $srch->addOrder('order_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Unpaid Subscription Plans
     * 
     * @param int $userId
     * @return null|array
     */
    public static function cancelPendingOrders(int $userId)
    {
        $tableRecord = new TableRecord(static::DB_TBL);
        $tableRecord->assignValues(['ordsplan_status' => static::CANCELLED]);
        if (!$tableRecord->update(['smt' => 'ordsplan_user_id = ? and ordsplan_status = ?', 'vals' => [$userId, OrderSubscriptionPlan::PENDING]])) {
            return false;
        }
    }

    /**
     * Get Unpaid Subscription Plans
     * 
     * @param int $userId
     * @return null|array
     */
    public static function completeExpiredPlans(int $userId)
    {
        $tableRecord = new TableRecord(static::DB_TBL);
        $tableRecord->assignValues(['ordsplan_status' => static::COMPLETED]);
        if (!$tableRecord->update(['smt' => 'ordsplan_user_id = ? and (ordsplan_status = ? OR ordsplan_status = ?) and ordsplan_end_date < ?', 'vals' => [$userId, OrderSubscriptionPlan::ACTIVE, OrderSubscriptionPlan::EXPIRED, date('Y-m-d H:i:s')]])) {
            return false;
        }
    }
}
