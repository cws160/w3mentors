<?php

/**
 * This class is used to handle Cart|Checkout
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Cart extends FatModel
{

    public const DB_TBL = 'tbl_user_cart';
    public const DB_TBL_PREFIX = 'cart_';
    /* Item Types */
    public const LESSON = 'LESSON';
    public const SUBSCR = 'SUBSCR';
    public const GCLASS = 'GCLASS';
    public const PACKGE = 'PACKGE';
    public const COURSE = 'COURSE';
    public const SUBSCRIPTIONPLAN = 'SUBSCRIPTIONPLAN';

    private $userId;
    private $langId;
    private $coupon;
    private $reward;
    private $items;

    /**
     * Initialize Cart
     * 
     * @param int $userId
     * @param int $langId
     */
    public function __construct(int $userId, int $langId)
    {
        parent::__construct();
        $this->userId = $userId;
        $this->langId = $langId;
        $row = $this->getData();
        $this->coupon = $row['cart_coupon'] ?? [];
        $this->reward = $row['cart_reward'] ?? 0;
        $this->items = [
            static::LESSON => $row['cart_items'][static::LESSON] ?? [],
            static::SUBSCR => $row['cart_items'][static::SUBSCR] ?? [],
            static::GCLASS => $row['cart_items'][static::GCLASS] ?? [],
            static::PACKGE => $row['cart_items'][static::PACKGE] ?? [],
            static::COURSE => $row['cart_items'][static::COURSE] ?? [],
            static::SUBSCRIPTIONPLAN => $row['cart_items'][static::SUBSCRIPTIONPLAN] ?? [],
        ];
    }

    /**
     * Add Lesson to Cart
     * 
     * 1. Get Valid Lesson Price
     * 2. Create Item Unique Key
     * 3. Apply Offer & Set into Cart
     * 
     * @param array $lesson = [
     *          ordles_teacher_id, ordles_tlang_id, ordles_duration, ordles_type,
     *          ordles_quantity, ordles_starttime, ordles_endtime 
     *          ];
     * @return bool
     */
    public function addLesson(array $lesson): bool
    {
        $this->clear();
        /* Validate Lesson Data */
        $lessonObj = new Lesson(0, $this->userId, User::LEARNER);
        if (!$lesson = $lessonObj->getLessonPrice($lesson)) {
            $this->error = $lessonObj->getError();
            return false;
        }
        /* Create Item Unique Key */
        $key = implode('_', [
            $lesson['ordles_teacher_id'], $lesson['ordles_tlang_id'], $lesson['ordles_duration'],
            $lesson['ordles_type'], $lesson['ordles_starttime'], $lesson['ordles_endtime']
        ]);
        /* Apply Offer & Set into Cart */
        $lesson['ordles_tlang'] = '';
        if ($lesson['ordles_tlang_id'] > 0) {
            $langName = (new UserTeachLanguage($lesson['ordles_teacher_id']))->getById($lesson['ordles_tlang_id'], $this->langId);
            $lesson['ordles_tlang'] = $langName['tlang_name'] ?? '';
        }
        $lesson['ordles_amount'] = OfferPrice::applyLessonOffer($this->userId, $lesson);
        $lesson['total_amount'] = $lesson['ordles_amount'] * $lesson['ordles_quantity'];

        $address = '';
        if (!empty($lesson['ordles_address_id'])) {
            $addrs = (new UserAddresses($lesson['ordles_teacher_id'], $lesson['ordles_address_id']));
            $address = $addrs->getFormattedAddress($this->langId);
            if (empty($address)) {
                $this->error = Label::getLabel('LBL_ADDRESS_FOR_OFFLINE_LESSON_NOT_AVAILABLE');
                return false;
            }
        }


        $lesson['ordles_address'] = $address;
        $this->items[static::LESSON][$key] = $lesson;
        return $this->refresh();
    }

    /**
     * Add Lesson Subscription to Cart
     * 
     * @param array $subscription
     * @return bool
     */
    public function addSubscription(array $subscription): bool
    {
        $this->clear();
        $days = FatApp::getConfig('CONF_RECURRING_SUBSCRIPTION_WEEKS') * 7;
        $dateRange = MyDate::getSubscriptionDates($days);
        $subscription['ordsub_startdate'] = $dateRange['ordsub_startdate'];
        $subscription['ordsub_enddate'] = $dateRange['ordsub_enddate'];
        $subscription['ordles_type'] = Lesson::TYPE_SUBCRIP;
        if ($subscription['ordles_teacher_id'] == $this->userId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_LESSON(S)'));
        }
        $error = false;
        foreach ($subscription['lessons'] as $lesson) {
            if ($lesson['ordles_starttime'] > $subscription['ordsub_enddate'] || $lesson['ordles_endtime'] > $subscription['ordsub_enddate']) {
                $error = true;
                $this->error = Label::getLabel('LBL_SLOT_NOT_AVAILABLE');
                break;
            }
        }
        if ($error) {
            return false;
        }
        $lessonObj = new Lesson(0, $this->userId, User::LEARNER);
        if (!$subscription = $lessonObj->getLessonPrice($subscription)) {
            $this->error = $lessonObj->getError();
            return false;
        }
        $langName = (new UserTeachLanguage($subscription['ordles_teacher_id']))->getById($subscription['ordles_tlang_id'], $this->langId);
        $subscription['ordles_tlang'] = $langName['tlang_name'] ?? '';
        $subscription['ordles_amount'] = OfferPrice::applyLessonOffer($this->userId, $subscription);
        $subscription['total_amount'] = $subscription['ordles_amount'] * $subscription['ordles_quantity'];
        $address = '';
        if (!empty($subscription['ordles_address_id'])) {
            $addrs = (new UserAddresses($subscription['ordles_teacher_id'], $subscription['ordles_address_id']));
            $address = $addrs->getFormattedAddress($this->langId);
            if (empty($address)) {
                $this->error = Label::getLabel('LBL_ADDRESS_FOR_OFFLINE_LESSON_NOT_AVAILABLE');
                return false;
            }
        }
        $subscription['ordles_address'] = $address;
        $this->items[static::SUBSCR] = $subscription;
        return $this->refresh();
    }

    /**
     * Add Group Class
     * 
     * 1. Get Group Class To Book
     * 2. Check Learner Availability
     * 3. Apply Offer & Set into Cart
     * 
     * @param int $classId
     * @return bool
     */
    public function addClass(int $classId): bool
    {
        $this->clear();
        $classObj = new GroupClass($classId, $this->userId, User::LEARNER);
        if (!$class = $classObj->getClassToBook($this->langId)) {
            $this->error = $classObj->getError();
            return false;
        }
        if ($class['grpcls_teacher_id'] == $this->userId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_CLASS'));
        }
        /* Get Group Class To Book */
        $unpaidOrder = OrderClass::getUnpaidClass($this->userId, $classId);
        if (!empty($unpaidOrder)) {
            $order = new Order($unpaidOrder['order_id'], $this->userId);
            if (!$order->cancelUnpaidOrder($unpaidOrder)) {
                $this->error = $order->getError();
                return false;
            }
        }
        /* Check In Process Orders */
        $unpaidSeats = OrderClass::getUnpaidSeats([$classId]);
        $unpaidSeatCount = $unpaidSeats[$classId] ?? 0;
        if (!empty($unpaidSeatCount) && $class['grpcls_total_seats'] <= ($unpaidSeatCount + $class['grpcls_booked_seats'])) {
            $this->error = Label::getLabel('LBL_PROCESSING_CLASS_ORDER_TEXT');
            return false;
        }
        /* Check Learner Availability */
        $avail = new Availability($this->userId);
        if (!$avail->isUserAvailable($class['grpcls_start_datetime'], $class['grpcls_end_datetime'])) {
            $this->error = $avail->getError();
            return false;
        }
        /* Apply Offer & Set into Cart */
        $class['ordcls_amount'] = OfferPrice::applyClassOffer($this->userId, $class);
        $class['total_amount'] = $class['ordcls_amount'];
        $address = '';
        if (!empty($class['grpcls_address_id'])) {
            $addrs = (new UserAddresses($class['grpcls_teacher_id'], $class['grpcls_address_id']));
            $address = $addrs->getFormattedAddress($this->langId);
        }
        $class['ordcls_address'] = $address;
        $this->items[static::GCLASS][$class['grpcls_id']] = $class;
        return $this->refresh();
    }

    /**
     * Add Class Package
     * 
     * 1. Get Group Class Package To Book
     * 2. Check In Process Orders 
     * 3. Check Learner Availability
     * 4. Apply Offer & Set into Cart
     * 
     * @param int $packageId
     * @return bool
     */
    public function addPackage(int $packageId): bool
    {
        $this->clear();
        $packageObj = new GroupClass($packageId, $this->userId, User::LEARNER);
        if (!$package = $packageObj->getPackageToBook($this->langId)) {
            $this->error = $packageObj->getError();
            return false;
        }
        if ($package['grpcls_teacher_id'] == $this->userId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_PACKAGE'));
        }
        /* Get Group Class Package To Book */
        $unpaidOrder = OrderPackage::getUnpaidPackage($this->userId, $packageId);
        if (!empty($unpaidOrder)) {
            $order = new Order($unpaidOrder['order_id'], $this->userId);
            if (!$order->cancelUnpaidOrder($unpaidOrder)) {
                $this->error = $order->getError();
                return false;
            }
        }
        /* Check In Process Orders */
        $unpaidSeats = OrderPackage::getUnpaidSeats([$packageId])[$packageId] ?? 0;
        if ($package['grpcls_total_seats'] <= ($package['grpcls_booked_seats'] + $unpaidSeats)) {
            $this->error = Label::getLabel('LBL_PROCESSING_PACKAGE_ORDER_TEXT');
            return false;
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
        /* Apply Offer & Set into Cart */
        $package['classes'] = $classes;
        $package['grpcls_amount'] = OfferPrice::applyPackageOffer($this->userId, $package);
        $package['total_amount'] = $package['grpcls_amount'];
        $address = '';
        if (!empty($package['grpcls_address_id'])) {
            $addrs = (new UserAddresses($package['grpcls_teacher_id'], $package['grpcls_address_id']));
            $address = $addrs->getFormattedAddress($this->langId);
        }
        $package['ordcls_address'] = $address;
        $this->items[static::PACKGE][$package['grpcls_id']] = $package;
        return $this->refresh();
    }

    /**
     * Add Course to Cart
     * 
     * @param int $courseId
     * @return bool
     */
    public function addCourse(int $courseId): bool
    {
        $this->clear();
        $srch = new CourseSearch($this->langId, $this->userId, 0);
        $srch->applyPrimaryConditions();
        $srch->applySearchConditions([
            'course_status' => Course::PUBLISHED,
            'course_id' => $courseId
        ]);
        $srch->addMultipleFields([
            'crsdetail.course_title AS course_title',
            'course_clang_id',
            'clang_identifier',
            'course_cate_id',
            'course_subcate_id',
            'course.course_price AS course_price',
            'course.course_currency_id AS course_currency_id',
            'course.course_id',
            'course.course_user_id',
            'course.course_user_id as course_teacher_id',
            'crsdetail.course_srchtags',
        ]);
        $srch->addCondition('course.course_active', '=', AppConstant::ACTIVE);
        $courses = $srch->fetchAndFormat();
        $course = current($courses);
        if (empty($course)) {
            $this->error = Label::getLabel('LBL_COURSE_NOT_AVAILABLE');
            return false;
        }
        if ($course['course_user_id'] == $this->userId) {
            $this->error = Label::getLabel('LBL_YOU_ARE_NOT_ALLOWED_TO_ENROLL_TO_YOUR_OWN_COURSE');
            return false;
        }
        $unpaidOrders = OrderCourse::getUnpaidCourses($this->userId, $courseId);
        if (!empty($unpaidOrders)) {
            foreach ($unpaidOrders as $unpaidOrder) {
                $order = new Order($unpaidOrder['order_id'], $this->userId);
                if (!$order->cancelUnpaidOrder($unpaidOrder)) {
                    $this->error = $order->getError();
                    return false;
                }
            }
        }
        $course['total_amount'] = $course['course_price'];
        $this->items[static::COURSE][$courseId] = $course;
        return $this->refresh();
    }

    public function addSubscriptionPlan(int $subPlanId): bool
    {
        $this->clear();
        $subPlans =  SubscriptionPlan::getByIds($this->langId, [$subPlanId]);
        if (empty($subPlans)) {
            $this->error = Label::getLabel('LBL_PLAN_DOES_NOT_EXIST');
            return false;
        }
        $unpaidOrders = OrderSubscriptionPlan::getUnpaidOrders($this->userId);
        if (!empty($unpaidOrders)) {
            foreach ($unpaidOrders as $unpaidOrder) {
                $order = new Order($unpaidOrder['order_id'], $this->userId);
                if (!$order->cancelUnpaidOrder($unpaidOrder)) {
                    $this->error = $order->getError();
                    return false;
                }
            }
        }
        $subPlan =  current($subPlans);
        $subPlan['total_amount'] = $subPlan['subplan_price'];
        $this->items[static::SUBSCRIPTIONPLAN][$subPlanId] = $subPlan;
        return $this->refresh();
    }

    /**
     * Remove Lesson
     * 
     * @param string $key
     * @return bool
     */
    public function removeLesson(string $key): bool
    {
        unset($this->items[static::LESSON][$key]);
        return $this->refresh();
    }

    /**
     * Remove Subscription
     * 
     * @param int $classId
     * @return bool
     */
    public function removeSubscription(int $classId): bool
    {
        $this->items[static::SUBSCR] = [];
        return $this->refresh();
    }

    /**
     * Remove Group Class
     * 
     * @param int $classId
     * @return bool
     */
    public function removeClass(int $classId): bool
    {
        unset($this->items[static::GCLASS][$classId]);
        return $this->refresh();
    }

    /**
     * Remove Group Class
     * 
     * @param int $classId
     * @return bool
     */
    public function removePackage(int $classId): bool
    {
        unset($this->items[static::PACKGE][$classId]);
        return $this->refresh();
    }

    /**
     * Remove Course
     * 
     * @param int $courseId
     * @return bool
     */
    public function removeCourse(int $courseId): bool
    {
        unset($this->items[static::COURSE][$courseId]);
        return $this->refresh();
    }

    /**
     * Get Cart Items
     *
     * @return bool|array
     */
    public function getItems()
    {
        if ($this->getCount() < 1) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }
        return $this->items;
    }

    /**
     * get Coupon 
     *
     * @return array
     */
    public function getCoupon(): array
    {
        return $this->coupon;
    }

    /**
     * Get Discount
     *
     * @return float $discount
     */
    public function getDiscount(): float
    {
        return FatUtility::float($this->coupon['coupon_discount'] ?? '');
    }

    /**
     * Apply Coupon
     * 
     * @param string $code
     * @return bool
     */
    public function applyCoupon(string $code): bool
    {
        $coupon = new Coupon();
        $record = $coupon->validateCoupon($code, $this->getTotal(), $this->userId);
        if (empty($record)) {
            $this->error = $coupon->getError();
            return false;
        }
        $this->coupon = $record;
        return $this->refresh();
    }

    /**
     * Remove Coupon
     * 
     * @return bool
     */
    public function removeCoupon(): bool
    {
        $this->coupon = [];
        return $this->refresh();
    }

    /**
     * Apply Reward
     * 
     * @return bool
     */
    public function applyReward(int $status): bool
    {
        $rewards = new RewardPoint($this->userId);
        $requiredPoints = RewardPoint::convertToPoints($this->getNetAmount());
        if ($status == 1 && !$rewards->validate($requiredPoints)) {
            $this->error = $rewards->getError();
            return false;
        }
        $this->reward = $status;
        return $this->refresh();
    }

    /**
     * Get Reward
     * 
     * @return int
     */
    public function appliedReward(): int
    {
        $points = FatApp::getConfig('CONF_REWARD_POINT_MINIMUM_USE');
        if (User::getRewardBalance($this->userId) < $points) {
            $this->reward = AppConstant::NO;
            $this->refresh();
        }
        return $this->reward;
    }

    /**
     * Get Reward Discount
     *
     * @return float
     */
    public function getRewardDiscount($returnPoints = false): float
    {
        if (empty($this->reward)) {
            return 0.00;
        }
        $rewardPoints = User::getRewardBalance($this->userId);

        $cartNetAmount = ($this->getTotal() - $this->getDiscount());
        $amtRewards = RewardPoint::convertToPoints($cartNetAmount);
        $rewardPoints = ($amtRewards < $rewardPoints) ? $amtRewards : $rewardPoints;
        $rewardValue = RewardPoint::convertToValue($rewardPoints);
        if ($cartNetAmount <= $rewardValue) {
            return ($returnPoints) ? $amtRewards : $cartNetAmount;
        }
        return ($returnPoints) ? $rewardPoints : $rewardValue;
    }

    /**
     * Get Reward Points
     *
     * @return int
     */
    public function getRewardPoints(): int
    {
        if (empty($this->reward)) {
            return 0;
        }
        return $this->getRewardDiscount(true);
    }

    /**
     * Get Reward Points & Value
     *
     * @return array
     */
    public function getRewards(): array
    {
        return [$this->getRewardPoints(), $this->getRewardDiscount()];
    }

    /**
     * Clear Cart
     * 
     * @return bool
     */
    public function clear(): bool
    {
        $this->coupon = [];
        $this->reward = 0;
        $this->items = [
            static::LESSON => [],
            static::SUBSCR => [],
            static::GCLASS => [],
            static::PACKGE => [],
            static::COURSE => [],
            static::SUBSCRIPTIONPLAN => [],
        ];
        return $this->refresh();
    }

    /**
     * Refresh Cart
     * 
     * @return bool
     */
    private function refresh(): bool
    {
        $cartData = [
            'cart_user_id' => $this->userId,
            'cart_items' => json_encode($this->items),
            'cart_coupon' => json_encode($this->coupon),
            'cart_reward' => $this->reward,
            'cart_updated' => date('Y-m-d H:i:s')
        ];
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues($cartData);
        if (!$record->addNew([], $cartData)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Cart Data
     * 
     * @return bool|array
     */
    public function getData()
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->addCondition('cart_user_id', '=', $this->userId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_CART_IS_EMPTY');
            return false;
        }
        $row['cart_items'] = json_decode($row['cart_items'], true);
        $row['cart_coupon'] = json_decode($row['cart_coupon'], true);
        return $row;
    }

    /**
     * Item Count
     * 
     * @return int
     */
    public function getCount(): int
    {
        return array_sum([
            static::LESSON => count($this->items[static::LESSON]),
            static::SUBSCR => count($this->items[static::SUBSCR]['lessons'] ?? []),
            static::GCLASS => count($this->items[static::GCLASS]),
            static::PACKGE => count($this->items[static::PACKGE]),
            static::COURSE => count($this->items[static::COURSE]),
            static::SUBSCRIPTIONPLAN => count($this->items[static::SUBSCRIPTIONPLAN]),
        ]);
    }

    /**
     * Get Order Total
     * 
     * @return float
     */
    public function getTotal(): float
    {
        $amount = array_sum(array_column($this->items[static::LESSON], 'total_amount'));
        $amount += $this->items[static::SUBSCR]['total_amount'] ?? 0;
        $amount += array_sum(array_column($this->items[static::GCLASS], 'total_amount'));
        $amount += array_sum(array_column($this->items[static::PACKGE], 'total_amount'));
        $amount += array_sum(array_column($this->items[static::COURSE], 'total_amount'));
        $amount += array_sum(array_column($this->items[static::SUBSCRIPTIONPLAN], 'total_amount'));
        return FatUtility::float($amount);
    }

    /**
     * Get Order Net Amount
     * 
     * @return float
     */
    public function getNetAmount(): float
    {
        return FatUtility::float($this->getTotal() - $this->getDiscount() - $this->getRewardDiscount());
    }

    /**
     * Get Item Types
     * 
     * @return array
     */
    public static function getItemTypes(): array
    {
        return [
            static::LESSON => static::LESSON,
            static::SUBSCR => static::SUBSCR,
            static::GCLASS => static::GCLASS,
            static::PACKGE => static::PACKGE,
            static::COURSE => static::COURSE,
        ];
    }

    /**
     * Get Lesson Form
     * 
     * @param int $quantity
     * @return Form
     */
    public function getLessonForm(int $quantity): Form
    {
        $frm = new Form('lessonForm');
        $frm = CommonHelper::setFormProperties($frm);
        /* ordles_teacher_id */
        $teacherId = $frm->addHiddenField(Label::getLabel('LBL_TEACHER_ID'), 'ordles_teacher_id');
        $teacherId->requirements()->setRequired(true);
        $teacherId->requirements()->setIntPositive();
        /* ordles_tlang_id */
        $teachLang = $frm->addHiddenField(Label::getLabel('LBL_LANGUAGE_ID'), 'ordles_tlang_id');
        /* ordles_duration */
        $teachSlot = $frm->addHiddenField(Label::getLabel('LBL_LESSON_DURATION'), 'ordles_duration');
        $teachSlot->requirements()->setRequired(true);
        $teachSlot->requirements()->setIntPositive();
        /* ordles_type */
        $lessonType = $frm->addSelectBox(Label::getLabel('LBL_LESSON_TYPE'), 'ordles_type', Lesson::getLessonTypes(), '', [], Label::getLabel('LBL_SELECT'));
        $lessonType->requirements()->setRequired(true);
        $lessonType->requirements()->setIntPositive();
        /* set teach Lang required */
        /* order_item_count */
        $lessonQty = $frm->addHiddenField(Label::getLabel('LBL_LESSON_QUANTITY'), 'ordles_quantity', 1);
        $lessonQty->requirements()->setRange(1, 999999999999);
        $lessonType->requirements()->setIntPositive();
        $lessonQty->requirements()->setRequired(true);
        for ($i = 0; $i < $quantity; $i++) {
            /* ordles_starttime */
            $starttime = $frm->addHiddenField(Label::getLabel('LBL_START_TIME'), 'startTime[' . $i . ']');
            $starttime->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
            /* ordles_endtime */
            $endtime = $frm->addHiddenField(Label::getLabel('LBL_END_TIME'), 'endTime[' . $i . ']');
            $endtime->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
            /* set start time not required */
            $endtime->requirements()->addOnChangerequirementUpdate('', 'eq', 'startTime[' . $i . ']', $starttime->requirements());
            /* set start time required */
            $requirement = new FormFieldRequirement('startTime[' . $i . ']', Label::getLabel('LBL_START_TIME'));
            $requirement->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
            $requirement->setRequired(true);
            $endtime->requirements()->addOnChangerequirementUpdate('', 'ne', 'startTime[' . $i . ']', $requirement);
            /* set end time not required */
            $starttime->requirements()->addOnChangerequirementUpdate('', 'eq', 'endTime[' . $i . ']', $endtime->requirements());
            /* set end time required */
            $requirement = new FormFieldRequirement('endTime[' . $i . ']', Label::getLabel('LBL_END_TIME'));
            $requirement->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
            $requirement->setRequired(true);
            $starttime->requirements()->addOnChangerequirementUpdate('', 'ne', 'endTime[' . $i . ']', $requirement);
            /* ordsub_recurring */
        }
        $frm->addHiddenField('', 'ordles_offline');
        $frm->addHiddenField('', 'ordles_address_id');
        /* ordles_starttime */
        $starttime = $frm->addHiddenField(Label::getLabel('LBL_START_TIME'), 'ordles_starttime');
        $starttime->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
        /* ordles_endtime */
        $endtime = $frm->addHiddenField(Label::getLabel('LBL_END_TIME'), 'ordles_endtime');
        $endtime->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
        return $frm;
    }

    /**
     * Get subscription Form
     * 
     * @param array $quantity
     * @return Form
     */
    public function getSubscriptionForm(int $quantity): Form
    {
        $frm = new Form('subcripForm');
        $frm = CommonHelper::setFormProperties($frm);
        /* ordles_teacher_id */
        $teacherId = $frm->addHiddenField(Label::getLabel('LBL_TEACHER_ID'), 'ordles_teacher_id');
        $teacherId->requirements()->setRequired(true);
        $teacherId->requirements()->setIntPositive();
        /* ordles_tlang_id */
        $teachLang = $frm->addHiddenField(Label::getLabel('LBL_LANGUAGE_ID'), 'ordles_tlang_id');
        $teachLang->requirements()->setRequired(true);
        $teachLang->requirements()->setIntPositive();
        /* ordles_duration */
        $duration = $frm->addHiddenField(Label::getLabel('LBL_LESSON_DURATION'), 'ordles_duration');
        $duration->requirements()->setRequired(true);
        $duration->requirements()->setIntPositive();
        /* set teach Lang required */
        /* order_item_count */
        $lessonQty = $frm->addHiddenField(Label::getLabel('LBL_LESSON_QUANTITY'), 'ordles_quantity', 1);
        $lessonQty->requirements()->setRange(1, 999999999999);
        $lessonQty->requirements()->setRequired(true);
        $days = (FatApp::getConfig('CONF_RECURRING_SUBSCRIPTION_WEEKS') * 7);
        $dateRange = MyDate::getSubscriptionDates($days);
        $dateRange['ordsub_startdate'] = MyDate::convert($dateRange['ordsub_startdate'], MyUtility::getSiteTimezone());
        $dateRange['ordsub_enddate'] = MyDate::convert($dateRange['ordsub_enddate'], MyUtility::getSiteTimezone());
        for ($i = 0; $i < $quantity; $i++) {
            $starttime = $frm->addRequiredField(Label::getLabel('LBL_START_TIME'), 'startTime[' . $i . ']');
            $starttime->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
            $starttime->requirements()->setRange($dateRange['ordsub_startdate'], $dateRange['ordsub_enddate']);
            $starttime->requirements()->setCustomErrorMessage(str_replace(['{caption}', '{minval}', '{maxval}'], [Label::getLabel('LBL_START_TIME'), MyDate::showDate($dateRange['ordsub_startdate'], true), MyDate::showDate($dateRange['ordsub_enddate'], true)], Label::getLabel('VLBL_VALUE_OF_{caption}_MUST_BE_BETWEEN_{minval}_AND_{maxval}.')));
            $endtime = $frm->addRequiredField(Label::getLabel('LBL_END_TIME'), 'endTime[' . $i . ']');
            $endtime->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
            $endtime->requirements()->setRange($dateRange['ordsub_startdate'], $dateRange['ordsub_enddate']);
            $endtime->requirements()->setCustomErrorMessage(str_replace(['{caption}', '{minval}', '{maxval}'], [Label::getLabel('LBL_END_TIME'), MyDate::showDate($dateRange['ordsub_startdate'], true), MyDate::showDate($dateRange['ordsub_enddate'], true)], Label::getLabel('VLBL_VALUE_OF_{caption}_MUST_BE_BETWEEN_{minval}_AND_{maxval}.')));
        }
        $frm->addHiddenField('', 'ordles_offline');
        $frm->addHiddenField('', 'ordles_address_id');
        return $frm;
    }

    /**
     * Fetch and Return Payment Methods
     * @return array
     */
    public function getPaymentMethodsArr(): array
    {
        return $this->getPaymentMethods();
    }

    /**
     * Allow Bank Transfer Payment
     * 
     * @return bool
     */
    private function allowBankTransferPayment(): bool
    {
        $starttimes = [];
        foreach ($this->items[static::LESSON] as $lesson) {
            foreach ($lesson['lessons'] as $schedule) {
                array_push($starttimes, $schedule['ordles_starttime']);
            }
        }
        foreach ($this->items[static::SUBSCR]['lessons'] ?? [] as $schedule) {
            array_push($starttimes, $schedule['ordles_starttime']);
        }
        foreach ($this->items[static::GCLASS] as $class) {
            array_push($starttimes, $class['grpcls_start_datetime']);
        }
        foreach ($this->items[static::PACKGE] as $package) {
            foreach ($package['classes'] as $class) {
                array_push($starttimes, MyDate::formatToSystemTimezone($class['grpcls_start_datetime']));
            }
        }
        $starttimes = array_filter($starttimes);
        if (empty($starttimes)) {
            return true;
        }
        $hours = (new BankTransferPay([]))->getBookBeforeHours();
        return (date('Y-m-d H:i:s', strtotime('+' . $hours . ' hours')) <= min($starttimes));
    }

    /**
     * Get Payment Methods
     * 
     * @return array
     */
    private function getPaymentMethods(): array
    {
        $srch = new SearchBase(PaymentMethod::DB_TBL, 'pmethod');
        $srch->addMultipleFields(['pmethod_id', 'pmethod_code']);
        $srch->addCondition('pmethod_active', '=', AppConstant::YES);
        $srch->addCondition('pmethod_type', '=', PaymentMethod::TYPE_PAYIN);
        $balance = User::getWalletBalance($this->userId);
        if ($balance <= 0 || $balance < $this->getNetAmount()) {
            $srch->addCondition('pmethod_code', '!=', WalletPay::KEY);
        }
        if (!$this->allowBankTransferPayment()) {
            $srch->addCondition('pmethod_code', '!=', BankTransferPay::KEY);
        }
        $srch->addOrder('pmethod_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $arr = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        foreach ($arr as $key => $value) {
            $arr[$key] = Label::getLabel('LBL_' . $value);
        }
        return $arr;
    }

    /**
     * Get Checkout Form
     * 
     * @param array $methods
     * @return Form
     */
    public function getCheckoutForm(array $methods = null): Form
    {
        $payins = is_null($methods) ? $this->getPaymentMethods() : $methods;
        $frm = new Form('checkoutForm', ['id' => 'checkoutForm']);
        $frm = CommonHelper::setFormProperties($frm);
        $orderType = $frm->addSelectBox(Label::getLabel('LBL_ORDER_TYPE'), 'order_type', Order::getTypeArr(), '', [], Label::getLabel('LBL_SELECT'));
        $orderType->requirements()->setRequired(true);
        $orderType->requirements()->setIntPositive();
        $selected = $this->getNetAmount() > 0 ? array_key_first($payins) : '';
        $pmethod = $frm->addSelectBox(Label::getLabel('LBL_PAYMENT_METHOD'), 'order_pmethod_id', $payins, $selected, [], Label::getLabel('LBL_SELECT'));
        if ($this->getNetAmount() > 0) {
            $pmethod->requirements()->setRequired(true);
            $pmethod->requirements()->setIntPositive();
        }
        $frm->addTextBox(Label::getLabel('LBL_COUPON_CODE'), 'coupon_code');
        $fld = $frm->addTextBox(Label::getLabel('LBL_ADD_&_PAY'), 'add_and_pay', '');
        $fld->requirements()->setIntPositive();
        $frm->addCheckBox('', 'apply_reward', AppConstant::YES, [], false, 0);
        $frm->addHiddenField('', 'ordles_type');
        $frm->addButton(Label::getLabel('LBL_REVIEW_ORDER_DETAILS'), 'submit', Label::getLabel('LBL_REVIEW_ORDER_DETAILS'));
        return $frm;
    }

    /**
     * Get Checkout Steps
     * 
     * @return type
     */
    public static function getSteps($steps = 4)
    {
        $stepsArr = [];
        for ($i=1; $i <= $steps; $i++) { 
            $stepsArr[$i] = Label::getLabel('LBL_'.$i);
        }
        return $stepsArr;
    }
}
