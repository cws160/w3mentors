<?php

/**
 * This class is used to handle Subscription
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Subscription extends MyAppModel
{

    const DB_TBL = 'tbl_order_subscriptions';
    const DB_TBL_PREFIX = 'ordsub_';
    /* Subscription Status */
    const ACTIVE = 1;
    const COMPLETED = 2;
    const CANCELLED = 3;
    const EXPIRED = 4;

    private $userId;
    private $userType;

    /**
     * Initialize Subscription
     * 
     * @param int $id
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $id = 0, int $userId = 0, int $userType = 0)
    {
        $this->userId = $userId;
        $this->userType = $userType;
        parent::__construct(static::DB_TBL, 'ordsub_id', $id);
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
            static::ACTIVE => Label::getLabel('LBL_ACTIVE'),
            static::EXPIRED => Label::getLabel('LBL_EXPIRED'),
            static::COMPLETED => Label::getLabel('LBL_COMPLETED'),
            static::CANCELLED => Label::getLabel('LBL_CANCELLED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Subscription To Cancel
     * 
     * @return bool|array
     */
    public function getSubscriptionToCancel()
    {
        $srch = new SearchBase(Subscription::DB_TBL, 'ordsub');
        $srch->addMultipleFields([
            'ordsub.ordsub_teacher_id', 'ordsub.ordsub_teacher_id as teacher_id', 'ordsub_id',
            'teacher.user_first_name as teacher_first_name', 'teacher.user_last_name as teacher_last_name',
            'teacher.user_email as teacher_email', 'teacher.user_timezone as teacher_timezone',
            'teacher.user_lang_id as teacher_lang_id', 'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name', 'learner.user_lang_id as learner_lang_id',
            'order_user_id', 'order_user_id as learner_id', 'order_discount_value', 'order_reward_value'
        ]);
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordsub.ordsub_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordsub.ordsub_teacher_id', 'teacher');
        $srch->addCondition('order_user_id', '=', $this->userId);
        $srch->addCondition('ordsub_id', '=', $this->mainTableRecordId);
        $srch->addCondition('ordsub_status', '=', static::ACTIVE);
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordsub_enddate', '>', date('Y-m-d H:i:s'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_SUBSCRIPTION_NOT_FOUND');
            return false;
        }
        return $row;
    }

    /**
     * Cancel Lesson
     * 
     * @param array $post
     * @return bool
     */
    public function cancel(array $post): bool
    {
        if (!$subscription = $this->getSubscriptionToCancel()) {
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $this->assignValues(['ordsub_status' => static::CANCELLED, 'ordsub_updated' => date('Y-m-d H:i:s')]);
        if (!$this->save()) {
            return false;
        }
        $db->commitTransaction();
        $subscription['comment'] = $post['comment'];
        $this->sendCancelledNotification($subscription);
        return true;
    }

    /**
     * Send Cancel subscription system notification and email to teacher 
     * 
     * @param array $subscription
     * @return bool
     */
    private function sendCancelledNotification(array $subscription): bool
    {
        $vars = [
            '{learner_name}' => $subscription['learner_first_name'] . ' ' . $subscription['learner_last_name'],
            '{comment}' => $subscription['comment']
        ];
        $notifi = new Notification($subscription['ordsub_teacher_id'], Notification::TYPE_SUBSCRIPTION_CANCELLED);
        $notifi->sendNotification($vars, User::TEACHER);
        $vars = [
            '{learner_name}' => $subscription['learner_first_name'] . ' ' . $subscription['learner_last_name'],
            '{teacher_name}' => $subscription['teacher_first_name'] . ' ' . $subscription['teacher_last_name'],
            '{teacher_comment}' => nl2br($subscription['comment']),
            '{subscription_id}' => $subscription['ordsub_id'],
        ];
        $mail = new FatMailer($subscription['teacher_lang_id'], 'teacher_subscription_cancelled_email');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$subscription['teacher_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Subs By Order Id
     * 
     * @param int $orderId
     * @param array $flds
     * @return null|array
     */
    public static function getSubsByOrderId(int $orderId, array $flds = null)
    {
        $srch = new SearchBase(Subscription::DB_TBL, 'ordsub');
        if (!is_null($flds)) {
            $srch->addMultipleFields($flds);
        }
        $srch->addCondition('ordsub_order_id', '=', $orderId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

}
