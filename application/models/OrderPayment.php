<?php

/**
 * This class is used to handle Order Payment
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderPayment extends FatModel
{

    private $order;
    private $orderId;

    /**
     * Initialize Order Payment
     * 
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        parent::__construct();
        $this->orderId = $orderId;
        $this->order = Order::getAttributesById($orderId);
    }

    /**
     * getById
     * 
     * @param int $payId
     * @return null|array
     */
    public static function getById(int $payId)
    {
        $src = new SearchBase(Order::DB_TBL_PAYMENT, 'ordpay');
        $src->addCondition('ordpay_id', '=', $payId);
        $src->doNotCalculateRecords();
        $src->setPageSize(1);
        return FatApp::getDb()->fetch($src->getResultSet());
    }

    /**
     * Get Methods
     * 
     * @param int $key
     * @return string|array
     */
    public static function getMethods(int $key = null)
    {
        $srch = PaymentMethod::getSearchObject(false);
        $srch->addMultipleFields(['pmethod_id', 'pmethod_code']);
        $arr = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        foreach ($arr as $ke => $value) {
            $arr[$ke] = Label::getLabel('LBL_' . $value);
        }
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Payments By Order Id
     * 
     * @param int $orderId
     * @return array
     */
    public static function getPaymentsByOrderId(int $orderId): array
    {
        $src = new SearchBase(Order::DB_TBL_PAYMENT, 'ordpay');
        $src->addCondition('ordpay_order_id', '=', $orderId);
        $src->doNotCalculateRecords();
        $src->doNotLimitRecords();
        $payments = FatApp::getDb()->fetchAll($src->getResultSet());
        foreach ($payments as $key => $payment) {
            $payments[$key]['ordpay_datetime'] = MyDate::convert($payment['ordpay_datetime']);
        }
        return $payments;
    }

    /**
     * Payment Settlements
     * 
     * @param string $txnId
     * @param float $amount
     * @param array $res
     * @return bool
     */
    public function paymentSettlements(string $txnId, float $amount, array $res): bool
    {
        if (empty($this->order)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if (Order::ISPAID == $this->order['order_payment_status']) {
            $this->error = Label::getLabel('LBL_ORDER_ALREADY_PAID');
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$this->addOrderPayment($txnId, $amount, $res)) {
            $db->rollbackTransaction();
            return false;
        }
        /* Send Email for Order Payment to Customer and Admin */
        $this->sendEmailNotification();

        if (!$this->sendSystemNotification()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->updatePaymentStatus()) {
            $db->rollbackTransaction();
            return false;
        }
        if (isset($res['ordpay_pmethod_id']) && $res['ordpay_pmethod_id'] != $this->order['order_pmethod_id']) {
            if (!$this->updatePaymentMethod($res['ordpay_pmethod_id'])) {
                $db->rollbackTransaction();
                return false;
            }
        }
        if (!$this->relatedOrderPayments()) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Related Order Payments
     * 
     * @return bool
     */
    private function relatedOrderPayments(): bool
    {
        $userId = $this->order['order_user_id'];
        $orderId = $this->order['order_related_order_id'];
        if (empty($orderId)) {
            return true;
        }
        /* Get Releated Order to Pay */
        $orderObj = new Order($orderId, $userId);
        if (!$order = $orderObj->getOrderToPay()) {
            $this->error = $orderObj->getError();
            return true;
        }
        /* Initialize Payment Method */
        $pay = new WalletPay($order);
        if (!$pay->initPayemtMethod()) {
            $this->error = $pay->getError();
            return false;
        }
        /* Check user wallet balance */
        $amount = FatUtility::float($order['order_net_amount']);
        if ($amount > User::getWalletBalance($order['order_user_id'])) {
            $this->error = Label::getLabel('MSG_INSUFFICIENT_WALLET_BALANCE');
            return false;
        }
        /* Debit user wallet balance */
        $vars = [Transaction::getTypes($order['order_type']), Order::formatOrderId($orderId)];
        $comment = str_replace(['{ordertype}', '{orderid}'], $vars, Label::getLabel('LBL_{ordertype}:_ID_{orderid}'));
        $txn = new Transaction($order['order_user_id'], $order['order_type']);
        if (!$txn->debit($amount, $comment)) {
            $this->error = Label::getLabel('MSG_SOMETHING_WENT_WRONG_TRY_AGAIN');
            return false;
        }
        $res = Transaction::getAttributesById($txn->getMainTableRecordId());
        $res['usrtxn_datetime'] = MyDate::showDate($res['usrtxn_datetime'], true) . ' UTC';
        /* Order Payment & Settlements */
        $payment = new OrderPayment($orderId);
        if (!$payment->paymentSettlements($res['usrtxn_id'], $amount, $res)) {
            $this->error = $payment->getError();
            return false;
        }
        return true;
    }

    /**
     * Add Order Payment
     * 
     * @param string $txnId
     * @param float $amount
     * @param array $res
     * @return bool
     */
    private function addOrderPayment(string $txnId, float $amount, array $res): bool
    {
        $pmethodId = $res['ordpay_pmethod_id'] ?? $this->order['order_pmethod_id'];
        $paymentData = [
            'ordpay_txn_id' => $txnId,
            'ordpay_amount' => $amount,
            'ordpay_pmethod_id' => $pmethodId,
            'ordpay_order_id' => $this->orderId,
            'ordpay_response' => json_encode($res),
            'ordpay_datetime' => date('Y-m-d H:i:s')
        ];
        $payment = new TableRecord(Order::DB_TBL_PAYMENT);
        $payment->assignValues($paymentData);
        if (!$payment->addNew(['HIGH_PRIORITY'])) {
            $this->error = $payment->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Payment Status
     * 
     * Mark order payment status paid
     * Update Suborder's required tables
     * 
     * @return bool
     */
    private function updatePaymentStatus(): bool
    {
        $orderType = FatUtility::int($this->order['order_type']);
        $order = new TableRecord(Order::DB_TBL);
        $order->setFldValue('order_payment_status', Order::ISPAID);
        $order->setFldValue('order_status', Order::STATUS_COMPLETED);
        if (!$order->update(['smt' => 'order_id = ?', 'vals' => [$this->orderId]])) {
            $this->error = $order->getError();
            return false;
        }
        $this->order['order_payment_status'] = Order::ISPAID;
        $this->order['order_status'] = Order::STATUS_COMPLETED;
        switch ($orderType) {
            case Order::TYPE_LESSON:
                return $this->updateLessonData();
            case Order::TYPE_SUBSCR:
                return $this->updateSubscriptionData();
            case Order::TYPE_GCLASS:
                return $this->updateGclassData();
            case Order::TYPE_PACKGE:
                return $this->updatePackageData();
            case Order::TYPE_COURSE:
                return $this->updateCourseData();
            case Order::TYPE_WALLET:
                return $this->updateWalletData();
            case Order::TYPE_GFTCRD:
                return $this->updateGiftcardData();
            case Order::TYPE_SUBPLAN:
                return $this->updateSubscriptionPlanData();
        }
        $this->error = Label::getLabel('LBL_INVALID_REQUEST');
        return false;
    }

    /**
     * Send Email Notification
     * 
     * @return bool
     */
    private function sendEmailNotification(): bool
    {
        $user = User::getAttributesById($this->order['order_user_id']);
        $variables = [
            '{orderid}' => Order::formatOrderId($this->order['order_id']),
            '{order_link}' => MyUtility::makeFullUrl('Orders', 'view', [$this->order['order_id']], CONF_WEBROOT_BACKEND),
            '{payment}' => MyUtility::formatMoney($this->order['order_net_amount']),
            '{customer}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
        ];
        $mail = new FatMailer($user['user_lang_id'], 'order_paid_to_customer');
        $mail->setVariables($variables);
        if (!$mail->sendMail([$user['user_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        $mail = new FatMailer(FatApp::getConfig("CONF_DEFAULT_LANG"), 'order_paid_to_admin');
        $mail->setVariables($variables);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Send System Notification
     * 
     * @return bool
     */
    private function sendSystemNotification(): bool
    {
        $userId = FatUtility::int($this->order['order_user_id']);
        $noti = new Notification($userId, Notification::TYPE_ORDER_PAID);
        $variables = [
            '{orderid}' => Order::formatOrderId($this->order['order_id']),
            '{payment}' => MyUtility::formatMoney($this->order['order_net_amount']),
        ];
        if (!$noti->sendNotification($variables, User::LEARNER)) {
            $this->error = $noti->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Lesson Order:
     * 
     * 1. Update Lesson counts in (tbl_offer_prices)
     * 2. Update Lesson counts in (tbl_teacher_stats)
     * 3. Update Student counts in (tbl_teacher_stats)
     * 
     * @return bool
     */
    private function updateLessonData(): bool
    {
        $subOrderObj = new SearchBase(Order::DB_TBL_LESSON);
        $subOrderObj->addMultipleFields([
            'ordles_teacher_id',
            'ordles_tlang_id',
            'ordles_id',
            'ordles_type',
            'IFNULL(lsetting.user_google_token,"") as learner_google_token',
            'IFNULL(tsetting.user_google_token,"") as teacher_google_token',
            'ordles_status',
            'learner.user_lang_id as learner_lang_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'learner.user_email as learner_email',
            'teacher.user_lang_id as teacher_lang_id',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'teacher.user_email as teacher_email',
            'ordles_duration',
            'ordles_lesson_starttime',
            'ordles_lesson_endtime',
            'teacher.user_timezone as teacher_timezone',
            'ordles_ordsplan_id'
        ]);
        $subOrderObj->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = ' . $this->order['order_user_id'], 'learner');
        $subOrderObj->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordles_teacher_id', 'teacher');
        $subOrderObj->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'lsetting.user_id = learner.user_id', 'lsetting');
        $subOrderObj->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'tsetting.user_id = teacher.user_id', 'tsetting');
        $subOrderObj->addCondition('ordles_order_id', '=', $this->orderId);
        $subOrderObj->doNotCalculateRecords();
        $subOrders = FatApp::getDb()->fetchAll($subOrderObj->getResultSet());
        if (empty($subOrders)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $subOrder = current($subOrders);
        $teacherId = FatUtility::int($subOrder['ordles_teacher_id']);
        $lessons = FatUtility::int($this->order['order_item_count']);
        $offerPrice = new OfferPrice($this->order['order_user_id']);
        if (!$offerPrice->increaseLesson($teacherId, $lessons)) {
            $this->error = $offerPrice->getError();
            return false;
        }
        if (!$offerPrice->updateTeacherStats($teacherId)) {
            $this->error = $offerPrice->getError();
            return false;
        }
        if (!empty($subOrder['ordles_ordsplan_id'])) {
            $subPlan = new OrderSubscriptionPlan($subOrder['ordles_ordsplan_id']);
            $subPlan->updateLessonCount(count($subOrders));
        }
        $teacherTlangName = Label::getLabel('LBL_FREE_TRIAL', $subOrder['teacher_lang_id']);
        $learnerTlangName = Label::getLabel('LBL_FREE_TRIAL', $subOrder['learner_lang_id']);
        if ($subOrder['ordles_type'] != Lesson::TYPE_FTRAIL) {
            $tlanguageNames = TeachLanguage::getNamesByLangIds([$subOrder['learner_lang_id'], $subOrder['teacher_lang_id']], [$subOrder['ordles_tlang_id']]);
            $teacherTlangName = $tlanguageNames[$subOrder['ordles_tlang_id']][$subOrder['teacher_lang_id']];
            $learnerTlangName = $tlanguageNames[$subOrder['ordles_tlang_id']][$subOrder['learner_lang_id']];
        }
        $vars = [
            '{learner_name}' => $subOrder['learner_first_name'] . ' ' . $subOrder['learner_last_name'],
            '{teacher_name}' => $subOrder['teacher_first_name'] . ' ' . $subOrder['teacher_last_name'],
            '{tlang_name}' => $learnerTlangName,
            '{lesson_url}' => MyUtility::makeFullUrl('Lessons', 'index', [], CONF_WEBROOT_DASHBOARD) . '?order_id=' . $this->orderId,
        ];
        $mail = new FatMailer($subOrder['learner_lang_id'], 'learner_lesson_book_email');
        $mail->setVariables($vars);
        $mail->sendMail([$subOrder['learner_email']]);


        $mail = new FatMailer($subOrder['teacher_lang_id'], 'teacher_lesson_book_email');
        $vars['{tlang_name}'] = $teacherTlangName;
        $mail->setVariables($vars);
        $mail->sendMail([$subOrder['teacher_email']]);

        $lesson = new Lesson($subOrder['ordles_id'], User::LEARNER, $subOrder['learner_lang_id']);
        foreach ($subOrders as $key => $subOrder) {
            $subOrders[$key]['teacher_tlang_name'] = $teacherTlangName;
            $subOrders[$key]['tlang_name'] = $teacherTlangName;
            $subOrders[$key]['learner_tlang_name'] = $learnerTlangName;
        }
        $lesson->sendScheduledMail($subOrders);

        $this->addLessonEvent($subOrders);
        $this->checkMeetingLicense($subOrders);
        return true;
    }

    /**
     * Update Lesson Order:
     * 
     * 1. Update Lesson counts in (tbl_offer_prices)
     * 2. Update Lesson counts in (tbl_teacher_stats)
     * 3. Update Student counts in (tbl_teacher_stats)
     * 
     * @return bool
     */
    private function updateSubscriptionData(): bool
    {
        $subOrderObj = new SearchBase(Subscription::DB_TBL);
        $subOrderObj->joinTable(Lesson::DB_TBL, 'INNER JOIN', 'ordsub_order_id = ordles_order_id', 'ordles');
        $subOrderObj->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = ' . $this->order['order_user_id'], 'learner');
        $subOrderObj->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordles_teacher_id', 'teacher');
        $subOrderObj->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'lsetting.user_id = learner.user_id', 'lsetting');
        $subOrderObj->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'tsetting.user_id = teacher.user_id', 'tsetting');
        $subOrderObj->addCondition('ordsub_order_id', '=', $this->orderId);
        $subOrderObj->addMultipleFields([
            'ordles_teacher_id',
            'ordles_tlang_id',
            'ordles_id',
            'ordles_type',
            'IFNULL(lsetting.user_google_token,"") as learner_google_token',
            'IFNULL(tsetting.user_google_token,"") as teacher_google_token',
            'ordles_status',
            'learner.user_lang_id as learner_lang_id',
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'learner.user_email as learner_email',
            'teacher.user_lang_id as teacher_lang_id',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'teacher.user_email as teacher_email',
            'teacher.user_timezone as teacher_timezone',
            'ordles_duration',
            'ordles_lesson_starttime',
            'ordles_lesson_endtime'
        ]);
        $subOrderObj->doNotCalculateRecords();
        $subOrders = FatApp::getDb()->fetchAll($subOrderObj->getResultSet());
        if (empty($subOrders)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $subOrder = current($subOrders);
        $teacherTlangName = Label::getLabel('LBL_FREE_TRIAL', $subOrder['teacher_lang_id']);
        $learnerTlangName = Label::getLabel('LBL_FREE_TRIAL', $subOrder['learner_lang_id']);
        if ($subOrder['ordles_type'] != Lesson::TYPE_FTRAIL) {
            $tlanguageNames = TeachLanguage::getNamesByLangIds([$subOrder['learner_lang_id'], $subOrder['teacher_lang_id']], [$subOrder['ordles_tlang_id']]);
            $teacherTlangName = $tlanguageNames[$subOrder['ordles_tlang_id']][$subOrder['teacher_lang_id']];
            $learnerTlangName = $tlanguageNames[$subOrder['ordles_tlang_id']][$subOrder['learner_lang_id']];
        }


        $lesson = new Lesson($subOrder['ordles_id'], User::LEARNER, $subOrder['learner_lang_id']);
        foreach ($subOrders as $key => $subOrder) {
            $subOrders[$key]['tlang_name'] = $teacherTlangName;
            $subOrders[$key]['teacher_tlang_name'] = $teacherTlangName;
            $subOrders[$key]['learner_tlang_name'] = $learnerTlangName;
        }
        $lesson->sendScheduledMail($subOrders);
        $teacherId = FatUtility::int($subOrder['ordles_teacher_id']);
        $lessons = FatUtility::int($this->order['order_item_count']);
        $offerPrice = new OfferPrice($this->order['order_user_id']);

        if (!$offerPrice->increaseLesson($teacherId, $lessons)) {
            $this->error = $offerPrice->getError();
            return false;
        }
        $teacherStat = new TeacherStat($teacherId);
        if (!$teacherStat->setLessonAndClassCount()) {
            $this->error = $teacherStat->getError();
            return false;
        }
        $vars = [
            '{learner_name}' => $subOrder['learner_first_name'] . ' ' . $subOrder['learner_last_name'],
            '{teacher_name}' => $subOrder['teacher_first_name'] . ' ' . $subOrder['teacher_last_name'],
            '{tlang_name}' => $learnerTlangName,
            '{lesson_url}' => MyUtility::makeFullUrl('Lessons', 'index', [], CONF_WEBROOT_DASHBOARD) . '?order_id=' . $this->orderId,
        ];

        $mail = new FatMailer($subOrder['learner_lang_id'], 'learner_lesson_book_email');
        $mail->setVariables($vars);
        $mail->sendMail([$subOrder['learner_email']]);

        $mail = new FatMailer($subOrder['teacher_lang_id'], 'teacher_lesson_book_email');
        $vars['{tlang_name}'] = $teacherTlangName;
        $mail->setVariables($vars);
        $mail->sendMail([$subOrder['teacher_email']]);

        $this->addLessonEvent($subOrders);
        $this->checkMeetingLicense($subOrders);
        return true;
    }

    /**
     * Update Group Class Order:
     * 
     * 1. Update Class counts in (tbl_offer_prices)
     * 2. Update Class counts in (tbl_teacher_stats)
     * 3. Update Student counts in (tbl_teacher_stats)
     * 4. Update Group class booked counts (tbl_group_classes)
     * 5. Add Event on google calendar (tbl_google_calendar_events)
     * 
     * @return bool
     */
    private function updateGclassData(): bool
    {
        $learner = User::getAttributesById($this->order['order_user_id'], ['user_lang_id', 'user_first_name', 'user_last_name', 'user_email']);
        $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_teacher_id = teacher.user_id', 'teacher');
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = grpclslang.gclang_grpcls_id and grpclslang.gclang_lang_id = ' . $learner['user_lang_id'], 'grpclslang');
        $srch->addCondition('ordcls_order_id', '=', $this->orderId);
        $srch->addMultipleFields([
            'grpcls_id',
            'ordcls_id',
            'grpcls_start_datetime',
            'teacher.user_email as teacher_email',
            'teacher.user_lang_id as teacher_lang_id',
            'grpcls_end_datetime',
            'grpcls_teacher_id',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'IFNULL(grpclslang.grpcls_description, grpcls.grpcls_description) as grpcls_description',
            'IFNULL(grpclslang.grpcls_title, grpcls.grpcls_title) as grpcls_title'
        ]);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $subOrder = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($subOrder)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $teacherId = FatUtility::int($subOrder['grpcls_teacher_id']);
        $classes = FatUtility::int($this->order['order_item_count']);
        $offerPrice = new OfferPrice($this->order['order_user_id']);
        if (!$offerPrice->increaseClass($teacherId, $classes)) {
            $this->error = $offerPrice->getError();
            return false;
        }
        $teacherStat = new TeacherStat($teacherId);
        if (!$teacherStat->setLessonAndClassCount()) {
            $this->error = $teacherStat->getError();
            return false;
        }
        $class = new GroupClass($subOrder['grpcls_id']);
        if (!$class->updateBookedSeatsCount()) {
            $this->error = $class->getError();
            return false;
        }
        $thread = new Thread(Thread::getIdByGroupId($subOrder['grpcls_id']));
        if (!$thread->addThreadUsers([$this->order['order_user_id']])) {
            $this->error = $thread->getError();
            return false;
        }
        $vars = [
            '{learner_name}' => $learner['user_first_name'] . ' ' . $learner['user_last_name'],
            '{teacher_name}' => $subOrder['teacher_first_name'] . ' ' . $subOrder['teacher_last_name'],
            '{class_name}' => $subOrder['grpcls_title']
        ];
        $mail = new FatMailer($subOrder['teacher_lang_id'], 'learner_class_book_email');
        $mail->setVariables($vars);
        $mail->sendMail([$subOrder['teacher_email']]);
        $mail = new FatMailer($learner['user_lang_id'], 'teacher_class_book_email');
        $mail->setVariables($vars);
        $mail->sendMail([$learner['user_email']]);
        $token = (new UserSetting($this->order['order_user_id']))->getGoogleToken();
        if (!empty($token)) {
            $subOrder['google_token'] = $token;
            $googleCalendar = new GoogleCalendarEvent($this->order['order_user_id'], $subOrder['grpcls_id'], AppConstant::GCLASS);
            $googleCalendar->addClassEvent($subOrder, User::LEARNER);
        }

        /* Send Quiz Attached Notification & Email */
        $quiz = new QuizLinked(0, $this->order['order_user_id']);
        if (!$quiz->bindUserQuiz($subOrder['grpcls_id'], AppConstant::GCLASS)) {
            $this->error = $quiz->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Package Data
     * 
     * @return bool
     */
    private function updatePackageData(): bool
    {
        $learner = User::getAttributesById($this->order['order_user_id'], ['user_lang_id', 'user_first_name', 'user_last_name', 'user_email']);
        $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = grpclslang.gclang_grpcls_id and grpclslang.gclang_lang_id = ' . $learner['user_lang_id'], 'grpclslang');
        $srch->addCondition('ordcls_order_id', '=', $this->orderId);
        $srch->addMultipleFields([
            'grpcls_id',
            'ordcls_id',
            'grpcls_start_datetime',
            'grpcls_end_datetime',
            'grpcls_teacher_id',
            'IFNULL(grpclslang.grpcls_description, grpcls.grpcls_description) as grpcls_description',
            'IFNULL(grpclslang.grpcls_title, grpcls.grpcls_title) as grpcls_title'
        ]);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $subOrders = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($subOrders)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $subOrder = current($subOrders);
        $teacherId = FatUtility::int($subOrder['grpcls_teacher_id']);
        $teacher = User::getAttributesById($teacherId, ['user_lang_id', 'user_first_name', 'user_last_name', 'user_email']);
        $offerPrice = new OfferPrice($this->order['order_user_id']);
        if (!$offerPrice->increaseClass($teacherId, count($subOrders))) {
            $this->error = $offerPrice->getError();
            return false;
        }
        $teacherStat = new TeacherStat($teacherId);
        if (!$teacherStat->setLessonAndClassCount()) {
            $this->error = $teacherStat->getError();
            return false;
        }
        $package = OrderPackage::getByOrderId($this->orderId, $teacher['user_lang_id']);
        $class = new GroupClass($package['ordpkg_package_id']);
        if (!$class->updateBookedSeatsCount()) {
            $this->error = $class->getError();
            return false;
        }
        foreach ($subOrders as $subOrder) {
            $class = new GroupClass($subOrder['grpcls_id']);
            if (!$class->updateBookedSeatsCount()) {
                $this->error = $class->getError();
                return false;
            }
            $thread = new Thread(Thread::getIdByGroupId($subOrder['grpcls_id']));
            if (!$thread->addThreadUsers([$this->order['order_user_id']])) {
                $this->error = $thread->getError();
                return false;
            }
        }
        /* Send Email Notifications */
        $vars = [
            '{learner_name}' => $learner['user_first_name'] . ' ' . $learner['user_last_name'],
            '{teacher_name}' => $teacher['user_first_name'] . ' ' . $teacher['user_last_name'],
            '{class_name}' => $package['package_name']
        ];
        $mail = new FatMailer($teacher['user_lang_id'], 'teacher_package_purchased');
        $mail->setVariables($vars);
        $mail->sendMail([$teacher['user_email']]);
        $mail = new FatMailer($learner['user_lang_id'], 'learner_package_purchased');
        $mail->setVariables($vars);
        $mail->sendMail([$learner['user_email']]);
        $token = (new UserSetting($this->order['order_user_id']))->getGoogleToken();
        if (!empty($token)) {
            foreach ($subOrders as $subOrder) {
                $subOrder['google_token'] = $token;
                $googleCalendar = new GoogleCalendarEvent($this->order['order_user_id'], $subOrder['grpcls_id'], AppConstant::GCLASS);
                $googleCalendar->addClassEvent($subOrder, User::LEARNER);
            }
        }

        /* Send Quiz Attached Notification & Email */
        foreach($subOrders as $subOrder) {
            $quiz = new QuizLinked(0, $this->order['order_user_id']);
            if (!$quiz->bindUserQuiz($subOrder['grpcls_id'], AppConstant::GCLASS)) {
                $this->error = $quiz->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Update Course Order:
     * 
     * 1. Update Course students counts in (tbl_courses)
     * 
     * @return bool
     */
    private function updateCourseData(): bool
    {
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'ordcrs.ordcrs_course_id = course.course_id', 'course');
        $srch->joinTable(Course::DB_TBL_LANG, 'INNER JOIN', 'course.course_id = crsdetail.course_id', 'crsdetail');
        $srch->addCondition('ordcrs_order_id', '=', $this->order['order_id']);
        $srch->addMultipleFields([
            'ordcrs_id',
            'ordcrs_course_id',
            'course_user_id',
            'course_title',
            'course_price'
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('ordcrs_course_id');
        if (!$orderCourse = FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $courseId = $orderCourse['ordcrs_course_id'];
        $course = new Course($courseId);
        if (!$course->setStudentCount()) {
            $this->error = $course->getError();
            return false;
        }
        /* update count in teachers stats */
        $teacherStats = new TeacherStat($orderCourse['course_user_id']);
        if (!$teacherStats->setCoursesCount()) {
            $this->error = $teacherStats->getError();
            return false;
        }
        $learner = User::getAttributesById($this->order['order_user_id'], ['user_lang_id', 'user_first_name', 'user_last_name', 'user_email']);
        $teacher = User::getAttributesById($orderCourse['course_user_id'], ['user_first_name', 'user_last_name']);
        $vars = [
            '{learner_name}' => ucwords($learner['user_first_name'] . ' ' . $learner['user_last_name']),
            '{teacher_name}' => ucwords($teacher['user_first_name'] . ' ' . $teacher['user_last_name']),
            '{course_title}' => ucfirst($orderCourse['course_title']),
            '{course_price}' => MyUtility::formatMoney($orderCourse['course_price']),
            '{course_link}' => MyUtility::generateFullUrl('Tutorials', 'start', [$orderCourse['ordcrs_id']], CONF_WEBROOT_DASHBOARD)
        ];
        $mail = new FatMailer($learner['user_lang_id'], 'course_booking_email_to_learner');
        $mail->setVariables($vars);
        $mail->sendMail([$learner['user_email']]);
        $mail = new FatMailer(MyUtility::getSiteLangId(), 'course_booking_email_to_admin');
        $mail->setVariables($vars);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);

        /* Send Quiz Attached Notification & Email */
        $quiz = new QuizLinked(0, $this->order['order_user_id']);
        if (!$quiz->bindUserQuiz($orderCourse['ordcrs_course_id'], AppConstant::COURSE)) {
            $this->error = $quiz->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Wallet Order:
     * 
     * 1. Add Credit TXN entry in (tbl_user_transactions) 
     * 2. Update User wallet balance (tbl_user_settings)
     * 
     * @return bool
     */
    private function updateWalletData(): bool
    {
        $langId = User::getAttributesById($this->order['order_user_id'], 'user_lang_id');
        $reason = Label::getLabel('LBL_WALLET_MONEY_ADDED', $langId);
        $txn = new Transaction($this->order['order_user_id'], Transaction::TYPE_MONEY_DEPOSIT);
        if (!$txn->credit($this->order['order_net_amount'], $reason)) {
            $this->error = $txn->getError();
            return false;
        }
        $notifiVar = ['{reason}' => $reason, '{amount}' => MyUtility::formatMoney($this->order['order_net_amount'])];
        $notifi = new Notification($this->order['order_user_id'], Notification::TYPE_WALLET_CREDIT);
        $notifi->sendNotification($notifiVar);
        return true;
    }

    /**
     * Update Gift Card Order
     * 
     * @return bool
     */
    private function updateGiftcardData(): bool
    {
        $giftcard = new Giftcard();
        $giftcard->sendMailToAdminAndRecipient($this->orderId);
        return true;
    }

    /**
     * Update Subscription Plan Order
     * 
     * @return bool
     */
    private function updateSubscriptionPlanData(): bool
    {
        if (!empty(OrderSubscriptionPlan::getActivePlan($this->order['order_user_id']))) {
            if (OrderSubscriptionPlan::cancelPendingOrders($this->order['order_user_id'])) {
                $this->error = Label::getLabel('LBL_SUBSCRIPTION_PENDING_ORDER_UPDATE_FAILED');
                return false;
            }
            return true;
        }
        if (OrderSubscriptionPlan::completeExpiredPlans($this->order['order_user_id'])) {
            $this->error = Label::getLabel('LBL_SUBSCRIPTION_EXPIRED_ORDER_UPDATE_FAILED');
            return false;
        }
        $learner = User::getAttributesById($this->order['order_user_id'], ['user_lang_id', 'user_first_name', 'user_last_name', 'user_email', 'user_timezone']);
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL, 'ordsplan');
        $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'subplan.subplan_id = ordsplan.ordsplan_plan_id', 'subplan');
        $srch->joinTable(SubscriptionPlan::DB_TBL_LANG, 'LEFT JOIN', 'subplang.subplang_subplan_id = subplan.subplan_id and subplang.subplang_lang_id = ' . $learner['user_lang_id'], 'subplang');
        $srch->addCondition('ordsplan_order_id', '=', $this->orderId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields([
            'ordsplan_id',
            'IFNULL(subplang.subplang_subplan_title, subplan.subplan_title) AS plan_name',
            'ordsplan_amount',
            'ordsplan_lessons',
            'ordsplan_start_date',
            'ordsplan_end_date',
            'ordsplan_duration',
            'ordsplan_lessons'
        ]);
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($record)) {
            $this->error = Label::getLabel('LBL_SUBSCRIPTION_ORDER_NOT_FOUND');
            return false;
        }
        $subPlan = new OrderSubscriptionPlan($record['ordsplan_id']);
        $subPlan->assignValues(['ordsplan_status' => OrderSubscriptionPlan::ACTIVE]);
        if (!$subPlan->save()) {
            $this->error = $subPlan->getError();
            return false;
        }
        $notifiVar = ['{plan_name}' =>  ucfirst($record['plan_name'])];
        $notifi = new Notification($this->order['order_user_id'], Notification::TYPE_SUB_PLAN_PURCHASED);
        $notifi->sendNotification($notifiVar);
        /* Email Notification  */
        $startDate = MyDate::convert($record['ordsplan_start_date'], $learner['user_timezone']);
        $endDate = MyDate::convert($record['ordsplan_end_date'], $learner['user_timezone']);
        $vars = [
            '{learner_name}' => ucwords($learner['user_first_name'] . ' ' . $learner['user_last_name']),
            '{subscription_plan}' => ucfirst($record['plan_name']),
            '{payment}' => MyUtility::formatMoney($record['ordsplan_amount']),
            '{start_date}' => MyDate::showDate($startDate, true, $learner['user_lang_id']),
            '{end_date}' => MyDate::showDate($endDate, true, $learner['user_lang_id']),
            '{duration}' => $record['ordsplan_duration'] . ' ' . Label::getLabel('LBL_MINS', $learner['user_lang_id']),
            '{lesson_count}' => $record['ordsplan_lessons'] . ' ' . Label::getLabel('LBL_LESSON(S)', $learner['user_lang_id']),
            '{order_link}' => $this->orderId,
        ];

        /* Subscription purchased email to learner */
        $mail = new FatMailer($learner['user_lang_id'], 'subscription_booking_email_to_learner');
        $mail->setVariables($vars);
        $mail->sendMail([$learner['user_email']]);

        /* Subscription purchased email to admin */
        $adminTimeZone = MyUtility::getSuperAdminTimeZone();
        $subPlan = current($subPlan->getSubOrdByIds([$record['ordsplan_id']], FatApp::getConfig("CONF_DEFAULT_LANG")));
        $vars = [
            '{learner_name}' => ucwords($learner['user_first_name'] . ' ' . $learner['user_last_name']),
            '{subscription_plan}' => ucfirst($subPlan['plan_name']),
            '{payment}' => MyUtility::formatMoney($record['ordsplan_amount']),
            '{start_date}' => MyDate::convert($record['ordsplan_start_date'], $adminTimeZone),
            '{end_date}' => MyDate::convert($record['ordsplan_end_date'], $adminTimeZone),
            '{duration}' => $record['ordsplan_duration'] . ' ' . Label::getLabel('LBL_MINS', FatApp::getConfig("CONF_DEFAULT_LANG")),
            '{lesson_count}' => $record['ordsplan_lessons'] . ' ' . Label::getLabel('LBL_LESSON(S)', FatApp::getConfig("CONF_DEFAULT_LANG")),
            '{order_link}' => $this->orderId,
        ];
        $vars['{start_date}'] = MyDate::showDate($vars['{start_date}'], true, FatApp::getConfig("CONF_DEFAULT_LANG"));
        $vars['{end_date}'] = MyDate::showDate($vars['{end_date}'], true, FatApp::getConfig("CONF_DEFAULT_LANG")) . ' (' . $adminTimeZone . ')';

        $mail = new FatMailer(FatApp::getConfig("CONF_DEFAULT_LANG"), 'subscription_booking_email_to_admin');
        $mail->setVariables($vars);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);

        $setting = new UserSetting($this->order['order_user_id']);
        if (!$setting->saveData(['user_autorenew_subscription' => AppConstant::ACTIVE])) {
            $this->error = $setting->getError();
        }
        return true;
    }

    /**
     * Get Payments By Order Id
     * @param int $orderId
     * @param bool $joinPaymentMethod
     * @param int $langId
     * @return array
     */
    public static function getPaymentByTxnId(string $txnId): array
    {
        $src = new SearchBase(Order::DB_TBL_PAYMENT, 'ordpay');
        $src->addMultipleFields(['ordpay.*']);
        $src->addCondition('ordpay_txn_id', '=', $txnId);
        $src->doNotCalculateRecords();
        $src->doNotLimitRecords();
        return FatApp::getDb()->fetch($src->getResultSet());
    }

    /**
     * Add Lesson Event
     * 
     * @param array $subOrders
     * @param string $tlangName
     * @return bool
     */
    private function addLessonEvent(array $subOrders): bool
    {
        foreach ($subOrders as $lesson) {
            if ($lesson['ordles_status'] != Lesson::SCHEDULED) {
                continue;
            }
            if (!empty($lesson['learner_google_token'])) {
                $lesson['tlang_name'] = $lesson['learner_tlang_name'];
                $lesson['google_token'] = $lesson['learner_google_token'];
                $lesson['lang_id'] = $lesson['learner_lang_id'];
                $googleCalendar = new GoogleCalendarEvent($this->order['order_user_id'], $lesson['ordles_id'], AppConstant::LESSON);
                $googleCalendar->addLessonEvent($lesson);
            }
            if (!empty($lesson['teacher_google_token'])) {
                $lesson['tlang_name'] = $lesson['teacher_tlang_name'];
                $lesson['google_token'] = $lesson['teacher_google_token'];
                $lesson['lang_id'] = $lesson['teacher_lang_id'];
                $googleCalendar = new GoogleCalendarEvent($lesson['ordles_teacher_id'], $lesson['ordles_id'], AppConstant::LESSON);
                $googleCalendar->addLessonEvent($lesson);
            }
        }
        return true;
    }

    /**
     * Check Meeting License
     * 
     * @param array $subOrders
     * @return bool
     */
    private function checkMeetingLicense(array $subOrders): bool
    {
        foreach ($subOrders as $lesson) {
            if ($lesson['ordles_status'] != Lesson::SCHEDULED) {
                continue;
            }
            Meeting::checkLicense($lesson['ordles_lesson_starttime'], $lesson['ordles_lesson_endtime']);
        }
        return true;
    }

    private function updatePaymentMethod(int $pmethodId): bool
    {
        $order = new TableRecord(Order::DB_TBL);
        $order->setFldValue('order_pmethod_id', $pmethodId);
        if (!$order->update(['smt' => 'order_id = ?', 'vals' => [$this->orderId]])) {
            $this->error = $order->getError();
            return false;
        }
        return true;
    }
}
