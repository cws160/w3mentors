<?php

/**
 * This class is used to handle Package Orders
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderPackage extends MyAppModel
{

    const DB_TBL = 'tbl_order_packages';
    const DB_TBL_PREFIX = 'ordpkg_';
    const SCHEDULED = 1;
    const COMPLETED = 2;
    const CANCELLED = 3;

    private $userId;
    private $userType;

    public function __construct(int $id = 0, int $userId = 0, int $userType = 0)
    {
        $this->userId = $userId;
        $this->userType = $userType;
        parent::__construct(static::DB_TBL, 'ordpkg_id', $id);
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
            static::SCHEDULED => Label::getLabel('LBL_SCHEDULED'),
            static::COMPLETED => Label::getLabel('LBL_COMPLETED'),
            static::CANCELLED => Label::getLabel('LBL_CANCELLED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public static function getByOrderId(int $orderId, int $langId = 0)
    {
        $srch = new SearchBase(static::DB_TBL, 'ordpkg');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordpkg.ordpkg_package_id', 'grpcls');
        $srch->addMultipleFields(['ordpkg.ordpkg_package_id', 'grpcls.grpcls_title as package_name', 'ordpkg_offline']);
        if ($langId > 0) {
            $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id and gclang_lang_id = ' . $langId, 'gclang');
            $srch->addFld('IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as package_name');
        }
        $srch->addCondition('ordpkg_order_id', '=', $orderId);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Unpaid Seats
     * 
     * @param array $packagesIds
     * @return array
     */
    public static function getUnpaidSeats(array $packagesIds): array
    {
        if (empty($packagesIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'ordpkg');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordpkg.ordpkg_order_id', 'orders');
        $srch->addMultipleFields(['ordpkg_package_id', 'count(*) as totalSeats']);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_INPROCESS);
        $srch->addCondition('orders.order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('ordpkg.ordpkg_package_id', 'IN', $packagesIds);
        $srch->addGroupBy('ordpkg_package_id', 'ASC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Unpaid Package
     * 
     * @param int $userId
     * @param int $packageId
     * @return null|array
     */
    public static function getUnpaidPackage(int $userId, int $packageId)
    {
        $srch = new SearchBase(static::DB_TBL, 'ordpkg');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordpkg.ordpkg_order_id', 'orders');
        $srch->joinTable(Coupon::DB_TBL_HISTORY, 'LEFT JOIN', 'couhis.couhis_order_id = orders.order_id', 'couhis');
        $srch->addMultipleFields(['order_id', 'couhis_id', 'order_type', 'couhis_coupon_id', 'order_user_id', 'order_reward_value']);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_INPROCESS);
        $srch->addCondition('orders.order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('ordpkg.ordpkg_package_id', '=', $packageId);
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addOrder('orders.order_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Package To Cancel
     * 
     * @param int $packageId
     * @param int $langId
     * @return bool|array
     */
    public function getPackageToCancel(int $packageId, int $langId = 0)
    {
        $srch = new SearchBase(static::DB_TBL, 'ordpkg');
        $srch->addMultipleFields([
            'grpcls.grpcls_id', 'order_id', 'order_user_id', 'ordpkg_package_id',
            'grpcls_start_datetime', 'grpcls_booked_seats', 'ordcls_amount', 'order_net_amount',
            'ordcls_discount', 'ordcls_reward_discount', 'grpcls.grpcls_title as package_name',
            'teacher.user_id as teacher_id',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'teacher.user_email as teacher_email',
            'teacher.user_lang_id as teacher_lang_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'learner.user_lang_id as learner_lang_id',
        ]);
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordpkg.ordpkg_package_id', 'grpcls');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
        if ($langId > 0) {
            $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id and gclang_lang_id = ' . $langId, 'gclang');
            $srch->addFld('IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as package_name');
        }
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordpkg.ordpkg_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->joinTable(OrderClass::DB_TBL, 'INNER JOIN', 'ordcls.ordcls_order_id = ordpkg.ordpkg_order_id', 'ordcls');
        $srch->addCondition('orders.order_user_id', '=', $this->userId);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordpkg.ordpkg_package_id', '=', $packageId);
        $srch->addCondition('ordpkg.ordpkg_status', '=', static::SCHEDULED);
        $srch->addCondition('orders.order_user_id', '=', $this->userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_PACKAGE_NOT_FOUND');
            return false;
        }
        $duration = FatApp::getConfig('CONF_CLASS_CANCEL_DURATION', FatUtility::VAR_INT, 24);
        $startTime = strtotime($row['grpcls_start_datetime'] . ' -' . $duration . ' hours');
        if (time() >= $startTime) {
            $this->error = Label::getLabel('LBL_TIME_TO_CANCEL_CLASS_PASSED');
            return false;
        }
        return $row;
    }

    /**
     * Cancel Class Package
     * 
     * @param int $packageId
     * @param int $langId
     * @return bool
     */
    public function cancelPackage(int $packageId, int $langId): bool
    {
        if (!$package = $this->getPackageToCancel($packageId, $langId)) {
            return false;
        }
        $orderClses = OrderClass::getOrdClsByPackageId($packageId, $this->userId);
        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$db->updateFromArray(static::DB_TBL, ['ordpkg_status' => static::CANCELLED], ['smt' => 'ordpkg_order_id = ?', 'vals' => [$package['order_id']]])) {
            $this->error = $db->getError();
            $db->rollbackTransaction();
            return false;
        }
        $singleClsRefundAmt = $package['ordcls_amount'] - $package['ordcls_discount'] - $package['ordcls_reward_discount'];
        $totalRefundAmt = $singleClsRefundAmt * count($orderClses);
        $totalRefundAmt = min($totalRefundAmt, $package['order_net_amount']);
        $record = new TableRecord(OrderClass::DB_TBL);
        $record->assignValues(['ordcls_refund' => $singleClsRefundAmt, 'ordcls_status' => OrderClass::CANCELLED, 'ordcls_updated' => date('Y-m-d H:i:s')]);
        $whr = ['smt' => 'ordcls_order_id = ? and ordcls_status = ?', 'vals' => [$package['order_id'], OrderClass::SCHEDULED]];
        if (!$record->update($whr)) {
            $this->error = $record->getError();
            $db->rollbackTransaction();
            return false;
        }
        if ($totalRefundAmt > 0) {
            $txn = new Transaction($package['order_user_id'], Transaction::TYPE_LEARNER_REFUND);
            $comment = Label::getLabel('LBL_CANCEL_PACKAGE_REFUND_{package-id}_{refund-percentage}', $package['learner_lang_id']);
            $comment = str_replace(['{package-id}', '{refund-percentage}'], [$package['ordpkg_package_id'], 100], $comment);
            if (!$txn->credit($totalRefundAmt, $comment)) {
                $this->error = $txn->getError();
                $db->rollbackTransaction();
                return false;
            }
            $txn->sendEmail();
            $notifiVar = ['{amount}' => MyUtility::formatMoney(abs($totalRefundAmt)), '{reason}' => Label::getLabel('LBL_WALLET_ORDER_PAYMENT', $package['learner_lang_id'])];
            $notifi = new Notification($package['order_user_id'], Notification::TYPE_WALLET_CREDIT);
            $notifi->sendNotification($notifiVar);
        }
        if (!$this->updateBookedSeatsCount($packageId, -1)) {
            $this->error = $record->getError();
            $db->rollbackTransaction();
            return false;
        }
        foreach ($orderClses as $value) {
            $record = new TableRecord(GroupClass::DB_TBL);
            $record->setFldValue('grpcls_booked_seats', 'mysql_func_grpcls_booked_seats - 1', true);
            if (!$record->update(['smt' => 'grpcls_id = ?', 'vals' => [$value['grpcls_id']]])) {
                $this->error = $record->getError();
                $db->rollbackTransaction();
                return false;
            }
            $thread = new Thread(0);
            if ($thread->groupThreadExist($value['grpcls_id'])) {
                if (!$thread->deleteThreadUser($this->userId)) {
                    $this->error = $thread->getError();
                    $db->rollbackTransaction();
                    return false;
                }
            }
        }
        $db->commitTransaction();
        $googleCalendar = new GoogleCalendarEvent($this->userId, $packageId, AppConstant::GCLASS);
        $googleCalendar->removeLearnerPackEvents();
        $this->sendCancelNotification($package);
        return true;
    }

    /**
     * Already Booked Class
     * 
     * @param int $userId
     * @param array $packageIds
     * @return array
     */
    public static function userBooked(int $userId, array $packageIds): array
    {
        if (empty($userId) || empty($packageIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'ordpkg');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordpkg.ordpkg_order_id', 'orders');
        $srch->addFld('ordpkg.ordpkg_package_id AS grpcls_id');
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addCondition('ordpkg.ordpkg_package_id', 'IN', $packageIds);
        $srch->addCondition('ordpkg.ordpkg_status', '!=', static::CANCELLED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $bookedPackages = array_column($records, 'grpcls_id', 'grpcls_id');
        return $bookedPackages;
    }

    /**
     * Update Booked Seats Count
     * 
     * @param int $packageId
     * @param int $count
     * @return bool
     */
    public function updateBookedSeatsCount(int $packageId, int $count = 1): bool
    {
        $record = new TableRecord(GroupClass::DB_TBL);
        $record->setFldValue('grpcls_booked_seats', 'mysql_func_grpcls_booked_seats + ' . $count, true);
        if (!$record->update(['smt' => 'grpcls_id = ?', 'vals' => [$packageId]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Send Cancel Package Notification
     * 
     * @param array $package
     */
    private function sendCancelNotification(array $package)
    {
        $noti = new Notification($package['teacher_id'], Notification::TYPE_PACKAGE_CANCELLED);
        $noti->sendNotification(['{package_name}' => $package['package_name']], User::TEACHER);
        $mail = new FatMailer(1, 'learner_package_cancelled_email');
        $vars = [
            '{package_name}' => $package['package_name'],
            '{learner_name}' => $package['learner_first_name'] . ' ' . $package['learner_last_name'],
            '{teacher_name}' => $package['teacher_first_name'] . ' ' . $package['teacher_last_name']
        ];
        $mail->setVariables($vars);
        $mail->sendMail([$package['teacher_email']]);
    }

    /**
     * Get Learners
     * 
     * @param int $packageId
     * @return array
     */
    public static function getLearners(int $packageId): array
    {
        $srch = new SearchBase(static::DB_TBL, 'ordpkg');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordpkg.ordpkg_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->addMultipleFields(['user_first_name', 'user_last_name', 'user_email', 'user_gender', 'learner.user_id']);
        $srch->addCondition('ordpkg.ordpkg_package_id', '=', $packageId);
        $srch->addCondition('ordpkg.ordpkg_status', '!=', static::CANCELLED);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addGroupBy('learner.user_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }
}
