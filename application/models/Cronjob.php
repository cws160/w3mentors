<?php

/**
 * This class is used to handle CronJob
 * @package W3Mentors
 * @author Fatbit Team
 */
class Cronjob
{

    /**
     * Send Archived Emails
     * 
     * @return string
     */
    public function sendArchivedEmails(): string
    {
        $srch = new SearchBase(FatMailer::DB_TBL_ARCHIVE);
        $srch->addCondition('earch_senton', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('earch_attempted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addOrder('earch_id', 'ASC');
        $srch->setPageSize(15);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $mail = new FatMailer(0, '');
            if (!$mail->sendArchivedMail($row)) {
                return $mail->getError();
            }
        }
        return 'Archived Emails sent successfully';
    }

    /**
     * Send Class Reminder
     * 
     * @param int $type
     * @return string
     */
    public function sendClassReminder(int $type): string
    {
        if (!GroupClass::isEnabled()) {
            return 'Group class/package module not available';
        }
        $reminder = new Reminder();
        if (!$reminder->sendClassReminder($type)) {
            return $reminder->getError();
        }
        return 'Classes reminder sent successfully';
    }

    /**
     * Send Lesson Reminder
     * 
     * @param int $type
     * @return string
     */
    public function sendLessonReminder(int $type): string
    {
        $reminder = new Reminder();
        if (!$reminder->sendLessonReminder($type)) {
            return $reminder->getError();
        }
        return 'lessons reminder sent successfully';
    }

    /**
     * Send Lesson Reminder
     * 
     * @param int $type
     * @return string
     */
    public function sendWalletBalanceReminder(int $type): string
    {
        $reminder = new Reminder();
        if (!$reminder->sendWalletBalanceReminder($type)) {
            return $reminder->getError();
        }
        return 'Wallet balance reminder sent successfully';
    }

    /**
     * Send Subscription Plan Renew Reminder
     * 
     * @param int $type
     * @return string
     */
    public function sendSubscriptionPlanRenewReminder(int $type): string
    {
        if (!SubscriptionPlan::isEnabled()) {
            return 'Subscription Plan module not available';
        }
        $reminder = new Reminder();
        if (!$reminder->sendSubscriptionPlanRenewReminder($type)) {
            return $reminder->getError();
        }
        return 'Subscription Plan reminder sent successfully';
    }

    /**
     * Resolved Issue Settlement
     * 
     * @return string
     */
    public function resolvedIssueSettlement(): string
    {
        $issue = new Issue();
        if (!$issue->resolvedIssueSettlement()) {
            return $issue->getError();
        }
        return 'resolved issue Settlement successfully';
    }

    /**
     * Completed Lesson Settlement
     * 
     * @return string
     */
    public function completedLessonSettlement(): string
    {
        $issue = new Issue();
        if (!$issue->completedLessonSettlement()) {
            return $issue->getError();
        }
        return 'completed lessons settlement successfully';
    }

    /**
     * Completed Class Settlement
     * 
     * @return string
     */
    public function completedClassSettlement(): string
    {
        if (!GroupClass::isEnabled()) {
            return 'Class module not available';
        }
        $issue = new Issue();
        if (!$issue->completedClassSettlement()) {
            return $issue->getError();
        }
        return 'completed Classes settlement successfully';
    }

    /**
     * Update Availability
     * 
     * @return string
     */
    public function updateAvailabililty(): string
    {
        $availability = new Availability(0);
        if (!$availability->updateBySystem()) {
            return $availability->getError();
        }
        return 'Update the users Availabililty successfully';
    }

    /**
     * Send Unread Messages Notifications
     * 
     * @return string
     */
    public function sendUnreadMsgsNotifications(): string
    {
        $thread = new Thread(0);
        if (!$thread->sendUnreadMsgsNotifications()) {
            return $thread->getError();
        }
        return 'completed unread notification mail successfully';
    }

    /**
     * Cancel Pending Orders
     * 
     * @return string
     */
    public function cancelPendingOrders(): string
    {
        $bankTransfer = PaymentMethod::getByCode(BankTransferPay::KEY);
        $duration = FatApp::getConfig('CONF_CANCEL_ORDER_DURATION');
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->addMultipleFields([
            'order_id',
            'couhis_id',
            'order_type',
            'couhis_coupon_id',
            'order_user_id',
            'order_pmethod_id',
            'order_addedon',
            'order_user_id',
            'order_reward_value',
            'user_first_name',
            'user_last_name',
            'user_lang_id',
            'user_email'
        ]);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'orders.order_user_id = users.user_id', 'users');
        $srch->joinTable(Coupon::DB_TBL_HISTORY, 'LEFT JOIN', 'orders.order_id = couhis.couhis_order_id', 'couhis');
        $srch->addCondition('order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('order_status', '!=', Order::STATUS_CANCELLED);
        if (!empty($bankTransfer['pmethod_id'])) {
            $srch->addCondition('order_pmethod_id', '!=', $bankTransfer['pmethod_id']);
        }
        $srch->addCondition('mysql_func_DATE_ADD(order_addedon, INTERVAL ' . $duration . ' MINUTE)', '<=', date('Y-m-d H:i:s'), 'AND', true);
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            $order = new Order();
            if (!$order->cancelUnpaidOrder($row, true)) {
                return $order->getError();
            }
        }
        return 'Cancel pending orders successfully';
    }

    /**
     * Cancel Pending Orders
     * 
     * @return string
     */
    public function cancelBankTransPendingOrders(): string
    {

        $bankTransfer = PaymentMethod::getByCode(BankTransferPay::KEY);
        if (empty($bankTransfer['pmethod_id'])) {
            return 'Bank transfer payment gateway not active';
        }
        $duration = (new BankTransferPay([]))->getBookBeforeHours();
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->addMultipleFields([
            'order_id',
            'couhis_id',
            'order_type',
            'couhis_coupon_id',
            'order_user_id',
            'order_pmethod_id',
            'order_addedon',
            'order_user_id',
            'order_reward_value',
            'user_first_name',
            'user_last_name',
            'user_lang_id',
            'user_email'
        ]);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'orders.order_user_id = users.user_id', 'users');
        $srch->joinTable(Coupon::DB_TBL_HISTORY, 'LEFT JOIN', 'orders.order_id = couhis.couhis_order_id', 'couhis');
        $srch->addCondition('order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('order_status', '!=', Order::STATUS_CANCELLED);
        $srch->addCondition('order_pmethod_id', '=', $bankTransfer['pmethod_id']);
        $srch->addCondition('mysql_func_DATE_ADD(order_addedon, INTERVAL ' . $duration . ' HOUR)', '<=', date('Y-m-d H:i:s'), 'AND', true);
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            $order = new Order();
            if (!$order->cancelUnpaidOrder($row, true)) {
                return $order->getError();
            }
        }
        return 'Cancel Bank transfer pending orders successfully';
    }

    /**
     * Recurring Subscription
     * 
     * @return string
     * @todo Need to discuss this method
     */
    public function recurringSubscription(): string
    {
        $walletPay = PaymentMethod::getByCode(WalletPay::KEY);
        if (empty($walletPay)) {
            return "WALLET PAY IS NOT ACTIVE";
        }
        $db = FatApp::getDb();
        $srch = new SearchBase(Subscription::DB_TBL, 'ordsub');
        $srch->addMultipleFields([
            'order_id',
            'learner.user_id as learner_id',
            'order_discount_value',
            'order_net_amount',
            'ordsub_id', 'ordsub_startdate', 'ordsub_enddate',
            'learner.user_timezone as learner_timezone',
            'ordsub_teacher_id',
            'learner.user_lang_id as learner_lang_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'learner.user_email as learner_email',
            'DATEDIFF(ordsub_enddate, ordsub_startdate) as subdays',
            'order_total_amount',
            'ordsub_offline',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name'
        ]);
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordsub.ordsub_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordsub.ordsub_teacher_id', 'teacher');
        $srch->addDirectCondition('learner.user_deleted IS NULL');
        $srch->addDirectCondition('teacher.user_deleted IS NULL');
        $srch->addCondition('ordsub.ordsub_status', '=', Subscription::ACTIVE);
        $srch->addCondition('ordsub.ordsub_enddate', '<', date('Y-m-d H:i:s'));
        $srch->addCondition('mysql_func_DATE_ADD(ordsub_enddate, INTERVAL 1 HOUR)', '>', date('Y-m-d H:i:s'), 'AND', true);
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('order_status', '=', Order::STATUS_COMPLETED);
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $rows = $db->fetchAll($srch->getResultSet(), 'ordsub_id');
        $orderIds = array_column($rows, 'subdays', 'order_id');
        $orderLessons = $this->orderLessons($orderIds);
        foreach ($rows as $value) {
            if (empty($orderLessons[$value['order_id']])) {
                continue;
            }
            $ordLes = current($orderLessons[$value['order_id']]);
            if ($ordLes['tlang_active'] == false) {
                $tabelRecord = new TableRecord(Subscription::DB_TBL);
                $tabelRecord->assignValues(['ordsub_status' => Subscription::COMPLETED, 'ordsub_updated' => date('Y-m-d H:i:s')]);
                if (!$tabelRecord->update(['smt' => 'ordsub_id = ?', 'vals' => [$value['ordsub_id']]])) {
                    $db->rollbackTransaction();
                    return false;
                }
                $teachLang = TeachLanguage::getLangById($ordLes['ordles_tlang_id'], $value['learner_lang_id']);

                $notifiVar = ['{teach_language}' => $teachLang];
                $notifi = new Notification($value['learner_id'], Notification::TYPE_RECURRING_LESSON_SUBJECT_INACTIVE);
                $notifi->sendNotification($notifiVar);
                
                $vars = [
                    '{learner_name}' => $value['learner_first_name'] . ' ' . $value['learner_last_name'],
                    '{teach_language}' => $teachLang
                ];
                $mail = new FatMailer($value['learner_lang_id'], 'recurring_lesson_subject_inactive');
                $mail->setVariables($vars);
                $mail->sendMail([$value['learner_email']]);
                continue;
            }
            $userWalletBalance = User::getWalletBalance($value['learner_id']);
            $totalAmount = $value['order_total_amount'];
            $activePlan = OrderSubscriptionPlan::getActivePlan($value['learner_id']);
            if (!empty($activePlan)) {
                $subscription = new OrderSubscriptionPlan($activePlan['ordsplan_id']);
                $subPlan = current($subscription->getSubOrdByIds([$activePlan['ordsplan_id']], $value['learner_lang_id']));
                $tabelRecord = new TableRecord(Subscription::DB_TBL);
                $tabelRecord->assignValues(['ordsub_status' => Subscription::COMPLETED, 'ordsub_updated' => date('Y-m-d H:i:s')]);
                if (!$tabelRecord->update(['smt' => 'ordsub_id = ?', 'vals' => [$value['ordsub_id']]])) {
                    $db->rollbackTransaction();
                    return false;
                }
                $notifiVar = ['{teacher_name}' => $value['teacher_first_name'] . ' ' . $value['teacher_last_name']];
                $notifi = new Notification($value['learner_id'], Notification::TYPE_RECURRING_LESSON_COMPLETED);
                $notifi->sendNotification($notifiVar);

                $vars = [
                    '{learner_name}' => $value['learner_first_name'] . ' ' . $value['learner_last_name'],
                    '{teacher_name}' => $value['teacher_first_name'] . ' ' . $value['teacher_last_name'],
                    '{plan_name}' => $subPlan['plan_name']
                ];
                $mail = new FatMailer($value['learner_lang_id'], 'recurring_lesson_completed_due_to_subscriptionplan');
                $mail->setVariables($vars);
                $mail->sendMail([$value['learner_email']]);
                continue;
            }
            if ($totalAmount > $userWalletBalance) {
                /* handle subscription plan active here */
                $tabelRecord = new TableRecord(Subscription::DB_TBL);
                $tabelRecord->assignValues(['ordsub_status' => Subscription::CANCELLED, 'ordsub_updated' => date('Y-m-d H:i:s')]);
                if (!$tabelRecord->update(['smt' => 'ordsub_id = ?', 'vals' => [$value['ordsub_id']]])) {
                    $db->rollbackTransaction();
                    return false;
                }
                $notifiVar = ['{teacher_name}' => $value['teacher_first_name'] . ' ' . $value['teacher_last_name']];
                $notifi = new Notification($value['learner_id'], Notification::TYPE_RECURRING_LESSON_RENEWAL_FAILED);
                $notifi->sendNotification($notifiVar);
                
                $vars = [
                    '{learner_name}' => $value['learner_first_name'] . ' ' . $value['learner_last_name'],
                    '{current_balance}' => MyUtility::formatMoney($userWalletBalance),
                    '{subscription_amount}' => MyUtility::formatMoney($totalAmount),
                ];
                $mail = new FatMailer($value['learner_lang_id'], 'wallet_balance_low_for_subscription');
                $mail->setVariables($vars);
                $mail->sendMail([$value['learner_email']]);
                continue;
            }
            $lessons = $this->formatAndValidate($orderLessons[$value['order_id']], $value['learner_id']);
            $days = FatApp::getConfig('CONF_RECURRING_SUBSCRIPTION_WEEKS') * 7;
            $startEndDate = [
                'ordsub_startdate' => $value['ordsub_enddate'],
                'ordsub_enddate' => date('Y-m-d H:i:s', strtotime($value['ordsub_enddate'] . " + $days days"))
            ];
            $subscription = [
                'order_item_count' => count($lessons['lessons']),
                'order_net_amount' => $totalAmount,
                'ordles_type' => Lesson::TYPE_SUBCRIP,
                'order_pmethod_id' => $walletPay['pmethod_id'],
                'ordsub_teacher_id' => $value['ordsub_teacher_id'],
                'ordsub_offline' => $value['ordsub_offline'],
                'ordsub_startdate' => $startEndDate['ordsub_startdate'],
                'ordsub_enddate' => $startEndDate['ordsub_enddate'],
                'lessons' => $lessons['lessons']
            ];
            if (!$db->startTransaction()) {
                return $db->getError();
            }
            $order = new Order(0, $value['learner_id']);
            if (!$order->recurringSubscription($subscription)) {
                $db->rollbackTransaction();
                return $order->getError();
            }
            $orderId = $order->getMainTableRecordId();
            $orderData = Order::getAttributesById($orderId, ['order_id', 'order_type', 'order_user_id', 'order_net_amount']);
            if ($orderData['order_net_amount'] == 0) {
                $payment = new OrderPayment($orderId);
                if (!$payment->paymentSettlements('NA', 0, [])) {
                    $db->rollbackTransaction();
                    return $payment->getError();
                }
            } else {
                $walletPayObj = new WalletPay($orderData);
                if (!$data = $walletPayObj->getChargeData()) {
                    $db->rollbackTransaction();
                    return $walletPayObj->getError();
                }
                $res = $walletPayObj->callbackHandler($data);
                if ($res['status'] == AppConstant::NO) {
                    $db->rollbackTransaction();
                    return $walletPayObj->getError();
                }
            }
            $tabelRecord = new TableRecord(Subscription::DB_TBL);
            $tabelRecord->assignValues(['ordsub_status' => Subscription::COMPLETED, 'ordsub_updated' => date('Y-m-d H:i:s')]);
            if (!$tabelRecord->update(['smt' => 'ordsub_id = ?', 'vals' => [$value['ordsub_id']]])) {
                $db->rollbackTransaction();
                return $tabelRecord->getError();
            }
            $db->commitTransaction();
            $startEndDate['ordsub_startdate'] = MyDate::convert($startEndDate['ordsub_startdate'], $value['learner_timezone']);
            $startEndDate['ordsub_enddate'] = MyDate::convert($startEndDate['ordsub_enddate'], $value['learner_timezone']);
            $vars = [
                '{learner_name}' => $value['learner_first_name'] . ' ' . $value['learner_last_name'],
                '{start_time}' => MyDate::showDate($startEndDate['ordsub_startdate'], true, $value['learner_lang_id']),
                '{end_time}' => MyDate::showDate($startEndDate['ordsub_enddate'], true, $value['learner_lang_id']),
                '{seheduled_lessons}' => $lessons['scheduledCount'],
                '{unscheduled_lessons}' => $lessons['unScheduledCount'],
            ];
            $mail = new FatMailer($value['learner_lang_id'], 'recurring_subscription');
            $mail->setVariables($vars);
            $mail->sendMail([$value['learner_email']]);
        }
        return 'recurring subscription successfully';
    }

    /**
     * Order Lessons
     * 
     * @param array $orderIds
     * @return array
     */
    public function orderLessons(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->addMultipleFields([
            'ordles.ordles_tlang_id', 'ordles.ordles_duration', 'ordles_type', 'ordles_teacher_id',
            'ordles_tlang_id', 'ordles_commission', 'ordles.ordles_order_id',
            'ordles_teacher_id', 'ordles_lesson_starttime', 'ordles_amount',
            'ordles_lesson_starttime', 'ordles_lesson_endtime', 'ordles_offline', 'ordles_address', 'tlang.tlang_active'
        ]);
        $srch->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'tlang.tlang_id = ordles.ordles_tlang_id', 'tlang');
        $srch->addCondition('ordles_order_id', 'IN', array_keys($orderIds));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $resultSet = $srch->getResultSet();
        $orderLessons = [];
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            if (!empty($row['ordles_lesson_starttime'])) {
                $row['ordles_lesson_starttime'] = date("Y-m-d H:i:s", strtotime($row['ordles_lesson_starttime'] . " +" . $orderIds[$row['ordles_order_id']] . " days"));
                $row['ordles_lesson_endtime'] = date("Y-m-d H:i:s", strtotime($row['ordles_lesson_endtime'] . " +" . $orderIds[$row['ordles_order_id']] . " days"));
            }

            $orderLessons[$row['ordles_order_id']][] = $row;
        }
        return $orderLessons;
    }

    /**
     * Format And Validate
     * 
     * @param array $lessons
     * @param int $userId
     * @return array
     */
    private function formatAndValidate(array $lessons, int $userId): array
    {
        $subscription = ['scheduledCount' => 0, 'unScheduledCount' => 0, 'lessons' => []];
        foreach ($lessons as $key => $value) {
            if (empty($value['ordles_lesson_starttime'])) {
                $lessons[$key]['ordles_status'] = Lesson::UNSCHEDULED;
                $subscription['unScheduledCount'] += 1;
                continue;
            }
            $avail = new Availability($value['ordles_teacher_id']);
            if (!$avail->isAvailable($value['ordles_lesson_starttime'], $value['ordles_lesson_endtime'])) {
                $lessons[$key]['ordles_status'] = Lesson::UNSCHEDULED;
                $lessons[$key]['ordles_lesson_starttime'] = null;
                $lessons[$key]['ordles_lesson_endtime'] = null;
                $subscription['unScheduledCount'] += 1;
                continue;
            }
            if (!$avail->isUserAvailable($value['ordles_lesson_starttime'], $value['ordles_lesson_endtime'])) {
                $lessons[$key]['ordles_status'] = Lesson::UNSCHEDULED;
                $lessons[$key]['ordles_lesson_starttime'] = null;
                $lessons[$key]['ordles_lesson_endtime'] = null;
                $subscription['unScheduledCount'] += 1;
                continue;
            }
            $avail = new Availability($userId);
            if (!$avail->isUserAvailable($value['ordles_lesson_starttime'], $value['ordles_lesson_endtime'])) {
                $lessons[$key]['ordles_status'] = Lesson::UNSCHEDULED;
                $lessons[$key]['ordles_lesson_starttime'] = null;
                $lessons[$key]['ordles_lesson_endtime'] = null;
                $subscription['unScheduledCount'] += 1;
                continue;
            }
            $lessons[$key]['ordles_status'] = Lesson::SCHEDULED;
            $subscription['scheduledCount'] += 1;
        }
        $subscription['lessons'] = $lessons;
        return $subscription;
    }

    public function cancelNotBookedClasses(): string
    {
        if (!GroupClass::isEnabled()) {
            return 'Class module not available';
        }
        $db = FatApp::getDb();
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->addCondition('grpcls.grpcls_status', '=', GroupClass::SCHEDULED);
        $srch->addCondition('grpcls.grpcls_booked_seats', '=', 0);
        $srch->addCondition('grpcls.grpcls_start_datetime', '<', date('Y-m-d H:i:s'));
        $srch->addCondition('grpcls.grpcls_parent', '=', 0);
        $srch->addFld('grpcls_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $classes = $db->fetchAll($srch->getResultSet(), 'grpcls_id');
        $groupClsIds = array_keys($classes);
        if (empty($groupClsIds)) {
            return 'cancel not booked classes successfully';
        }

        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'gclang.gclang_grpcls_id = '
            . 'grpcls.grpcls_id and gclang.gclang_lang_id = teacher.user_lang_id', 'gclang');
        $srch->addMultipleFields([
            'user_first_name',
            'user_last_name',
            'user_lang_id',
            'grpcls_id',
            'user_email',
            'teacher.user_id',
            'IFNULL(gclang.grpcls_title,grpcls.grpcls_title) as grpcls_title',
            'grpcls_parent'
        ]);
        $srch->addDirectCondition('grpcls.grpcls_parent IN (' . implode(',', $groupClsIds) . ') OR grpcls.grpcls_id IN (' . implode(',', $groupClsIds) . ')');
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();

        while ($row = $db->fetch($resultSet)) {
            $db = FatApp::getDb();
            $db->startTransaction();
            $record = new TableRecord(GroupClass::DB_TBL);
            $record->setFldValue('grpcls_status', GroupClass::CANCELLED);
            if (!$record->update(['smt' => 'grpcls_id = ? OR grpcls_parent = ?', 'vals' => [$row['grpcls_id'], $row['grpcls_id']]])) {
                return $record->getError();
            }

            $googleCalendar = new GoogleCalendarEvent($row['user_id'], $row['grpcls_id'], AppConstant::GCLASS);
            $googleCalendar->removeClassEvents();

            $threadId = Thread::getIdByGroupId($row['grpcls_id']);
            if ($threadId) {
                $thread = new Thread($threadId);
                if (!$thread->markDelete()) {
                    $db->rollbackTransaction();
                    return $thread->getError();
                }
            }

            if ($row['grpcls_parent'] == 0) {
                $vars = ['{teacher_name}' => $row['user_first_name'] . ' ' . $row['user_last_name'], '{title}' => $row['grpcls_title'],];
                $mail = new FatMailer($row['user_lang_id'], 'no_booking_class_or_package_cancelled');
                $mail->setVariables($vars);
                $mail->sendMail([$row['user_email']]);
            }
            $db->commitTransaction();
        }
        return 'cancel not booked classes successfully';
    }

    public function restoreDb()
    {
        $restore = new Restore();
        if (!$restore->restoreDb()) {
            return $restore->getError();
        }
        return 'Demo Database Restored';
    }

    /**
     * Completed Course Settlement
     * 
     * @return string
     */
    public function completedCourseSettlement(): string
    {
        if (!Course::isEnabled()) {
            return 'Course module not available';
        }
        $course = new Course();
        if (!$course->completedCourseSettlement()) {
            return $course->getError();
        }
        return 'Courses settlement successful';
    }

    public function syncCurrencyRates()
    {
        $currency = new Currency();
        if (!$currency->syncRates()) {
            return $currency->getError();
        }
        return 'currency rates sync successfully';
    }

    public function revokeLicenses()
    {
        $meeting = new Meeting(0, 0);
        if ($meeting->initMeeting(0)) {
            return $meeting->getError();
        }
        if ($meeting->removeLicenses()) {
            return $meeting->getError();
        }
        return 'Revoke Meeting Licenses Success';
    }

    public function removeArchivedEmails()
    {
        $stmt = ['smt' => 'earch_senton < ?', 'vals' => [date('Y-m-d', strtotime('-45 day'))]];
        FatApp::getDb()->deleteRecords(FatMailer::DB_TBL_ARCHIVE, $stmt);
        return 'Email log deleted Successfully!';
    }

    public function autoCompleteLessonSession(int $offline = 0)
    {
        if ($offline == 1) {
            $hours = FatApp::getConfig('CONF_TEACHER_END_SESSION_DURATION', FatUtility::VAR_INT, 0);
        } else {
            $hours = FatApp::getConfig('CONF_AUTOCOMPLETE_LESSON_SESSION', FatUtility::VAR_INT, 0);
        }
        if (empty($hours)) {
            return 'Autocomplete lesson session not configured';
        }

        $startTime = date("Y-m-d H:i:s");
        $convertedTime = date('Y-m-d H:i:s', strtotime('-' . $hours . ' hour', strtotime($startTime)));
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->addMultipleFields([
            'orders.order_user_id',
            'ordles_teacher_id',
            'ordles_id',
            'ordles_status',
            'ordles_lesson_starttime',
            'ordles_offline',
            'ordles_metool_id',
        ]);
        $srch->addCondition('ordles.ordles_status', '=', Lesson::SCHEDULED);
        $srch->addCondition('ordles.ordles_lesson_endtime', '<', $convertedTime);
        if ($offline == 1) {
            $srch->addDirectCondition("ordles_offline = '" . AppConstant::YES . "'");
        } else {
            $srch->addDirectCondition("ordles_offline = '" . AppConstant::NO . "' AND ordles_teacher_starttime IS NOT NULL");
        }
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $db = FatApp::getDb();
        while ($row = $db->fetch($resultSet)) {
            $lesson = new Lesson($row['ordles_id'], $row['order_user_id'], User::SYSTEMS);
            if ($row['ordles_offline'] == AppConstant::NO) {
                /* Initialize Meeting Tool */
                $meetingObj = new Meeting($row['ordles_teacher_id'], User::TEACHER);
                if (!$meetingObj->initMeeting($row['ordles_metool_id'])) {
                    FatUtility::dieJsonError($meetingObj->getError());
                }
    
                /* End on Meeting Tool */
                if (!$meetingObj->endMeeting($row['ordles_id'], AppConstant::LESSON)) {
                    FatUtility::dieJsonError($meetingObj->getError());
                }
            }
            if (!$lesson->complete($row)) {
                return $lesson->getError();
            }
        }
        return 'Lessons Marked Completed Successfully!';
    }

    public function autoCompleteClassSession(int $offline = 0)
    {
        if (!GroupClass::isEnabled()) {
            return 'Group class/package module not available';
        }
        if ($offline == 1) {
            $hours = FatApp::getConfig('CONF_TEACHER_END_SESSION_DURATION', FatUtility::VAR_INT, 0);
        } else {
            $hours = FatApp::getConfig('CONF_AUTOCOMPLETE_CLASSES_SESSION', FatUtility::VAR_INT, 0);
        }
        if (empty($hours)) {
            return 'Autocomplete class session not configured';
        }
        $startTime = date("Y-m-d H:i:s");
        $convertedTime = date('Y-m-d H:i:s', strtotime('-' . $hours . ' hour', strtotime($startTime)));
        $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->addCondition('grpcls.grpcls_end_datetime', '<', $convertedTime);

        if ($offline == 1) {
            $srch->addDirectCondition("grpcls_offline = '" . AppConstant::YES . "'");
        } else {
            $srch->addDirectCondition("grpcls.grpcls_offline = '" . AppConstant::NO . "' AND grpcls.grpcls_teacher_starttime IS NOT NULL AND ordcls.ordcls_endtime IS NULL");
        }

        $srch->addCondition('ordcls.ordcls_status', '=', OrderClass::SCHEDULED);
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields([
            'grpcls_parent',
            'ordcls_status',
            'ordcls_order_id',
            'grpcls_id',
            'ordcls_id',
            'orders.order_user_id',
            'grpcls_offline',
            'grpcls_start_datetime',
            'grpcls_teacher_id',
            'grpcls_metool_id',
        ]);
        $resultSet = $srch->getResultSet();
        $db = FatApp::getDb();
        while ($row = $db->fetch($resultSet)) {
            $class = ['grpcls_parent' => $row['grpcls_parent'], 'order_id' => $row['ordcls_order_id'], 'grpcls_offline' => $row['grpcls_offline'], 'grpcls_start_datetime' => $row['grpcls_start_datetime']];
            $ordClass = new GroupClass($row['grpcls_id'], $row['order_user_id'], User::SYSTEMS);
            if ($row['grpcls_offline'] == AppConstant::NO) {
                /* Initialize Meeting Tool */
                $meetingObj = new Meeting($row['grpcls_teacher_id'], User::TEACHER);
                if (!$meetingObj->initMeeting($row['grpcls_metool_id'])) {
                    FatUtility::dieJsonError($meetingObj->getError());
                }
    
                /* End on Meeting Tool */
                if (!$meetingObj->endMeeting($row['grpcls_id'], AppConstant::GCLASS)) {
                    FatUtility::dieJsonError($meetingObj->getError());
                }
            }
            if (!$ordClass->complete($class)) {
                return $ordClass->getError();
            }
        }
        return 'Classes Marked Completed Successfully!';
    }

    /**
     * Synchronize Google Calendar Events
     * 
     * @return string
     */
    public function syncGoogleCalEvents()
    {
        $srch = new SearchBase(User::DB_TBL, 'users');
        $srch->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'users.user_id = us.user_id', 'us');
        $srch->addMultipleFields([
            'user_google_event_watch_id',
            'user_google_event_watch_expiration',
            'user_google_event_sync_token',
            'user_google_token',
            'us.user_id',
            'user_google_event_sync_date',
            'datediff(`user_google_event_sync_date`, "' . date('Y-m-d H:i:s') . '") as dayCount'
        ]);
        $srch->addCondition('user_google_token', '!=', '');

        /* sync after a particular days */
        $days = FatApp::getConfig('CONF_CALENDAR_SYNC_EXECUTION_DURATION', FatUtility::VAR_INT, 7);
        $cond = $srch->addCondition('datediff("' . date('Y-m-d H:i:s') . '", `user_google_event_sync_date`)', '>=', $days);
        $cond->attachCondition('user_google_event_sync_date', 'IS', 'mysql_func_NULL', 'OR', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(10);
        $resultSet = $srch->getResultSet();
        while ($user = FatApp::getDb()->fetch($resultSet)) {
            $googleCalendar = new GoogleCalendarEvent($user['user_id'], 0, 0);
            if (!$googleCalendar->addEventsList($user['user_google_token'], $user['user_google_event_sync_date'])) {
                continue;
            }
        }
        return 'Google Calendar Synchronization Success';
    }

    /**
     * Watch For Calendar Changes
     * 
     * @return string
     */
    public function watchForCalendarChanges()
    {
        $srch = new SearchBase(User::DB_TBL, 'users');
        $srch->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'users.user_id = us.user_id', 'us');
        $srch->addMultipleFields([
            'user_google_event_watch_id',
            'user_google_event_watch_resource_id',
            'user_google_event_watch_expiration',
            'user_google_token',
            'us.user_id',
        ]);
        $cond1 = $srch->addCondition('user_google_token', '!=', '');
        $cond1->attachCondition('user_google_event_sync_token', 'IS NOT', 'mysql_func_NULL', 'AND', true);
        $cond1->attachCondition('user_google_event_watch_id', '!=', '', 'AND');
        $cond1->attachCondition('user_google_event_watch_expiration', '<=', date("Y-m-d H:i:s", strtotime('+1 hours')), 'AND');
        $cond2 = $srch->addCondition('user_google_token', 'IS NOT', 'mysql_func_NULL', 'OR', true);
        $cond2->attachCondition('user_google_event_sync_token', 'IS NOT', 'mysql_func_NULL', 'AND', true);
        $cond2->attachCondition('user_google_event_watch_id', 'IS', 'mysql_func_NULL', 'AND', true);
        $cond2->attachCondition('user_google_event_watch_expiration', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(10);
        $resultSet = $srch->getResultSet();
        $users = FatApp::getDb()->fetchAll($resultSet);
        foreach ($users as $user) {
            $googleCalendar = new GoogleCalendar($user['user_id']);
            if (!$googleCalendar->addGoogleWatch($user['user_google_token'])) {
                continue;
            }
            if (!empty($user['user_google_event_watch_id'])) {
                if (!$googleCalendar->removeWatch($user['user_google_token'], $user['user_google_event_watch_id'], $user['user_google_event_watch_resource_id'])) {
                    continue;
                }
            }
        }
        return 'Google Calendar Watch Success';
    }

    /**
     * Recurring Subscription
     * 
     * @return string
     *
     */
    public function renewSubscriptionPlan(): string
    {
        if (!SubscriptionPlan::isEnabled()) {
            return 'Subscription Plan module not available';
        }
        $walletPay = PaymentMethod::getByCode(WalletPay::KEY);
        if (empty($walletPay)) {
            return "WALLET PAY IS NOT ACTIVE";
        }
        $db = FatApp::getDb();
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $srch->addMultipleFields([
            'order_id',
            'learner.user_id as learner_id',
            'learner.user_timezone as learner_timezone',
            'learner.user_lang_id as learner_lang_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'learner.user_email as learner_email',
            'ordsplan.ordsplan_duration',
            'ordsplan.ordsplan_validity',
            'ordsplan.ordsplan_amount',
            'ordsplan.ordsplan_discount',
            'ordsplan.ordsplan_reward_discount',
            'ordsplan.ordsplan_refund',
            'ordsplan.ordsplan_lessons',
            'ordsplan.ordsplan_plan_id',
            'subplan.subplan_active',
            'subplan.subplan_title',
            'ordsplan.ordsplan_id',
            'setting.user_autorenew_subscription', 'ordsplan_start_date', 'ordsplan_end_date'
        ]);
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordsplan.ordsplan_order_id', 'orders');
        $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'subplan.subplan_id = ordsplan.ordsplan_plan_id', 'subplan');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = ordsplan.ordsplan_user_id', 'learner');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'learner.user_id = setting.user_id', 'setting');
        $srch->addDirectCondition('learner.user_deleted IS NULL');
        $cond = $srch->addCondition('ordsplan.ordsplan_status', '=', OrderSubscriptionPlan::ACTIVE);
        $cond->attachCondition('ordsplan_status', '=', OrderSubscriptionPlan::EXPIRED, 'OR');
        $srch->addCondition('ordsplan.ordsplan_end_date', '<', date('Y-m-d H:i:s'), 'AND');
        $srch->addCondition('mysql_func_DATE_ADD(ordsplan.ordsplan_end_date, INTERVAL 1 HOUR)', '>', date('Y-m-d H:i:s'), 'AND', true);
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('order_status', '=', Order::STATUS_COMPLETED);
        $srch->setPageSize(10);
        $srch->doNotCalculateRecords();
        $rows = $db->fetchAll($srch->getResultSet(), 'ordsplan_id');
        foreach ($rows as $value) {
            $db->startTransaction();
            if ($value['subplan_active'] == AppConstant::INACTIVE || $value['user_autorenew_subscription'] == AppConstant::INACTIVE) {
                $subscription = new OrderSubscriptionPlan($value['ordsplan_id']);
                if (!$subscription->markCompleted()) {
                    $db->rollbackTransaction();
                    return false;
                }
                $db->commitTransaction();
                continue;
            }

            $userWalletBalance = User::getWalletBalance($value['learner_id']);
            $totalAmount = $value['ordsplan_amount'];
            if ($totalAmount > $userWalletBalance) {
                $subscription = new OrderSubscriptionPlan($value['ordsplan_id']);
                if (!$subscription->markCompleted()) {
                    $db->rollbackTransaction();
                    return false;
                }
                $subPlan = current($subscription->getSubOrdByIds([$value['ordsplan_id']], $value['learner_lang_id']));
                $notifiVar = ['{plan_name}' => $subPlan['plan_name']];
                $notifi = new Notification($value['learner_id'], Notification::TYPE_SUB_PLAN_RENEWAL_FAILED);
                $notifi->sendNotification($notifiVar);
                $vars = [
                    '{learner_name}' => $value['learner_first_name'] . ' ' . $value['learner_last_name'],
                    '{current_balance}' => MyUtility::formatMoney($userWalletBalance),
                    '{subscription_amount}' => MyUtility::formatMoney($totalAmount),
                    '{plan_name}' => $subPlan['plan_name']
                ];
                $mail = new FatMailer($value['learner_lang_id'], 'wallet_balance_low_for_subscription_plan');
                $mail->setVariables($vars);
                $mail->sendMail([$value['learner_email']]);
                $db->commitTransaction();
                continue;
            }
            $days = $value['ordsplan_validity'] * 7;
            $subscription = [
                'order_item_count' => 1,
                'order_net_amount' => $value['ordsplan_amount'],
                'ordsplan_user_id' => $value['learner_id'],
                'ordsplan_duration' => $value['ordsplan_duration'],
                'ordsplan_validity' => $value['ordsplan_validity'],
                'ordsplan_amount' => $value['ordsplan_amount'],
                'ordsplan_plan_id' => $value['ordsplan_plan_id'],
                'ordsplan_lessons' => $value['ordsplan_lessons'],
                'ordsplan_discount' => $value['ordsplan_discount'],
                'ordsplan_refund' => $value['ordsplan_refund'],
                'ordsplan_reward_discount' => $value['ordsplan_reward_discount'],
                'ordsplan_start_date' => $value['ordsplan_end_date'],
                'ordsplan_end_date' => date('Y-m-d H:i:s', strtotime($value['ordsplan_end_date'] . " + $days days"))
            ];
            $orderSubPlan = new OrderSubscriptionPlan($value['ordsplan_id']);
            if (!$orderSubPlan->renew($subscription)) {
                $db->rollbackTransaction();
                return $orderSubPlan->getError();
            }
            $db->commitTransaction();
        }
        return 'recurring subscription successfully';
    }
    /**
     * Cancel incomplete quizzes
     *
     * @return void
     */
    public function cancelIncompleteQuizzes()
    {
        $quiz = new QuizAttempt();
        if (!$quiz->cancelIncompleteQuizzes()) {
            return $quiz->getError();
        }
        return 'Incomplete Quizzess Settlement Successful';
    }

    /**
     * Mark Course Modifications Complete
     * 
     * @return string
     */
    public function markCourseUpdatesComplete(): string
    {
        if (!Course::isEnabled()) {
            return 'Course module not available';
        }
        $srch = new SearchBase(Course::DB_TBL_EDIT_REQUEST);
        $srch->addCondition('coedre_updates_done', '=', Course::EDIT_UPDATES_INPROGRESS);
        $srch->addCondition('coedre_status', '=', Course::EDIT_REQUEST_APPROVED);
        $srch->addDirectCondition("DATE_ADD(coedre_updated, INTERVAL coedre_duration DAY) < '" . date('Y-m-d H:i:s') . "'");
        $srch->addFld('coedre_course_id');
        $srch->addFld('coedre_id');
        $srch->doNotCalculateRecords();
        $courses = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($courses)) {
            return true;
        }
        foreach ($courses as $crs) {
            $course = new Course($crs['coedre_course_id']);
            if (!$course->completeEditRequest($crs['coedre_id'])) {
                return $course->getError();
            }
        }
        return 'Courses marked completed successfully';
    }

}
