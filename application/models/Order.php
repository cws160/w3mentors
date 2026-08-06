<?php

/**
 * This class is used to handle Order
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Order extends MyAppModel
{

    const DB_TBL = 'tbl_orders';
    const DB_TBL_PREFIX = 'order_';
    const DB_TBL_LESSON = 'tbl_order_lessons';
    const DB_TBL_SUBSCR = 'tbl_order_subscriptions';
    const DB_TBL_GCLASS = 'tbl_order_classes';
    const DB_TBL_PACKAG = 'tbl_order_packages';
    const DB_TBL_COURSE = 'tbl_order_courses';
    const DB_TBL_PAYMENT = 'tbl_order_payments';
    const DB_TBL_SUBPLAN = 'tbl_order_subscription_plans';
    /* Order Types */
    const TYPE_LESSON = 1;
    const TYPE_SUBSCR = 2;
    const TYPE_GCLASS = 3;
    const TYPE_PACKGE = 4;
    const TYPE_COURSE = 5;
    const TYPE_WALLET = 6;
    const TYPE_GFTCRD = 7;
    const TYPE_SUBPLAN = 18;
    /* Order Status */
    const STATUS_INPROCESS = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELLED = 3;
    /* Payment Status */
    const UNPAID = 0;
    const ISPAID = 1;

    private $userId;
    private $orderId;
    private $rewardPoint = 0;
    private $rewardValue = 0;
    private $coupon = [];
    private $lessons = [];
    private $subscrp = [];
    private $classes = [];
    private $packags = [];
    private $courses = [];
    private $subPlans = [];

    /**
     * Initialize Order
     * @param int $orderId
     * @param int $userId
     */
    public function __construct(int $orderId = 0, int $userId = 0)
    {
        $this->userId = $userId;
        $this->orderId = $orderId;
        parent::__construct(static::DB_TBL, 'order_id', $orderId);
    }

    /**
     * Add Lessons
     * 
     * @param array $lessons = [
     *          ordles_teacher_id, ordles_tlang_id, ordles_duration, ordles_type,
     *          ordles_quantity, ordles_starttime, ordles_endtime
     *          ];
     * @return bool
     */
    private function addLessons(array $lessons): bool
    {
        if (empty($lessons)) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }

        foreach ($lessons as $lesson) {
            $lessonObj = new Lesson(0, $this->userId, User::LEARNER);
            if (!$lesson = $lessonObj->getLessonPrice($lesson)) {
                FatUtility::dieJsonError($lessonObj->getError());
            }
            if ($this->userId == $lesson['ordles_teacher_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_LESSON(S)'));
            }
            if (isset($lesson['ordles_offline']) && $lesson['ordles_offline'] == AppConstant::YES) {
                if (!User::offlineSessionsEnabled($lesson['ordles_teacher_id'])) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_OFFLINE_LESSONS_NOT_AVAILABLE'));
                }
                $usrAddres = new UserAddresses($lesson['ordles_teacher_id'], $lesson['ordles_address_id']);
                if (!$usrAddres->getAddressById(0)) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_ADDRESS_FOR_OFFLINE_LESSON_NOT_AVAILABLE'));
                }
            } else {
                Meeting::zoomVerificationCheck($lesson['ordles_teacher_id']);
            }

            $comm = Commission::getCommission($lesson['ordles_teacher_id']);
            $lessonData = [
                'ordles_type' => $lesson['ordles_type'],
                'ordles_amount' => OfferPrice::applyLessonOffer($this->userId, $lesson),
                'ordles_teacher_id' => $lesson['ordles_teacher_id'],
                'ordles_tlang_id' => $lesson['ordles_tlang_id'],
                'ordles_commission' => $comm['comm_lessons'],
                'ordles_lesson_starttime' => null,
                'ordles_lesson_endtime' => null,
                'ordles_duration' => $lesson['ordles_duration'],
                'ordles_offline' => $lesson['ordles_offline'] ?? 0,
                'ordles_address' => $lesson['ordles_address'] ?? Null,
                'ordles_ordsplan_id' => $lesson['ordles_ordsplan_id'] ?? Null,
                'ordles_status' => Lesson::UNSCHEDULED,
            ];
            foreach ($lesson['lessons'] as $value) {
                $value = array_merge($value, $lessonData);
                $value['ordles_lesson_starttime'] = $value['ordles_starttime'];
                $value['ordles_lesson_endtime'] = $value['ordles_endtime'];
                if (!empty($value['ordles_lesson_starttime']) && !empty($value['ordles_lesson_endtime'])) {
                    $value['ordles_status'] = Lesson::SCHEDULED;
                }
                array_push($this->lessons, $value);
            }
        }
        return true;
    }

    /**
     * Add Subscription
     * 
     * @param array $subscription
     * @return bool
     */
    private function addSubscriptions(array $subscription)
    {
        if (empty($subscription)) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }
        $lesson = new Lesson(0, $this->userId, User::LEARNER);
        if (!$subscription = $lesson->getLessonPrice($subscription)) {
            $this->error = $lesson->getError();
            return false;
        }
        if ($this->userId == $subscription['ordles_teacher_id']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_LESSON(S)'));
        }
        if (isset($subscription['ordles_offline']) && $subscription['ordles_offline'] == AppConstant::YES) {
            if (!User::offlineSessionsEnabled($subscription['ordles_teacher_id'])) {
                FatUtility::dieJsonError(Label::getLabel('LBL_OFFLINE_LESSONS_NOT_AVAILABLE'));
            }
            $usrAddres = new UserAddresses($subscription['ordles_teacher_id'], $subscription['ordles_address_id']);
            if (!$usrAddres->getAddressById(0)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ADDRESS_FOR_OFFLINE_LESSON_NOT_AVAILABLE'));
            }
        } else {
            Meeting::zoomVerificationCheck($subscription['ordles_teacher_id']);
        }
        $lessonAmount = OfferPrice::applyLessonOffer($this->userId, $subscription);
        $comm = Commission::getCommission($subscription['ordles_teacher_id']);
        $subscription['total_amount'] = $lessonAmount * $subscription['ordles_quantity'];
        $subscription['ordsub_teacher_id'] = $subscription['ordles_teacher_id'];
        $lessonData = [
            'ordles_type' => Lesson::TYPE_SUBCRIP,
            'ordles_amount' => $lessonAmount,
            'ordles_teacher_id' => $subscription['ordles_teacher_id'],
            'ordles_tlang_id' => $subscription['ordles_tlang_id'],
            'ordles_commission' => $comm['comm_lessons'],
            'ordles_duration' => $subscription['ordles_duration'],
            'ordles_status' => Lesson::UNSCHEDULED,
            'ordles_offline' => $subscription['ordles_offline'] ?? 0,
        ];
        foreach ($subscription['lessons'] as $key => $value) {
            $value = array_merge($value, $lessonData);
            $value['ordles_lesson_starttime'] = $value['ordles_starttime'];
            $value['ordles_lesson_endtime'] = $value['ordles_endtime'];
            if (!empty($value['ordles_lesson_starttime']) && !empty($value['ordles_lesson_endtime'])) {
                $value['ordles_status'] = Lesson::SCHEDULED;
            }
            $subscription['lessons'][$key] = $value;
        }
        $this->subscrp = $subscription;
        return true;
    }

    /**
     * Add Classes
     * 
     * @param array $classes
     * @return bool
     */
    private function addClasses(array $classes): bool
    {
        if (!GroupClass::isEnabled()) {
            $this->error = Label::getLabel('LBL_CLASS_MODULE_NOT_AVAILABLE');
            return false;
        }
        if (empty($classes)) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }
        foreach ($classes as $classId => $value) {
            $classObj = new GroupClass($classId, $this->userId, User::LEARNER);
            if (!$class = $classObj->getClassToBook()) {
                $this->error = Label::getLabel('LBL_CLASS_NOT_AVAILABLE');
                return false;
            }
            if ($this->userId == $class['grpcls_teacher_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_CLASS'));
            }
            $avail = new Availability($this->userId);
            if (!$avail->isUserAvailable($class['grpcls_start_datetime'], $class['grpcls_end_datetime'])) {
                $this->error = $avail->getError();
                return false;
            }
            $unpaidSeats = OrderClass::getUnpaidSeats([$classId]);
            $unpaidSeatCount = $unpaidSeats[$classId] ?? 0;
            if (!empty($unpaidSeatCount) && $class['grpcls_total_seats'] <= ($unpaidSeatCount + $class['grpcls_booked_seats'])) {
                $this->error = Label::getLabel('LBL_PROCESSING_CLASS_ORDER_TEXT');
                return false;
            }
            if (isset($value['grpcls_offline']) && $value['grpcls_offline'] == AppConstant::YES) {
                if ($class['grpcls_offline'] == AppConstant::NO) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_CLASS_NOT_AVAILABLE_AS_OFFLINE._PLEASE_TRY_AGAIN'));
                }
            } else {
                Meeting::zoomVerificationCheck($class['grpcls_teacher_id']);
            }
            $amount = OfferPrice::applyClassOffer($this->userId, $class);
            $comm = Commission::getCommission($class['grpcls_teacher_id']);
            array_push($this->classes, [
                'ordcls_starttime' => null,
                'ordcls_endtime' => null,
                'ordcls_amount' => $amount,
                'ordcls_grpcls_id' => $classId,
                'ordcls_status' => OrderClass::SCHEDULED,
                'ordcls_type' => GroupClass::TYPE_REGULAR,
                'ordcls_commission' => $comm['comm_classes'],
                'ordcls_address' => $value['ordcls_address'],
            ]);
        }
        return true;
    }

    /**
     * Add packages
     * 
     * @param array $packages
     * @return bool
     */
    private function addPackages(array $packages): bool
    {
        if (!GroupClass::isEnabled()) {
            $this->error = Label::getLabel('LBL_CLASS_MODULE_NOT_AVAILABLE');
            return false;
        }
        if (empty($packages)) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }
        foreach ($packages as $packageId => $value) {
            $classObj = new GroupClass($packageId, $this->userId, User::LEARNER);
            if (!$package = $classObj->getPackageToBook()) {
                $this->error = Label::getLabel('LBL_CLASS_PACKAGE_NOT_AVAILABLE');
                return false;
            }
            if ($this->userId == $package['grpcls_teacher_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_PACKAGE'));
            }
            /* Check In Process Orders */
            $unpaidSeats = OrderPackage::getUnpaidSeats([$packageId])[$packageId] ?? 0;
            if ($package['grpcls_total_seats'] <= ($package['grpcls_booked_seats'] + $unpaidSeats)) {
                $this->error = Label::getLabel('LBL_PROCESSING_PACKAGE_ORDER_TEXT');
                return false;
            }
            if (isset($value['grpcls_offline']) && $value['grpcls_offline'] == AppConstant::YES) {
                if ($package['grpcls_offline'] == AppConstant::NO) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_PACKAGE_NOT_AVAILABLE_AS_OFFLINE._PLEASE_TRY_AGAIN'));
                }
            } else {
                Meeting::zoomVerificationCheck($package['grpcls_teacher_id']);
            }
            /* Check Learner Availability */
            $avail = new Availability($this->userId);
            $classes = PackageSearch::getClasses($packageId);
            foreach ($classes as $class) {
                $starttime = MyDate::formatToSystemTimezone($class['grpcls_start_datetime']);
                $endtime = MyDate::formatToSystemTimezone($class['grpcls_end_datetime']);
                if (!$avail->isUserAvailable($starttime, $endtime)) {
                    $this->error = $avail->getError();
                    return false;
                }
            }
            $amount = OfferPrice::applyPackageOffer($this->userId, $package);
            $comm = Commission::getCommission($package['grpcls_teacher_id']);
            $record = [
                'classes' => $classes,
                'ordpkg_offline' => $package['grpcls_offline'],
                'ordpkg_amount' => $amount,
                'ordpkg_package_id' => $packageId,
                'ordpkg_commission' => $comm['comm_classes'],
                'ordpkg_status' => OrderPackage::SCHEDULED,
                'ordpkg_address' => $value['ordcls_address'],
            ];
            array_push($this->packags, $record);
        }
        return true;
    }

    /**
     * Add Courses
     * 
     * @param array $courseIds
     * @return bool
     */
    private function addCourses(array $courses): bool
    {
        if (!Course::isEnabled()) {
            $this->error = Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE');
            return false;
        }
        if (empty($courses)) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }

        $courseIds = array_keys($courses);
        $srch = new SearchBase(Course::DB_TBL, 'course');
        $srch->addCondition('course.course_id', 'IN', $courseIds);
        $srch->addCondition('course.course_status', '=', Course::PUBLISHED);
        $srch->addCondition('course.course_active', '=', AppConstant::ACTIVE);
        $srch->addMultipleFields(['course_id', 'course_price', 'course_user_id', 'course_type']);
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet(), 'course_id');
        if (count($rows) < count($courseIds)) {
            $this->error = Label::getLabel('LBL_COURSE_NOT_AVAILABLE');
            return false;
        }
        foreach ($courseIds as $courseId) {
            if ($this->userId == $rows[$courseId]['course_user_id']) {
                FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_COURSE'));
            }
            $commission = Commission::getCommission($rows[$courseId]['course_user_id']);
            array_push($this->courses, [
                'ordcrs_course_id' => $courseId,
                'ordcrs_course_type' => $rows[$courseId]['course_type'],
                'ordcrs_amount' => $rows[$courseId]['course_price'],
                'ordcrs_commission' => $commission['comm_courses'],
                'ordcrs_status' => OrderCourse::PENDING,
                'ordcrs_payment' => AppConstant::UNPAID,
            ]);
        }
        return true;
    }

    /**
     * Add Subscription Plan
     * 
     * @param array $plans
     * @return bool
     */
    private function addSubscriptionPlan(array $plans): bool
    {
        if (empty($plans)) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }
        $planIds = array_keys($plans);
        $rows = SubscriptionPlan::getByIds(0, $planIds);
        if (count($rows) < count($planIds)) {
            $this->error = Label::getLabel('LBL_Plan_NOT_AVAILABLE');
            return false;
        }
        $rows = array_combine(array_column($rows, 'subplan_id'), $rows);
        foreach ($planIds as $planId) {
            $noOfDays = $rows[$planId]['subplan_validity'] * 7;
            $subscriptionDates = MyDate::getSubscriptionPlanDates($noOfDays);
            array_push($this->subPlans, [
                'ordsplan_plan_id' => $planId,
                'ordsplan_user_id' => $this->userId,
                'ordsplan_amount' => $rows[$planId]['subplan_price'],
                'ordsplan_payment' => AppConstant::UNPAID,
                'ordsplan_status' => OrderSubscriptionPlan::PENDING,
                'ordsplan_lessons' => $rows[$planId]['subplan_lesson_count'],
                'ordsplan_duration' => $rows[$planId]['subplan_lesson_duration'],
                'ordsplan_validity' => $rows[$planId]['subplan_validity'],
                'ordsplan_created' => date('Y-m-d H:i:s'),
                'ordsplan_updated' => date('Y-m-d H:i:s'),
                ...$subscriptionDates
            ]);
        }
        return true;
    }

    /**
     * Place Order
     * 
     * Prepare Order Data
     * Insert Order Record
     * Insert Order Coupon
     * Insert Order Lessons
     * Insert Order Classes
     * Insert Order Courses
     * Add Order Payments
     * 
     * @param int $type
     * @param int $pmethodId
     * @param int $addPay
     * @return bool
     */
    public function placeOrder(int $type, int $pmethodId, int $addPay): bool
    {
        if ($addPay == AppConstant::YES) {
            $methodId = $pmethodId;
            $balance = User::getWalletBalance($this->userId);
            $remaining = $this->getNetAmount() - $balance;
            if ($remaining <= 0) {
                $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                return false;
            }
            $wallet = PaymentMethod::getByCode(WalletPay::KEY);
            $pmethodId = FatUtility::int($wallet['pmethod_id']);
        }
        $currency = MyUtility::getSystemCurrency();
        $orderData = [
            'order_type' => $type,
            'order_user_id' => $this->userId,
            'order_addedon' => date('Y-m-d H:i:s'),
            'order_total_amount' => $this->getTotal(),
            'order_net_amount' => $this->getNetAmount(),
            'order_item_count' => $this->getItemCount(),
            'order_payment_status' => AppConstant::UNPAID,
            'order_discount_value' => $this->getDiscount(),
            'order_reward_value' => $this->rewardValue,
            'order_pmethod_id' => FatUtility::int($pmethodId),
            'order_currency_code' => $currency['currency_code'],
            'order_currency_value' => $currency['currency_value'],
            'order_status' => static::STATUS_INPROCESS,
        ];
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        /* Insert Order Record */
        $this->assignValues($orderData);
        if (!$this->addNew(['HIGH_PRIORITY'])) {
            $db->rollbackTransaction();
            return false;
        }
        $this->orderId = $this->getMainTableRecordId();
        $this->mainTableRecordId = $this->orderId;
        if (!$this->insertCoupon()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->insertLessons()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->insertSubscriptions()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->insertClasses()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->insertPackages()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->insertCourses()) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->insertSubscriptionPlans()) {
            $db->rollbackTransaction();
            return false;
        }
        if ($addPay && !$this->placeWalletOrder($remaining, $methodId)) {
            $db->rollbackTransaction();
            return false;
        }
        if ($this->rewardPoint) {
            $record = new RewardPoint($this->userId);
            if (!$record->usedRewards($this->orderId, $this->rewardPoint)) {
                $db->rollbackTransaction();
                $this->error = $record->getError();
                return false;
            }
        }
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Place Wallet Order
     * 
     * @param float $amount
     * @param int $pmethodId
     * @return bool
     */
    public function placeWalletOrder(float $amount, int $pmethodId): bool
    {
        $currency = MyUtility::getSystemCurrency();
        $this->assignValues([
            'order_type' => static::TYPE_WALLET,
            'order_user_id' => $this->userId,
            'order_addedon' => date('Y-m-d H:i:s'),
            'order_item_count' => 1,
            'order_discount_value' => 0,
            'order_reward_value' => 0,
            'order_net_amount' => $amount,
            'order_total_amount' => $amount,
            'order_pmethod_id' => $pmethodId,
            'order_status' => static::STATUS_INPROCESS,
            'order_payment_status' => AppConstant::UNPAID,
            'order_currency_code' => $currency['currency_code'],
            'order_currency_value' => $currency['currency_value'],
            'order_related_order_id' => $this->getMainTableRecordId(),
        ]);
        return $this->addNew(['HIGH_PRIORITY']);
    }

    /**
     * Place recurring Subscription Order
     * 
     * @param float $amount
     * @param int $pmethodId
     * @return bool
     */
    public function recurringSubscription(array $subscrp): bool
    {
        $currency = MyUtility::getSystemCurrency();
        $this->assignValues([
            'order_type' => static::TYPE_SUBSCR,
            'order_user_id' => $this->userId,
            'order_addedon' => date('Y-m-d H:i:s'),
            'order_item_count' => $subscrp['order_item_count'],
            'order_net_amount' => $subscrp['order_net_amount'],
            'order_total_amount' => $subscrp['order_net_amount'],
            'order_pmethod_id' => $subscrp['order_pmethod_id'],
            'order_status' => static::STATUS_INPROCESS,
            'order_payment_status' => AppConstant::UNPAID,
            'order_currency_code' => $currency['currency_code'],
            'order_currency_value' => $currency['currency_value'],
        ]);
        if (!$this->addNew(['HIGH_PRIORITY'])) {
            return false;
        }
        $tabelRecord = new TableRecord(Subscription::DB_TBL);
        $tabelRecord->assignValues([
            'ordsub_order_id' => $this->getMainTableRecordId(),
            'ordsub_teacher_id' => $subscrp['ordsub_teacher_id'],
            'ordsub_startdate' => $subscrp['ordsub_startdate'],
            'ordsub_enddate' => $subscrp['ordsub_enddate'],
            'ordsub_offline' => $subscrp['ordsub_offline'],
            'ordsub_recurring' => AppConstant::YES,
            'ordsub_status' => Subscription::ACTIVE,
            'ordsub_created' => date('Y-m-d H:i:s'),
            'ordsub_updated' => date('Y-m-d H:i:s')
        ]);
        if (!$tabelRecord->addNew(['HIGH_PRIORITY'])) {
            $this->error = $tabelRecord->getError();
            return false;
        }
        $data = [
            'ordles_order_id' => $this->getMainTableRecordId(),
            'ordles_updated' => date('Y-m-d H:i:s'),
        ];
        foreach ($subscrp['lessons'] as $record) {
            $lesson = new TableRecord(static::DB_TBL_LESSON);
            $lesson->assignValues(array_merge($record, $data));
            if (!$lesson->addNew(['HIGH_PRIORITY'])) {
                $this->error = $lesson->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Place recurring renew Subscription Plan Order
     * 
     * @param float $amount
     * @param int $pmethodId
     * @return bool
     */
    public function renewSubscriptionPlan(array $subscrp): bool
    {
        $currency = MyUtility::getSystemCurrency();
        $this->assignValues([
            'order_type' => static::TYPE_SUBPLAN,
            'order_user_id' => $this->userId,
            'order_addedon' => date('Y-m-d H:i:s'),
            'order_item_count' => $subscrp['order_item_count'],
            'order_net_amount' => $subscrp['order_net_amount'],
            'order_total_amount' => $subscrp['order_net_amount'],
            'order_pmethod_id' => $subscrp['order_pmethod_id'],
            'order_status' => static::STATUS_INPROCESS,
            'order_payment_status' => AppConstant::UNPAID,
            'order_currency_code' => $currency['currency_code'],
            'order_currency_value' => $currency['currency_value'],
        ]);
        if (!$this->addNew(['HIGH_PRIORITY'])) {
            return false;
        }
        $tabelRecord = new TableRecord(OrderSubscriptionPlan::DB_TBL);
        $tabelRecord->assignValues([
            'ordsplan_order_id' => $this->getMainTableRecordId(),
            'ordsplan_user_id' => $subscrp['ordsplan_user_id'],
            'ordsplan_start_date' => $subscrp['ordsplan_start_date'],
            'ordsplan_end_date' => $subscrp['ordsplan_end_date'],
            'ordsplan_duration' => $subscrp['ordsplan_duration'],
            'ordsplan_validity' => $subscrp['ordsplan_validity'],
            'ordsplan_amount' => $subscrp['ordsplan_amount'],
            'ordsplan_lessons' => $subscrp['ordsplan_lessons'],
            'ordsplan_lesson_amount' => round(($subscrp['ordsplan_amount'] / $subscrp['ordsplan_lessons']), 2),
            'ordsplan_plan_id' => $subscrp['ordsplan_plan_id'],
            'ordsplan_used_lesson_count' => 0,
            'ordsplan_status' => OrderSubscriptionPlan::PENDING,
            'ordsplan_created' => date('Y-m-d H:i:s'),
            'ordsplan_updated' => date('Y-m-d H:i:s')
        ]);
        if (!$tabelRecord->addNew(['HIGH_PRIORITY'])) {
            $this->error = $tabelRecord->getError();
            return false;
        }
        return true;
    }


    /**
     * Place GiftCard Order
     * 
     * @param array $data
     * @return bool
     */
    public function placeGiftcardOrder(array $data): bool
    {
        $amount = FatUtility::float($data['order_total_amount']);
        $minamount = FatApp::getConfig('MINIMUM_GIFT_CARD_AMOUNT');
        if ($amount < $minamount) {
            $minamount = MyUtility::formatMoney($minamount);
            $label = Label::getLabel("LBL_MINIMUM_GIFT_CARD_{minamount}");
            $this->error = str_replace("{minamount}", $minamount, $label);
            return false;
        }
        $pmethodId = $data['order_pmethod_id'];
        $addpay = FatUtility::int($data['add_and_pay']);
        if ($addpay == AppConstant::YES) {
            $balance = User::getWalletBalance($this->userId);
            $remaining = FatUtility::float($amount - $balance);
            if ($remaining <= 0) {
                $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                return false;
            }
            $methodId = $pmethodId;
            $wallet = PaymentMethod::getByCode(WalletPay::KEY);
            $pmethodId = FatUtility::int($wallet['pmethod_id']);
        }
        $db = FatApp::getDb();
        if (!$db->startTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        $currency = MyUtility::getSystemCurrency();
        $this->assignValues([
            'order_type' => static::TYPE_GFTCRD,
            'order_user_id' => $this->userId,
            'order_addedon' => date('Y-m-d H:i:s'),
            'order_item_count' => 1,
            'order_discount_value' => 0,
            'order_reward_value' => 0,
            'order_net_amount' => $amount,
            'order_total_amount' => $amount,
            'order_payment_status' => AppConstant::UNPAID,
            'order_pmethod_id' => FatUtility::int($pmethodId),
            'order_currency_code' => $currency['currency_code'],
            'order_currency_value' => $currency['currency_value'],
            'order_status' => static::STATUS_INPROCESS,
        ]);
        if (!$this->addNew(['HIGH_PRIORITY'])) {
            $db->rollbackTransaction();
            $this->error = $db->getError();
            return false;
        }
        $cardData = [
            'ordgift_code' => uniqid(),
            'ordgift_expiry' => date('Y-m-d H:i:s', strtotime('+2 months')),
            'ordgift_status' => Giftcard::STATUS_UNUSED,
            'ordgift_order_id' => $this->getMainTableRecordId(),
            'ordgift_receiver_name' => $data['ordgift_receiver_name'],
            'ordgift_receiver_email' => $data['ordgift_receiver_email'],
        ];
        $receiver = User::getByEmail($data['ordgift_receiver_email']);
        if (!empty($receiver['user_email'])) {
            $cardData['ordgift_receiver_id'] = $receiver['user_id'];
        }
        $card = new Giftcard(0);
        $card->assignValues($cardData);
        if (!$card->addNew(['HIGH_PRIORITY'])) {
            $this->error = $card->getError();
            $db->rollbackTransaction();
            return false;
        }
        if ($addpay > 0 && !$this->placeWalletOrder($remaining, $methodId)) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->commitTransaction()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Insert Coupon
     * 
     * @return bool
     */
    private function insertCoupon(): bool
    {
        $couponId = $this->coupon['coupon_id'] ?? 0;
        if ($couponId < 1) {
            return true;
        }
        $coupon = new Coupon($couponId);
        if (!$coupon->increaseUsedCount()) {
            $this->error = $coupon->getError();
            return false;
        }
        $coupon = new TableRecord(Coupon::DB_TBL_HISTORY);
        $coupon->assignValues([
            'couhis_coupon_id' => $couponId,
            'couhis_order_id' => $this->orderId,
            'couhis_created' => date('Y-m-d H:i:s'),
            'couhis_coupon' => json_encode($this->coupon),
        ]);
        if (!$coupon->addNew(['HIGH_PRIORITY'])) {
            $this->error = $coupon->getError();
            return false;
        }
        return true;
    }

    /**
     * Insert Lessons
     * 
     * @return bool
     */
    private function insertLessons(): bool
    {
        if (count($this->lessons) < 1) {
            return true;
        }
        $data = [
            'ordles_order_id' => $this->orderId,
            'ordles_discount' => $this->getLessonDiscount(),
            'ordles_reward_discount' => $this->getLessonRewardDiscount(),
            'ordles_updated' => date('Y-m-d H:i:s'),
        ];
        foreach ($this->lessons as $record) {
            $lesson = new TableRecord(static::DB_TBL_LESSON);
            $lesson->assignValues(array_merge($record, $data));
            if (!$lesson->addNew(['HIGH_PRIORITY'])) {
                $this->error = $lesson->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Insert Subscriptions
     * 
     * @return bool
     */
    private function insertSubscriptions(): bool
    {
        if (count($this->subscrp) < 1) {
            return true;
        }
        $subscription = new TableRecord(Subscription::DB_TBL);
        $subscription->assignValues([
            'ordsub_order_id' => $this->orderId,
            'ordsub_teacher_id' => $this->subscrp['ordsub_teacher_id'],
            'ordsub_startdate' => $this->subscrp['ordsub_startdate'],
            'ordsub_enddate' => $this->subscrp['ordsub_enddate'],
            'ordsub_offline' => $this->subscrp['ordles_offline'],
            'ordsub_recurring' => AppConstant::YES,
            'ordsub_status' => Subscription::ACTIVE,
            'ordsub_created' => date('Y-m-d H:i:s'),
            'ordsub_updated' => date('Y-m-d H:i:s')
        ]);
        if (!$subscription->addNew(['HIGH_PRIORITY'])) {
            $this->error = $subscription->getError();
            return false;
        }
        $data = [
            'ordles_order_id' => $this->orderId,
            'ordles_discount' => $this->getSubscriptionDiscount(),
            'ordles_reward_discount' => $this->getSubRewardDiscount(),
            'ordles_updated' => date('Y-m-d H:i:s'),
            'ordles_address' => $this->subscrp['ordles_address'],
        ];
        foreach ($this->subscrp['lessons'] as $record) {
            $lesson = new TableRecord(static::DB_TBL_LESSON);
            $lesson->assignValues(array_merge($record, $data));
            if (!$lesson->addNew(['HIGH_PRIORITY'])) {
                $this->error = $lesson->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Insert Classes
     * 
     * @return bool
     */
    private function insertClasses(): bool
    {
        if (count($this->classes) < 1) {
            return true;
        }
        $data = [
            'ordcls_order_id' => $this->orderId,
            'ordcls_discount' => $this->getClassDiscount(),
            'ordcls_reward_discount' => $this->getClassRewardDiscount(),
            'ordcls_updated' => date('Y-m-d H:i:s')
        ];
        foreach ($this->classes as $record) {
            $class = new TableRecord(static::DB_TBL_GCLASS);
            $class->assignValues(array_merge($record, $data));
            if (!$class->addNew(['HIGH_PRIORITY'])) {
                $this->error = $class->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Insert Packages
     * 
     * @return bool
     */
    private function insertPackages(): bool
    {
        if (count($this->packags) < 1) {
            return true;
        }
        $discount = FatUtility::float($this->getPackageDiscount() / count($this->packags));
        $reward = FatUtility::float($this->getPackageRewardDiscount() / count($this->packags));
        $data = ['ordpkg_order_id' => $this->orderId, 'ordpkg_discount' => $discount, 'ordpkg_reward_discount' => $reward];
        foreach ($this->packags as $record) {
            $package = new TableRecord(static::DB_TBL_PACKAG);
            $package->assignValues(array_merge($record, $data));
            if (!$package->addNew(['HIGH_PRIORITY'])) {
                $this->error = $package->getError();
                return false;
            }
            $classCount = count($record['classes']);
            foreach ($record['classes'] as $row) {
                $classData = [
                    'ordcls_order_id' => $this->orderId,
                    'ordcls_type' => GroupClass::TYPE_PACKAGE,
                    'ordcls_grpcls_id' => $row['grpcls_id'],
                    'ordcls_commission' => $record['ordpkg_commission'],
                    'ordcls_amount' => $record['ordpkg_amount'] / $classCount,
                    'ordcls_discount' => $data['ordpkg_discount'] / $classCount,
                    'ordcls_reward_discount' => $data['ordpkg_reward_discount'] / $classCount,
                    'ordcls_status' => OrderClass::SCHEDULED,
                    'ordcls_address' => $record['ordpkg_address'],
                ];
                $class = new TableRecord(static::DB_TBL_GCLASS);
                $class->assignValues(array_merge($row, $classData));
                if (!$class->addNew(['HIGH_PRIORITY'])) {
                    $this->error = $class->getError();
                    return false;
                }
                $thread = new Thread(0);
                if ($thread->groupThreadExist($classData['ordcls_grpcls_id'])) {
                    $thread->addThreadUsers([$this->userId]);
                }
            }
        }
        return true;
    }

    /**
     * Insert Courses
     * 
     * @return bool
     */
    private function insertCourses(): bool
    {
        if (count($this->courses) < 1) {
            return true;
        }
        $data = [
            'ordcrs_order_id' => $this->orderId,
            'ordcrs_discount' => $this->getCourseDiscount()
        ];
        foreach ($this->courses as $record) {
            $course = new TableRecord(static::DB_TBL_COURSE);
            $course->assignValues(array_merge($record, $data));
            if (!$course->addNew(['HIGH_PRIORITY'])) {
                $this->error = $course->getError();
                return false;
            }
            $progress = new CourseProgress();
            if (!$progress->setup($course->getId())) {
                $this->error = $progress->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Insert subscription plans
     * 
     * @return bool
     */
    private function insertSubscriptionPlans(): bool
    {
        if (count($this->subPlans) < 1) {
            return true;
        }
        $data = [
            'ordsplan_order_id' => $this->orderId,
            'ordsplan_discount' => $this->getSubPlanDiscount(),
            'ordsplan_reward_discount' =>  $this->getSubPlanRewardDiscount(),
        ];
        $netAmt = $this->getNetAmount();
        foreach ($this->subPlans as $record) {
            $subPlan = new TableRecord(static::DB_TBL_SUBPLAN);
            $subPlan->setFldValue('ordsplan_lesson_amount', round(($netAmt / $record['ordsplan_lessons']), 2));
            $subPlan->assignValues(array_merge($record, $data));
            if (!$subPlan->addNew(['HIGH_PRIORITY'])) {
                $this->error = $subPlan->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Apply Discount Coupon
     * 
     * @param array $coupon
     * @return bool
     */
    public function applyCoupon(array $coupon): bool
    {
        if (empty($coupon)) {
            return true;
        }
        $code = $coupon['coupon_code'] ?? '';
        $couponObj = new Coupon(0, MyUtility::getSiteLangId());
        if (!$row = $couponObj->validateCoupon($code, $this->getTotal(), $this->userId)) {
            $this->error = $couponObj->getError();
            return false;
        }
        $this->coupon = $row;
        return true;
    }

    /**
     * Apply Reward Pints
     *
     * @param int $points
     * @param float $value
     * @return bool
     */
    public function applyRewards(int $points, float $value): bool
    {
        if ($points < 1) {
            return true;
        }
        $rewards = new RewardPoint($this->userId);
        $requiredPoints = RewardPoint::convertToPoints($this->getNetAmount());
        if (!$rewards->validate($requiredPoints)) {
            $this->error = $rewards->getError();
            return false;
        }
        $this->setRewards($points, $value);
        return true;
    }

    /**
     * Get Coupon
     * 
     * @return type
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * Get Discount
     * 
     * @return float
     */
    public function getDiscount(): float
    {
        return FatUtility::float($this->coupon['coupon_discount'] ?? 0);
    }

    /**
     * Get Lesson Discount
     * 
     * @return float
     */
    public function getLessonDiscount(): float
    {
        $discount = $this->getDiscount();
        if (count($this->lessons) > 0 && $discount > 0) {
            return FatUtility::float($discount / count($this->lessons));
        }
        return 0;
    }

    /**
     * Get Lesson Reward Discount
     * 
     * @return float
     */
    public function getLessonRewardDiscount(): float
    {
        if (count($this->lessons) > 0 && $this->rewardValue > 0) {
            return FatUtility::float($this->rewardValue / count($this->lessons));
        }
        return 0;
    }

    /**
     * Get Subscription Discount
     * 
     * @return float
     */
    public function getSubscriptionDiscount(): float
    {
        $discount = $this->getDiscount();
        if (count($this->subscrp) > 0 && $discount > 0) {
            return FatUtility::float($discount / count($this->subscrp['lessons']));
        }
        return 0;
    }

    /**
     * Get Subscription Reward Discount
     * 
     * @return float
     */
    public function getSubRewardDiscount(): float
    {
        if (count($this->subscrp) > 0 && $this->rewardValue > 0) {
            return FatUtility::float($this->rewardValue / count($this->subscrp['lessons']));
        }
        return 0;
    }

    /**
     * Get Class Discount

     * @return float
     */
    public function getClassDiscount(): float
    {
        $discount = $this->getDiscount();
        if (count($this->classes) > 0 && $discount > 0) {
            return FatUtility::float($discount / count($this->classes));
        }
        return 0;
    }

    /**
     * Get Class Reward Discount
     * 
     * @return float
     */
    public function getClassRewardDiscount(): float
    {
        if (count($this->classes) > 0 && $this->rewardValue > 0) {
            return FatUtility::float($this->rewardValue / count($this->classes));
        }
        return 0;
    }

    /**
     * Get Package Discount

     * @return float
     */
    public function getPackageDiscount(): float
    {
        $discount = $this->getDiscount();
        if (count($this->packags) > 0 && $discount > 0) {
            return FatUtility::float($discount / count($this->packags));
        }
        return 0;
    }

    /**
     * Get Package Reward Discount
     * 
     * @return float
     */
    public function getPackageRewardDiscount(): float
    {
        if (count($this->packags) > 0 && $this->rewardValue > 0) {
            return FatUtility::float($this->rewardValue / count($this->packags));
        }
        return 0;
    }

    /**
     * Get Course Discount
     * 
     * @return float
     */
    public function getCourseDiscount(): float
    {
        $discount = $this->getDiscount();
        if (count($this->courses) > 0 && $discount > 0) {
            return FatUtility::float($discount / count($this->courses));
        }
        return 0;
    }

    /**
     * Get Course Reward Discount
     * 
     * @return float
     */
    public function getCourseRewardDiscount(): float
    {
        if (count($this->courses) > 0 && $this->rewardValue > 0) {
            return FatUtility::float($this->rewardValue / count($this->courses));
        }
        return 0;
    }


    /**
     * Get Subscription Discount
     * 
     * @return float
     */
    public function getSubPlanDiscount(): float
    {
        $discount = $this->getDiscount();
        if (count($this->subPlans) > 0 && $discount > 0) {
            return FatUtility::float($discount / count($this->subPlans));
        }
        return 0;
    }

    /**
     * Get Subscription Reward Discount
     * 
     * @return float
     */
    public function getSubPlanRewardDiscount(): float
    {
        if (count($this->subPlans) > 0 && $this->rewardValue > 0) {
            return FatUtility::float($this->rewardValue / count($this->subPlans));
        }
        return 0;
    }


    /**
     * Get Total Amount
     * 
     * @return float
     */
    private function getTotal(): float
    {
        $lessonsAmount = array_sum(array_column($this->lessons, 'ordles_amount'));
        $subscrpAmount = $this->subscrp['total_amount'] ?? 0;
        $classesAmount = array_sum(array_column($this->classes, 'ordcls_amount'));
        $packagsAmount = array_sum(array_column($this->packags, 'ordpkg_amount'));
        $coursesAmount = array_sum(array_column($this->courses, 'ordcrs_amount'));
        $subPlansAmount = array_sum(array_column($this->subPlans, 'ordsplan_amount'));
        return FatUtility::float($lessonsAmount + $subscrpAmount + $classesAmount + $packagsAmount + $coursesAmount + $subPlansAmount);
    }

    /**
     * Get Net Amount
     * 
     * @return float
     */
    private function getNetAmount(): float
    {
        return FatUtility::float($this->getTotal() - $this->getDiscount() - $this->rewardValue);
    }

    /**
     * Get Item Count
     * 
     * @return int
     */
    private function getItemCount(): int
    {
        return array_sum([
            static::TYPE_LESSON => count($this->lessons),
            static::TYPE_SUBSCR => count($this->subscrp['lessons'] ?? []),
            static::TYPE_GCLASS => count($this->classes),
            static::TYPE_PACKGE => count($this->packags),
            static::TYPE_COURSE => count($this->courses),
            static::TYPE_SUBPLAN => count($this->subPlans)
        ]);
    }

    /**
     * Get Order Type Array
     * 
     * @param int $key
     * @return string|array
     */
    public static function getTypeArr(int $key = null)
    {
        $arr = [
            static::TYPE_LESSON => Label::getLabel('LBL_LESSON'),
            static::TYPE_SUBSCR => Label::getLabel('LBL_RECURRING_LESSONS'),
            static::TYPE_GCLASS => Label::getLabel('LBL_GROUP_CLASSES'),
            static::TYPE_PACKGE => Label::getLabel('LBL_CLASS_PACKAGES'),
            static::TYPE_COURSE => Label::getLabel('LBL_COURSE_PURCHASED'),
            static::TYPE_WALLET => Label::getLabel('LBL_WALLET_RECHARGE'),
            static::TYPE_GFTCRD => Label::getLabel('LBL_GIFTCARD_PURCHASED'),
            static::TYPE_SUBPLAN => Label::getLabel('LBL_SUBSCRIPTION_PLAN'),
        ];
        if (!Course::isEnabled()) {
            unset($arr[static::TYPE_COURSE]);
        }
        if (!GroupClass::isEnabled()) {
            unset($arr[static::TYPE_GCLASS]);
            unset($arr[static::TYPE_PACKGE]);
        }
        if (!SubscriptionPlan::isEnabled()) {
            unset($arr[static::TYPE_SUBPLAN]);
        }
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Order Status Array
     *
     * @param int $key
     * @return string|array
     */
    public static function getStatusArr(int $key = null)
    {
        $arr = [
            static::STATUS_INPROCESS => Label::getLabel('LBL_INPROCESS'),
            static::STATUS_COMPLETED => Label::getLabel('LBL_COMPLETED'),
            static::STATUS_CANCELLED => Label::getLabel('LBL_CANCELLED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Payment Status Array
     * 
     * @param int $key
     * @return string|array
     */
    public static function getPaymentArr(int $key = null)
    {
        $arr = [
            static::UNPAID => Label::getLabel('LBL_UNPAID'),
            static::ISPAID => Label::getLabel('LBL_IS_PAID'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Format Order id
     * 
     * @param int $orderId
     * @return string
     */
    public static function formatOrderId(int $orderId): string
    {
        return 'O' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get Order FInfo
     * 
     * @return type
     */
    public function getOrderInfo()
    {
        $src = new SearchBase(Order::DB_TBL);
        $src->addMultipleFields([
            'order_id',
            'order_discount_value',
            'order_reward_value',
            'order_payment_status',
            'order_type',
            'order_net_amount',
            'order_user_id',
            'order_total_amount',
            'order_related_order_id',
            'order_item_count',
        ]);
        $src->addCondition('order_id', '=', $this->orderId);
        $src->doNotCalculateRecords();
        return FatApp::getDb()->fetch($src->getResultSet());
    }

    /**
     * Add Order Items
     * 
     * @param int $orderType
     * @param array $items
     * @return bool
     */
    public function addItems(int $orderType, array $items): bool
    {
        switch ($orderType) {
            case static::TYPE_LESSON:
                return $this->addLessons($items[Cart::LESSON]);
            case static::TYPE_SUBSCR:
                return $this->addSubscriptions($items[Cart::SUBSCR]);
            case static::TYPE_GCLASS:
                return $this->addClasses($items[Cart::GCLASS]);
            case static::TYPE_PACKGE:
                return $this->addPackages($items[Cart::PACKGE]);
            case static::TYPE_COURSE:
                return $this->addCourses($items[Cart::COURSE]);
            case static::TYPE_SUBPLAN:
                return $this->addSubscriptionPlan($items[Cart::SUBSCRIPTIONPLAN]);
            default:
                $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                return false;
        }
        return true;
    }

    /**
     * Get Valid Order to Pay
     * 
     * @return bool|array
     *
     */
    public function getOrderToPay()
    {
        $srch = new SearchBase(static::DB_TBL, 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'users.user_id=orders.order_user_id', 'users');
        $srch->joinTable(PaymentMethod::DB_TBL, 'LEFT JOIN', 'pmethod.pmethod_id=orders.order_pmethod_id', 'pmethod');
        $srch->addMultipleFields([
            'orders.order_id',
            'orders.order_type',
            'orders.order_user_id',
            'orders.order_pmethod_id',
            'orders.order_related_order_id',
            'orders.order_currency_code',
            'orders.order_net_amount',
            'orders.order_total_amount',
            'orders.order_payment_status',
            'users.user_id',
            'users.user_first_name',
            'users.user_last_name',
            'users.user_email',
            'pmethod.pmethod_code',
            'CONCAT(users.user_first_name, " ", users.user_last_name) AS user_name',
            'users.user_country_id',
            'users.user_lang_id',
            'users.user_currency_id',
        ]);
        $srch->addDirectCondition('users.user_deleted IS NULL');
        $srch->addDirectCondition('users.user_verified IS NOT NULL');
        $srch->addCondition('orders.order_id', '=', $this->orderId);
        $srch->addCondition('orders.order_status', '!=', Order::STATUS_CANCELLED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $order = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($order)) {
            $this->error = Label::getLabel('LBL_ORDER_NOT_FOUND');
            return false;
        }
        return $order;
    }

    /**
     * Cancel Order
     * 
     * @return boolean
     */
    public function cancelOrder()
    {
        $src = new SearchBase(Order::DB_TBL, 'orders');
        $src->joinTable(Coupon::DB_TBL_HISTORY, 'LEFT JOIN', 'orders.order_id = couhis.couhis_order_id', 'couhis');
        $src->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = orders.order_user_id', 'user');
        $src->addMultipleFields([
            'order_status',
            'order_id',
            'order_status',
            'order_user_id',
            'order_user_id',
            'order_type',
            'couhis_id',
            'couhis_coupon_id',
            'order_reward_value',
            'user.user_first_name',
            'user.user_last_name',
            'user_email',
            'user_lang_id'
        ]);
        $src->addCondition('order_id', '=', $this->orderId);
        $src->addCondition('order_payment_status', '=', Order::UNPAID);
        $src->addCondition('order_status', '!=', Order::STATUS_CANCELLED);
        $src->doNotCalculateRecords();
        $src->setPageSize(1);
        $data = FatApp::getDb()->fetch($src->getResultSet());
        if (empty($data)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        return $this->cancelUnpaidOrder($data);
    }

    /**
     * Get Child Order Details 
     * 
     * @param integer $orderType
     * @param integer $langId
     * @return null|array
     */
    public function getSubOrders(int $orderType, int $langId, $subOrderId = null)
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->addCondition('order_type', '=', $orderType);
        $srch->addCondition('order_id', '=', $this->orderId);
        switch ($orderType) {
            case Order::TYPE_LESSON:
                $srch->joinTable(Lesson::DB_TBL, 'INNER JOIN', 'ordles.ordles_order_id = orders.order_id', 'ordles');
                $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordles.ordles_teacher_id', 'teacher');
                $srch->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'tlang.tlang_id = ordles.ordles_tlang_id', 'tlang');
                $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
                $srch->addMultipleFields([
                    'teacher.user_timezone',
                    'teacher.user_country_id',
                    'teacher.user_first_name',
                    'teacher.user_last_name',
                    'teacher.user_email',
                    'ordles_id',
                    'ordles_order_id',
                    'ordles_type',
                    'ordles_teacher_id',
                    'ordles_amount',
                    'ordles_tlang_id',
                    'order_item_count',
                    'ordles_lesson_starttime',
                    'ordles_lesson_endtime',
                    'ordles_status',
                    'ordles_duration',
                    'ordles_commission',
                    'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name',
                    'order_addedon',
                    'ordles_offline as is_offline',
                    'ordles_address as teacher_address',
                    'ordles_affiliate_commission',
                    'ordles_reward_discount',
                    'ordles_discount',
                    'ordles_commission_amount',
                    'IFNULL(ordles.ordles_teacher_paid, 0) AS teacher_amount',
                    'IFNULL(ordles.ordles_refund, 0) AS ordles_refund',
                ]);
                if ($subOrderId) {
                    $srch->addCondition('ordles_id', '=', $subOrderId);
                }
                break;
            case Order::TYPE_SUBSCR:
                $srch->joinTable(Subscription::DB_TBL, 'INNER JOIN', 'ordsub.ordsub_order_id = orders.order_id', 'ordsub');
                $srch->joinTable(Lesson::DB_TBL, 'INNER JOIN', 'ordles.ordles_order_id = orders.order_id', 'ordles');
                $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ordles.ordles_teacher_id', 'teacher');
                $srch->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'tlang.tlang_id = ordles.ordles_tlang_id', 'tlang');
                $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
                $srch->addMultipleFields([
                    'teacher.user_timezone',
                    'teacher.user_country_id',
                    'teacher.user_first_name',
                    'teacher.user_last_name',
                    'teacher.user_email',
                    'ordles_id',
                    'ordles_order_id',
                    'ordles_type',
                    'ordles_teacher_id',
                    'ordles_amount',
                    'ordles_tlang_id',
                    'order_item_count',
                    'ordles_lesson_starttime',
                    'ordles_lesson_endtime',
                    'ordles_status',
                    'ordles_duration',
                    'ordles_commission',
                    'teacher.user_timezone',
                    'ordsub_startdate',
                    'ordsub_enddate',
                    'ordsub_status',
                    'ordsub_created',
                    'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name',
                    'order_addedon',
                    'ordsub_offline as is_offline',
                    'ordles_address as teacher_address',
                    'ordles_affiliate_commission',
                    'ordles_commission_amount',
                    'ordles_reward_discount',
                    'ordles_discount',
                    'IFNULL(ordles.ordles_teacher_paid, 0) AS teacher_amount',
                    'IFNULL(ordles.ordles_refund, 0) AS ordles_refund'
                ]);
                if ($subOrderId) {
                    $srch->addCondition('ordles_id', '=', $subOrderId);
                }
                break;
            case Order::TYPE_GCLASS:
                $srch->joinTable(OrderClass::DB_TBL, 'INNER JOIN', 'ordcls.ordcls_order_id = orders.order_id', 'ordcls');
                $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
                $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
                $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'gclang.gclang_grpcls_id = grpcls.grpcls_id  AND gclang.gclang_lang_id = ' . $langId, 'gclang');
                $srch->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'tlang.tlang_id = grpcls.grpcls_tlang_id', 'tlang');
                $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
                $srch->addCondition('ordcls_type', '=', GroupClass::TYPE_REGULAR);
                $srch->addGroupBy('ordcls_id');
                $srch->addMultipleFields([
                    'teacher.user_timezone',
                    'teacher.user_country_id',
                    'teacher.user_first_name',
                    'teacher.user_last_name',
                    'teacher.user_email',
                    'ordcls_id',
                    'ordcls_status',
                    'ordcls_order_id',
                    'grpcls_teacher_id',
                    'ordcls_amount',
                    'grpcls_tlang_id',
                    'grpcls_total_seats',
                    'grpcls_start_datetime',
                    'grpcls_end_datetime',
                    'ordcls_commission',
                    'grpcls_duration',
                    'ordcls_amount',
                    'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name',
                    'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title',
                    'order_addedon',
                    'grpcls_offline as is_offline',
                    'ordcls_address as teacher_address',
                    'ordcls_affiliate_commission',
                    'ordcls_discount',
                    'ordcls_reward_discount',
                    'ordcls_commission_amount',
                    'IFNULL(ordcls_teacher_paid, 0) AS teacher_amount',
                    'IFNULL(ordcls_refund, 0) AS ordcls_refund',
                ]);
                if ($subOrderId) {
                    $srch->addCondition('ordcls_id', '=', $subOrderId);
                }
                break;
            case Order::TYPE_PACKGE:
                $srch->joinTable(OrderPackage::DB_TBL, 'INNER JOIN', 'ordpkg.ordpkg_order_id = orders.order_id', 'ordpkg');
                $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'package.grpcls_id = ordpkg.ordpkg_package_id', 'package');
                $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'packlang.gclang_grpcls_id = package.grpcls_id', 'packlang');
                $srch->joinTable(OrderClass::DB_TBL, 'INNER JOIN', 'ordcls.ordcls_order_id = orders.order_id', 'ordcls');
                $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
                $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'gclang.gclang_grpcls_id = grpcls.grpcls_id  AND gclang.gclang_lang_id = ' . $langId, 'gclang');
                $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
                $srch->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'tlang.tlang_id = grpcls.grpcls_tlang_id', 'tlang');
                $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
                $srch->addCondition('ordcls_type', '=', GroupClass::TYPE_PACKAGE);
                $srch->addGroupBy('ordcls_id');
                $srch->addMultipleFields([
                    'teacher.user_timezone',
                    'teacher.user_country_id',
                    'teacher.user_first_name',
                    'teacher.user_last_name',
                    'teacher.user_email',
                    'ordcls_id',
                    'ordcls_status',
                    'ordcls_order_id',
                    'grpcls.grpcls_teacher_id',
                    'ordcls_amount',
                    'grpcls.grpcls_tlang_id',
                    'grpcls.grpcls_total_seats',
                    'grpcls.grpcls_start_datetime',
                    'grpcls.grpcls_end_datetime',
                    'ordcls_commission',
                    'grpcls.grpcls_duration',
                    'ordcls_amount',
                    'ordpkg_commission',
                    'ordpkg_amount',
                    'ordpkg_discount',
                    'IFNULL(packlang.grpcls_title, package.grpcls_title) as package_title',
                    'package.grpcls_start_datetime as package_start',
                    'package.grpcls_end_datetime as package_end',
                    'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name',
                    'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title',
                    'order_addedon',
                    'ordpkg_offline as is_offline',
                    'ordcls_address as teacher_address',
                    'ordcls_affiliate_commission',
                    'ordpkg_discount',
                    'ordpkg_reward_discount',
                    'ordcls_commission_amount',
                    'IFNULL(ordcls_teacher_paid, 0) AS teacher_amount',
                    'IFNULL(ordcls_refund, 0) AS ordcls_refund',
                    'ordcls_discount',
                    'ordcls_reward_discount',
                ]);
                if ($subOrderId) {
                    $srch->addCondition('ordcls_id', '=', $subOrderId);
                }
                break;
            case Order::TYPE_COURSE:
                $srch->joinTable(OrderCourse::DB_TBL, 'LEFT JOIN', 'orders.order_type = ' . Order::TYPE_COURSE . ' AND orders.order_id = ordcrs.ordcrs_order_id', 'ordcrs');
                $srch->joinTable(Course::DB_TBL, 'LEFT JOIN', 'course.course_id = ordcrs.ordcrs_course_id', 'course');
                $srch->joinTable(Course::DB_TBL_LANG, 'LEFT JOIN', 'course.course_id = crsdetail.course_id', 'crsdetail');
                $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = teacher.user_id', 'teacher');
                $srch->joinTable(CourseLanguage::DB_TBL, 'INNER JOIN', 'clang.clang_id = course.course_clang_id', 'clang');
                $srch->joinTable(CourseLanguage::DB_TBL_LANG, 'LEFT JOIN', 'clang.clang_id = clanglang.clanglang_clang_id AND clanglang.clanglang_lang_id = ' . $langId, 'clanglang');
                $srch->addMultipleFields([
                    'crsdetail.course_title',
                    'teacher.user_first_name',
                    'teacher.user_last_name',
                    'teacher.user_timezone',
                    'teacher.user_country_id',
                    'IFNULL(clanglang.clang_name, clang.clang_identifier) AS clang_name',
                    'course.course_duration',
                    'ordcrs.ordcrs_amount',
                    'teacher.user_email',
                    'ordcrs.ordcrs_status',
                    'ordcrs.ordcrs_commission',
                    'order_addedon',
                    'ordcrs_affiliate_commission',
                    'ordcrs_discount',
                    'ordcrs_commission_amount',
                    'IFNULL(ordcrs.ordcrs_teacher_paid, 0) AS teacher_amount',
                    'IFNULL(ordcrs_refund, 0) AS ordcrs_refund',

                ]);
                break;
            case Order::TYPE_WALLET:
                break;
            case Order::TYPE_GFTCRD:
                $srch->addMultipleFields(['ordgift.*', 'order_addedon']);
                $srch->joinTable(Giftcard::DB_TBL, 'INNER JOIN', 'ordgift.ordgift_order_id = orders.order_id', 'ordgift');
                break;
            case Order::TYPE_SUBPLAN:
                $srch->joinTable(OrderSubscriptionPlan::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_order_id = orders.order_id', 'ordsplan');
                $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'ordsplan.ordsplan_plan_id = sp.subplan_id', 'sp');
                $srch->joinTable(SubscriptionPlan::DB_TBL_LANG, 'LEFT JOIN', 'subplang.subplang_subplan_id = sp.subplan_id and subplang.subplang_lang_id = ' . $langId, 'subplang');
                $srch->addMultipleFields([
                    'ordsplan.ordsplan_id',
                    'ordsplan.ordsplan_start_date',
                    'ordsplan.ordsplan_end_date',
                    'ordsplan.ordsplan_validity',
                    'ordsplan.ordsplan_duration',
                    'ordsplan.ordsplan_lessons',
                    'ordsplan.ordsplan_used_lesson_count',
                    'ordsplan.ordsplan_created',
                    'ordsplan.ordsplan_updated',
                    'ordsplan.ordsplan_status',
                    'ordsplan.ordsplan_reward_discount',
                    'ordsplan.ordsplan_discount',
                    'ordsplan.ordsplan_amount',
                    'IFNULL(subplang.subplang_subplan_title, sp.subplan_title) AS plan_name',
                    'order_addedon'
                ]);
                break;
        }
        $srch->doNotCalculateRecords();
        $orders = FatApp::getDb()->fetchAll($srch->getResultSet());
        $orderTlangs = array_column($orders, 'ordles_tlang_id');
        $tlangArr = TeachLanguage::getTeachLanguages($langId, true, ['tlang_ids' => $orderTlangs]);
        $lessonCommission = $classCommission = $lessonAffComm = $classAffComm = 0;
        foreach ($orders as $key => $order) {
            if (!empty($order['ordles_tlang_id'])) {
                $orders[$key]['tlang_name'] = $tlangArr[$order['ordles_tlang_id']]['tlang_name'] ?? $order['tlang_name'];
            }
            $orders[$key]['order_addedon'] = MyDate::convert($order['order_addedon']);
            if ($orderType == Order::TYPE_LESSON || $orderType == Order::TYPE_SUBSCR) {
                
                $orders[$key]['ordles_lesson_starttime'] = MyDate::convert($order['ordles_lesson_starttime']);
                $orders[$key]['ordles_lesson_endtime'] = MyDate::convert($order['ordles_lesson_endtime']);
                if ($orderType == Order::TYPE_SUBSCR) {
                    $orders[$key]['ordsub_startdate'] = MyDate::convert($order['ordsub_startdate']);
                    $orders[$key]['ordsub_enddate'] = MyDate::convert($order['ordsub_enddate']);
                }
                $lessonAffComm += $order['ordles_affiliate_commission'];
                $lessonCommission += $order['ordles_commission_amount'];
            } elseif ($orderType == Order::TYPE_GCLASS || $orderType == Order::TYPE_PACKGE) {
                $orders[$key]['grpcls_start_datetime'] = MyDate::convert($order['grpcls_start_datetime']);
                $orders[$key]['grpcls_end_datetime'] = MyDate::convert($order['grpcls_end_datetime']);
                if ($orderType == Order::TYPE_PACKGE) {
                    $orders[$key]['package_start'] = MyDate::convert($order['package_start']);
                    $orders[$key]['package_end'] = MyDate::convert($order['package_end']);
                }
                $classCommission += $order['ordcls_commission_amount'];
                $classAffComm += $order['ordcls_affiliate_commission'];
            } elseif ($orderType == Order::TYPE_SUBPLAN) {
                if ($orders[$key]['ordsplan_end_date'] < date('Y-m-d H:i:s') && $orders[$key]['ordsplan_status'] !=  OrderSubscriptionPlan::CANCELLED) {
                    $orders[$key]['ordsplan_status'] = OrderSubscriptionPlan::COMPLETED;
                }
                $orders[$key]['ordsplan_start_date'] = MyDate::convert($order['ordsplan_start_date']);
                $orders[$key]['ordsplan_end_date'] = MyDate::convert($order['ordsplan_end_date']);
            }
        }
        if (isset($orders[0])) {
            $orders[0]['ordles_commission_amount'] = $lessonCommission;
            $orders[0]['ordles_affiliate_commission'] = $lessonAffComm;
            $orders[0]['ordcls_commission_amount'] = $classCommission;
            $orders[0]['ordcls_affiliate_commission'] = $classAffComm;
        }
        return $orders;
    }

    /**
     * Cancel Unpaid Order
     * 
     * @param array $order
     * @param bool $notify
     * @return bool
     * 
     */
    public function cancelUnpaidOrder(array $order, bool $notify = false): bool
    {
        $db = FatApp::getDb();
        $db->startTransaction();
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues(['order_status' => Order::STATUS_CANCELLED]);
        if (!$record->update(['smt' => 'order_id = ?', 'vals' => [$order['order_id']]])) {
            $db->rollbackTransaction();
            $this->error = $record->getError();
            return false;
        }
        if (!empty($order['couhis_id'])) {
            $coupon = new Coupon($order['couhis_coupon_id']);
            if (!$coupon->decreaseUsedCount($order['couhis_id'])) {
                $db->rollbackTransaction();
                $this->error = $coupon->getError();
                return false;
            }
        }
        switch ($order['order_type']) {
            case Order::TYPE_LESSON:
                $table = Lesson::DB_TBL;
                $updateArray = ['ordles_status' => Lesson::CANCELLED];
                $whereArray = ['smt' => 'ordles_order_id = ?', 'vals' => [$order['order_id']]];
                break;
            case Order::TYPE_SUBSCR:
                if (!$db->updateFromArray(
                    Lesson::DB_TBL,
                    ['ordles_status' => Lesson::CANCELLED],
                    ['smt' => 'ordles_order_id = ?', 'vals' => [$order['order_id']]]
                )) {
                    $db->rollbackTransaction();
                    $this->error = $db->getError();
                    return false;
                }
                $table = Subscription::DB_TBL;
                $updateArray = ['ordsub_status' => Subscription::CANCELLED];
                $whereArray = ['smt' => 'ordsub_order_id = ?', 'vals' => [$order['order_id']]];
                break;
            case Order::TYPE_GCLASS:
                $table = OrderClass::DB_TBL;
                $updateArray = ['ordcls_status' => OrderClass::CANCELLED];
                $whereArray = ['smt' => 'ordcls_order_id = ?', 'vals' => [$order['order_id']]];
                break;
            case Order::TYPE_PACKGE:
                if (!$db->updateFromArray(
                    OrderClass::DB_TBL,
                    ['ordcls_status' => OrderClass::CANCELLED],
                    ['smt' => 'ordcls_order_id = ?', 'vals' => [$order['order_id']]]
                )) {
                    $db->rollbackTransaction();
                    $this->error = $db->getError();
                    return false;
                }
                $table = OrderPackage::DB_TBL;
                $updateArray = ['ordpkg_status' => OrderPackage::CANCELLED];
                $whereArray = ['smt' => 'ordpkg_order_id = ?', 'vals' => [$order['order_id']]];
                break;
            case Order::TYPE_GFTCRD:
                $table = Giftcard::DB_TBL;
                $updateArray = ['ordgift_status' => Giftcard::STATUS_CANCELLED];
                $whereArray = ['smt' => 'ordgift_order_id = ?', 'vals' => [$order['order_id']]];
                break;
            case Order::TYPE_COURSE:
                $srch = new SearchBase(OrderCourse::DB_TBL);
                $srch->joinTable(CourseProgress::DB_TBL, 'INNER JOIN', 'ordcrs_id = crspro_ordcrs_id');
                $srch->addCondition('ordcrs_order_id', '=', $order['order_id']);
                $srch->addFld('crspro_id');
                $progressData = FatApp::getDb()->fetch($srch->getResultSet());
                if (!$db->updateFromArray(
                    CourseProgress::DB_TBL,
                    ['crspro_status' => CourseProgress::CANCELLED],
                    ['smt' => 'crspro_id = ?', 'vals' => [$progressData['crspro_id']]]
                )) {
                    $db->rollbackTransaction();
                    $this->error = $db->getError();
                    return false;
                }
                $table = OrderCourse::DB_TBL;
                $updateArray = ['ordcrs_status' => OrderCourse::CANCELLED];
                $whereArray = ['smt' => 'ordcrs_order_id = ?', 'vals' => [$order['order_id']]];
                break;
            case Order::TYPE_SUBPLAN:
                $table = OrderSubscriptionPlan::DB_TBL;
                $updateArray = ['ordsplan_status' => OrderSubscriptionPlan::CANCELLED];
                $whereArray = ['smt' => 'ordsplan_order_id = ?', 'vals' => [$order['order_id']]];
                break;
        }
        if (!empty($table) && !$db->updateFromArray($table, $updateArray, $whereArray)) {
            $db->rollbackTransaction();
            $this->error = $db->getError();
            return false;
        }
        if ($order['order_reward_value'] > 0) {
            $record = new RewardPoint($order['order_user_id']);
            $rewardPoints = RewardPoint::convertToPoints($order['order_reward_value']);
            if (!$record->refundRewards($order['order_id'], $rewardPoints)) {
                $db->rollbackTransaction();
                $this->error = $record->getError();
                return false;
            }
        }
        if ($notify == false) {
            $db->commitTransaction();
            return true;
        }
        $notifi = new Notification($order['order_user_id'], Notification::TYPE_ORDER_CANCELLED);
        $notifi->sendNotification(['{orderid}' => Order::formatOrderId($order['order_id'])]);
        $db->commitTransaction();
        $mail = new FatMailer($order['user_lang_id'], 'order_cancelled_by_admin');
        $vars = [
            '{order_id}' => Order::formatOrderId($order['order_id']),
            '{user_name}' => $order['user_first_name'] . ' ' . $order['user_last_name'],
            '{link}' => MyUtility::makeFullUrl('orders', '', [], CONF_WEBROOT_DASHBOARD),
        ];
        $mail->setVariables($vars);
        $mail->sendMail([$order['user_email']]);
        return true;
    }

    /**
     * Set Reward Discount
     * 
     * @param float $reward
     */
    public function setRewards(int $point, float $value)
    {
        $this->rewardPoint = $point;
        $this->rewardValue = $value;
    }
}
