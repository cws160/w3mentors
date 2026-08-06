<?php

/**
 * Cart Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class CartController extends LoggedUserController
{

    /**
     * Initialize Cart
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType == USER::AFFILIATE) {
            MyUtility::dieJsonError(Label::getLabel('LBL_PLEASE_LOGIN_AS_LEARNER'));
        }
        $this->setUserSubscription();
    }

    /**
     * Language and Duration Slots
     */
    public function langSlots()
    {
        $teacherId = FatApp::getPostedData('ordles_teacher_id', FatUtility::VAR_INT, 0);
        $duration = FatApp::getPostedData('ordles_duration', FatUtility::VAR_INT, 0);
        $tlangId = FatApp::getPostedData('ordles_tlang_id', FatUtility::VAR_INT, 0);

        $quantity = FatApp::getPostedData('ordles_quantity', FatUtility::VAR_INT, 0);
        $ordlesType = FatApp::getPostedData('ordles_type', FatUtility::VAR_INT, 0);
        $ordlesType = ($ordlesType < 1) ? Lesson::TYPE_REGULAR : $ordlesType;
        $ordlesOffline = FatApp::getPostedData('ordles_offline', FatUtility::VAR_INT, 0);
        $address = UserAddresses::getDefault($teacherId, $this->siteLangId);
        if ($teacherId < 1 || $teacherId == $this->siteUserId) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $user = new User($teacherId);
        if (!$teacher = $user->validateTeacher($this->siteLangId, $this->siteUserId)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!empty($this->activePlan)) {
            if (!($this->activePlan['ordsplan_lessons'] - $this->activePlan['ordsplan_used_lesson_count'])) {
                MyUtility::dieJsonError(Label::getLabel('LBL_NO_LESSON_LEFT_IN_SUBSCRIPTION'));
            }
        }
        $utl = new UserTeachLanguage($teacherId);
        $langslots = $utl->getLangSlots($this->siteLangId);
        $tlangs = array_keys($langslots);
        $tlangId = (!in_array($tlangId, $tlangs)) ? current($tlangs) : $tlangId;
        $slots = $langslots[$tlangId]['slots'] ?? [];
        if (!empty($this->activePlan)) {
            $duration = (!in_array($this->activePlan['ordsplan_duration'], $slots)) ? 0 : $this->activePlan['ordsplan_duration'];
            if (!$duration) {
                MyUtility::dieJsonError(Label::getLabel('LBL_SLOT_NOT_AVAILABLE'));
            }
        } else {
            $duration = (!in_array($duration, $slots)) ? current($slots) : $duration;
        }
        $this->sets([
            'teacher' => $teacher, 'langslots' => $langslots,
            'tlangId' => $tlangId, 'duration' => $duration,
            'quantity' => $quantity, 'ordlesType' => $ordlesType,
            'ordlesOffline' => $ordlesOffline,
            'postedData' => FatApp::getPostedData(),
            'stepCompleted' => [], 'stepProcessing' => [1],
            'activePlan' => $this->activePlan,
            'subWeek' => FatApp::getConfig('CONF_RECURRING_SUBSCRIPTION_WEEKS'),
            'address' => $address,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Lesson Price and lesson Quantity 
     */
    public function priceSlabs()
    {
        $teacherId = FatApp::getPostedData('ordles_teacher_id', FatUtility::VAR_INT, 0);
        $tlangId = FatApp::getPostedData('ordles_tlang_id', FatUtility::VAR_INT, 0);
        $duration = FatApp::getPostedData('ordles_duration', FatUtility::VAR_INT, 0);
        $quantity = FatApp::getPostedData('ordles_quantity', FatUtility::VAR_INT, 0);
        $ordlesType = FatApp::getPostedData('ordles_type', FatUtility::VAR_INT, 0);
        $ordlesType = ($ordlesType < 1) ? Lesson::TYPE_REGULAR : $ordlesType;
        if ($teacherId < 1 || $tlangId < 1 || $duration < 1 || $teacherId == $this->siteUserId) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!empty($this->activePlan) && $duration != $this->activePlan['ordsplan_duration']) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_DURATION_FOR_SLOT'));
        }
        if (!empty($this->activePlan)) {
            if (!($this->activePlan['ordsplan_lessons'] - $this->activePlan['ordsplan_used_lesson_count'])) {
                MyUtility::dieJsonError(Label::getLabel('LBL_NO_LESSON_LEFT_IN_SUBSCRIPTION'));
            }
        }
        if (!$userLangData = (new UserTeachLanguage($teacherId))->getById($tlangId, $this->siteLangId)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_TEACHER_DOES_NOT_HAVE_PRICE'));
        }
        $quantity = empty($quantity) ? 1 : $quantity;
        $discount = 0;
        $offer = OfferPrice::getLessonOffer($this->siteUserId, $teacherId);
        if (!empty($offer['offpri_lesson_price'])) {
            $offers = json_decode($offer['offpri_lesson_price'], 1);
            $offers = array_column($offers, 'offer', 'duration');
            $discount = FatUtility::float(($offers[$duration] ?? 0));
        }

        $price = MyUtility::slotPrice($userLangData['utlang_price'], $duration);
        if($discount > 0) {
            $price = FatUtility::float($price - ($discount * $price) / 100);
        }
        $data = [
            'price' => MyUtility::formatMoney($price * $quantity),
        ];
        MyUtility::dieJsonSuccess($data);
    }

    /**
     * Render Booking Calendar for selecting slots
     */
    public function viewCalendar()
    {
        $teacherId = FatApp::getPostedData('ordles_teacher_id', FatUtility::VAR_INT, 0);
        $tlangId = FatApp::getPostedData('ordles_tlang_id', FatUtility::VAR_INT, 0);
        $duration = FatApp::getPostedData('ordles_duration', FatUtility::VAR_INT, 0);
        $quantity = FatApp::getPostedData('ordles_quantity', FatUtility::VAR_INT, 0);
        $ordlesType = FatApp::getPostedData('ordles_type', FatUtility::VAR_INT, 0);
        $subPlan = 0;
        $subEndDate = '';
        if (
            $teacherId < 1 || $tlangId < 1 || $duration < 1 || $quantity < 1 ||
            $ordlesType < 1 || $teacherId == $this->siteUserId
        ) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $user = new User($teacherId);
        if (!$user->validateTeacher($this->siteLangId, $this->siteUserId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($ordlesType == Lesson::TYPE_SUBCRIP) {
            $startEndDate = MyDate::getSubscriptionDates(FatApp::getConfig('CONF_RECURRING_SUBSCRIPTION_WEEKS') * 7);
            $subEndDate = $startEndDate['ordsub_enddate'];
        }
        if (!empty($this->activePlan) && $ordlesType = Lesson::TYPE_REGULAR) {
            $lessonDiff = $this->activePlan['ordsplan_lessons'] - $this->activePlan['ordsplan_used_lesson_count'];
            if (!$lessonDiff) {
                MyUtility::dieJsonError(Label::getLabel('LBL_NO_LESSON_LEFT_IN_SUBSCRIPTION'));
            }
            if ($lessonDiff < $quantity) {
                $lbl = str_replace(['{lessons}'], [$lessonDiff], Label::getLabel('LBL_ONLY_{lessons}_LESSON(S)_LEFT_IN_SUBSCRIPTION'));
                MyUtility::dieJsonError($lbl);
            }
            $subEndDate = $this->activePlan['ordsplan_end_date'];
            $subPlan = 1;
        }
        if ($this->activePlan) {
            $cart = new Cart($this->siteUserId, $this->siteLangId);
            $form = $cart->getCheckoutForm([0 => Label::getLabel('LBL_NA')]);
            $form->fill(['order_type' => Order::TYPE_LESSON]);
            $this->set('form', $form);
        }

        $this->sets([
            'minDateToShow' => MyDate::formatDate(date('Y-m-d')),
            'quantity' => $quantity,
            'teacherId' => $teacherId,
            'duration' => $duration,
            'ordlesType' => $ordlesType,
            'activePlan' => $this->activePlan,
            'subPlan' => $subPlan,
            'subEndDate' => MyDate::formatDate($subEndDate) ?? '',
            'calendarType' => AppConstant::AVAIL_VIEW_BOOKING
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Add Lesson(s) to Cart
     */
    public function addLesson()
    {
        $quantity = FatApp::getPostedData('ordles_quantity', FatUtility::VAR_INT, 0);
        $post = FatApp::getPostedData();
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        $frm = $cart->getLessonForm($quantity);
        if (!$post = $frm->getFormDataFromArray($post)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if ($post['ordles_teacher_id'] == $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_LESSON(S)'));
        }
        if (!empty($post['ordles_starttime']) && !empty($post['ordles_endtime'])) {
            $post['ordles_starttime'] = MyDate::formatToSystemTimezone($post['ordles_starttime']);
            $post['ordles_endtime'] = MyDate::formatToSystemTimezone($post['ordles_endtime']);
        }
        if ($post['ordles_type'] == Lesson::TYPE_REGULAR) {
            $post['lessons'] = $this->formatLessonData($post);
            unset($post['startTime'], $post['endTime']);
        }
        if (
            $post['ordles_type'] == Lesson::TYPE_FTRAIL &&
            Lesson::isTrailAvailed($this->siteUserId, $post['ordles_teacher_id'])
        ) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_ALLREADY_AVAILED_FREE_TRIAL_LESSON'));
        }
        if (isset($post['ordles_offline']) && $post['ordles_offline'] == AppConstant::YES && !User::offlineSessionsEnabled($post['ordles_teacher_id'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_OFFLINE_LESSONS_NOT_AVAILABLE'));
        }
        if (!empty($this->activePlan) && $post['ordles_type'] != Lesson::TYPE_FTRAIL) {
            $lessonDiff = $this->activePlan['ordsplan_lessons'] - $this->activePlan['ordsplan_used_lesson_count'];
            if (!$lessonDiff) {
                MyUtility::dieJsonError(Label::getLabel('LBL_NO_LESSON_LEFT_IN_SUBSCRIPTION'));
            }
            if ($lessonDiff < $quantity) {
                $lbl = str_replace(['{lessons}'], [$lessonDiff], Label::getLabel('LBL_ONLY_{lessons}_LESSON(S)_LEFT_IN_SUBSCRIPTION'));
                MyUtility::dieJsonError($lbl);
            }
            $post['ordles_ordsplan_id'] = $this->activePlan['ordsplan_id'];
            $cart->applyReward(0);
        }
        if (!$cart->addLesson($post)) {
            MyUtility::dieJsonError($cart->getError());
        }
        if ($post['ordles_type'] == Lesson::TYPE_FTRAIL || API_CALL || !empty($this->activePlan)) {
            MyUtility::dieJsonSuccess(Label::getLabel('LBL_ITEM_ADDED_SUCCESSFULLY'));
        }
        $this->set('post', $post);
        $this->paymentSummary(Order::TYPE_LESSON);
    }

    /**
     * Add Subscription to cart
     */
    public function addSubscription()
    {
        $quantity = FatApp::getPostedData('ordles_quantity', FatUtility::VAR_INT, 0);
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        $frm = $cart->getSubscriptionForm($quantity);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (isset($post['ordles_offline']) && $post['ordles_offline'] == AppConstant::YES && !User::offlineSessionsEnabled($post['ordles_teacher_id'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_OFFLINE_LESSONS_NOT_AVAILABLE'));
        }
        $post['lessons'] = $this->formatLessonData($post);
        unset($post['startTime'], $post['endTime']);
        if (!$cart->addSubscription($post)) {
            MyUtility::dieJsonError($cart->getError());
        }
        if (API_CALL) {
            MyUtility::dieJsonSuccess(Label::getLabel('LBL_ITEM_ADDED_SUCCESSFULLY'));
        }
        $this->set('post', $post);
        $this->paymentSummary(Order::TYPE_SUBSCR);
    }

    /**
     * Add Class to cart
     */
    public function addClass()
    {
        $grpclsId = FatApp::getPostedData('grpcls_id', FatUtility::VAR_INT, 0);
        if (empty($grpclsId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cart->addClass($grpclsId)) {
            FatUtility::dieJsonError($cart->getError());
        }
        $this->set('post', ['grpcls_id' => $grpclsId]);
        $this->paymentSummary(Order::TYPE_GCLASS);
    }

    /**
     * Add Package to cart
     */
    public function addPackage()
    {
        $packageId = FatApp::getPostedData('packageId', FatUtility::VAR_INT, 0);
        if (empty($packageId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cart->addPackage($packageId)) {
            FatUtility::dieJsonError($cart->getError());
        }
        $this->set('post', ['package_id' => $packageId]);
        $this->paymentSummary(Order::TYPE_PACKGE);
    }

    /**
     * Add Course to cart
     */
    public function addCourse()
    {
        if (!Course::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        $isFree = FatApp::getPostedData('is_free', FatUtility::VAR_INT, 0);
        $courseId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        if (empty($courseId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* check course already booked */
        $course = CourseSearch::getPurchasedCourses($this->siteUserId, [$courseId]);
        if (!empty($course)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_HAVE_ALREADY_PURCHASED_THIS_COURSE'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cart->addCourse($courseId)) {
            FatUtility::dieJsonError($cart->getError());
        }
        $cart = $cart->getItems();
        if ($cart[Cart::COURSE][$courseId]);
        if ($isFree == AppConstant::YES && $cart[Cart::COURSE][$courseId]['course_price'] > 0) {
            FatUtility::dieJsonError(['msg' => '', 'status' => 2]);
        } elseif ($isFree == AppConstant::NO && $cart[Cart::COURSE][$courseId]['course_price'] < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_THIS_COURSE_IS_NOW_AVAILABLE_FREE_OF_COST'));
        }
        $this->set('post', ['course_id' => $courseId]);
        $this->paymentSummary(Order::TYPE_COURSE);
    }

    /**
     * Add Course to cart
     */
    public function addSubscriptionPlan()
    {
        if (!SubscriptionPlan::isEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_MODULE_NOT_AVAILABLE'));
        }
        if (!empty($this->activePlan)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_CANCEL_EXISTING_SUBSCRIPTION_PLAN'));
        }
        $planId = FatApp::getPostedData('planId', FatUtility::VAR_INT, 0);
        if (empty($planId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cart->addSubscriptionPlan($planId)) {
            FatUtility::dieJsonError($cart->getError());
        }
        $this->set('post', ['plan_id' => $planId]);
        $this->paymentSummary(Order::TYPE_SUBPLAN);
    }

    /**
     * Apply Coupon
     */
    public function applyCoupon()
    {
        $code = FatApp::getPostedData('coupon_code', FatUtility::VAR_STRING, '');
        $orderType = FatApp::getPostedData('order_type', FatUtility::VAR_INT, 0);
        if (empty($code) || empty($orderType)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cart->applyCoupon($code)) {
            MyUtility::dieJsonError($cart->getError());
        }
        $this->paymentSummary($orderType);
    }

    /**
     * Remove Coupon
     */
    public function removeCoupon()
    {
        $orderType = FatApp::getPostedData('order_type', FatUtility::VAR_INT, 0);
        if (empty($orderType)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cart->removeCoupon()) {
            FatUtility::dieJsonError(Label::getLabel("LBL_INVALID_ACTION"));
        }
        $this->paymentSummary($orderType);
    }

    /**
     * Apply Reward Points
     */
    public function applyRewards()
    {
        $orderType = FatApp::getPostedData('order_type', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('apply_reward', FatUtility::VAR_INT, 0);
        if (empty($orderType)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cart->applyReward($status)) {
            MyUtility::dieJsonError($cart->getError());
        }
        $this->paymentSummary($orderType);
    }

    /**
     * Render Payment Summary
     * 
     * @param $orderType
     */
    public function paymentSummary($orderType, $isPrevStep = false)
    {
        if ($orderType == Order::TYPE_COURSE && !Course::isEnabled()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        $addAndPay = FatApp::getPostedData('add_and_pay', FatUtility::VAR_INT, 0);
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (1 > $cart->getCount()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_CART_IS_EMPTY'));
        }
        $couponCode = $cart->getCoupon()['coupon_code'] ?? '';
        $checkoutFormData = [
            'order_type' => $orderType,
            'coupon_code' => $couponCode,
            'apply_reward' => $cart->appliedReward()
        ];
        $pmethodId = FatApp::getPostedData('order_pmethod_id', FatUtility::VAR_STRING, '');
        if (!empty($pmethodId) && $cart->getNetAmount() > 0) {
            $checkoutFormData['order_pmethod_id'] = $pmethodId;
        }
        $checkoutForm = $cart->getCheckoutForm();
        if(!$isPrevStep) {
            $checkoutForm->fill($checkoutFormData);
        } else {
            $checkoutForm->fill(FatApp::getPostedData());
        }
        $checkoutForm->fill($checkoutFormData);
        $coupon = new Coupon(0, $this->siteLangId);
        $this->sets([
            'orderTypeUsed' => $orderType,
            'addAndPay' => $addAndPay,
            'checkoutForm' => $checkoutForm,
            'cartTotal' => $cart->getTotal(),
            'cartDiscount' => $cart->getDiscount(),
            'cartNetAmount' => $cart->getNetAmount(),
            'appliedCoupon' => $cart->getCoupon(),
            'appliedReward' => $cart->appliedReward(),
            'rewardDiscount' => $cart->getRewardDiscount(),
            'availableCoupons' => $coupon->getCouponList(),
            'currencyData' => MyUtility::getSystemCurrency(),
            'walletBalance' => User::getWalletBalance($this->siteUserId),
            'rewardBalance' => User::getRewardBalance($this->siteUserId),
            'walletPayId' => PaymentMethod::getByCode(WalletPay::KEY)['pmethod_id'],
            'activePlan' => $this->activePlan,
        ]);
        $this->_template->render(false, false, 'cart/payment-summary.php');
    }

    /**
     * Confirm Order to place
     */
    public function confirmOrder()
    {
        $orderType = FatApp::getPostedData('order_type', FatUtility::VAR_INT, 0);
        if ($orderType == Order::TYPE_COURSE && !Course::isEnabled()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
        }
        if ($orderType == Order::TYPE_SUBPLAN && !SubscriptionPlan::isEnabled()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_MODULE_NOT_AVAILABLE'));
        }
        $ordlesType = FatApp::getPostedData('ordles_type', FatUtility::VAR_INT, 0);
        if (!empty($this->activePlan) && Order::TYPE_LESSON == $orderType && $ordlesType != Lesson::TYPE_FTRAIL) {
            if (!($this->activePlan['ordsplan_lessons'] - $this->activePlan['ordsplan_used_lesson_count'])) {
                MyUtility::dieJsonError(Label::getLabel('LBL_NO_LESSON_LEFT_IN_SUBSCRIPTION'));
            }
        }
        if ($orderType == Order::TYPE_SUBPLAN && !empty($this->activePlan)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_CANCEL_EXISTING_SUBSCRIPTION_PLAN'));
        }
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if ($cart->getCount() < 1) {
            MyUtility::dieJsonError(Label::getLabel('LBL_CART_IS_EMPTY'));
        }
        $frm = $cart->getCheckoutForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $order = new Order(0, $this->siteUserId);
        if (!$order->addItems($post['order_type'], $cart->getItems())) {
            MyUtility::dieJsonError($order->getError());
        }
        $rewards = $cart->getRewards();
        if (!$order->applyRewards(...$rewards)) {
            MyUtility::dieJsonError($order->getError());
        }
        if (!$order->applyCoupon($cart->getCoupon())) {
            MyUtility::dieJsonError($order->getError());
        }
        $pmethodId = FatApp::getPostedData('order_pmethod_id', FatUtility::VAR_INT, 0);
        $pmethod = PaymentMethod::getAttributesById($pmethodId);
        if ($cart->getNetAmount() > 0 && (empty($pmethod) || empty($pmethod['pmethod_active']))) {
            MyUtility::dieJsonError(Label::getLabel('LBL_PAYMENT_METHOD_NOT_AVAILABLE'));
        }
        if (!$order->placeOrder($post['order_type'], $pmethodId, $post['add_and_pay'])) {
            MyUtility::dieJsonError($order->getError());
        }
        $orderId = $order->getMainTableRecordId();
        $rootUrl = (API_CALL) ? CONF_WEBROOT_FRONTEND . 'api/' : CONF_WEBROOT_FRONTEND;
        $redirectUrl = MyUtility::makeFullUrl('Payment', 'charge', [$orderId], $rootUrl);
        if (Order::getAttributesById($orderId, 'order_net_amount') == 0) {
            $payment = new OrderPayment($orderId);
            if (!$payment->paymentSettlements('NA', 0, [])) {
                MyUtility::dieJsonError($payment->getError());
            }
            $redirectUrl = MyUtility::makeFullUrl('Payment', 'success', [$orderId], $rootUrl);
        }
        $viewOrderId = $orderId;
        $relatedOrderId = Order::getAttributesById($orderId, 'order_related_order_id');
        if (!empty($relatedOrderId)) {
            $viewOrderId = FatUtility::int($relatedOrderId);
        }
        MyUtility::dieJsonSuccess([
            'order_id' => $orderId,
            'redirectUrl' => $redirectUrl,
            'view_order_id' => $viewOrderId,
            'msg' => Label::getLabel('MSG_PLEASE_WAIT_FOR_A_WHILE'),
        ]);
    }

    /**
     * Render Trail Calendar
     */
    public function trailCalendar()
    {
        $teacherId = FatApp::getPostedData('teacherId', FatUtility::VAR_INT, 0);
        Meeting::zoomVerificationCheck($teacherId);
        if (FatApp::getConfig('CONF_ENABLE_FREE_TRIAL', FatUtility::VAR_INT, 0) != 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $user = new User($teacherId);
        if (!$teacher = $user->validateTeacher($this->siteLangId, $this->siteUserId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($teacher['user_trial_enabled'] == AppConstant::NO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_FREE_TRIAL_IS_DISABLED_BY_TEACHER'));
        }
        if (Lesson::isTrailAvailed($this->siteUserId, $teacher['user_id'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_ALLREADY_AVAILED_FREE_TRIAL_LESSON'));
        }
        $teacher['user_country_code'] = Country::getAttributesById($teacher['user_country_id'], 'country_code');
        $duration = FatApp::getConfig('CONF_TRIAL_LESSON_DURATION');
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        $form = $cart->getCheckoutForm([0 => Label::getLabel('LBL_NA')]);
        $form->fill(['order_type' => Order::TYPE_LESSON]);
        $this->set('form', $form);
        $this->sets([
            'teacherId' => $teacherId,
            'teacher' => $teacher, 'duration' => $duration,
            'minDateToShow' => MyDate::formatDate(date('Y-m-d')),
            'quantity' => 1, 'ordlesType' => Lesson::TYPE_FTRAIL,
            'calendarType' => AppConstant::AVAIL_VIEW_BOOKING
        ]);
        $this->_template->render(false, false, 'cart/view-calendar.php');
    }

    /**
     * Review Order Details
     * @return void
     */
    public function reviewOrder()
    {
        $pmethodId = FatApp::getPostedData('order_pmethod_id', FatUtility::VAR_INT, 0);
        $addAndPay = FatApp::getPostedData('add_and_pay', FatUtility::VAR_INT, 0);
        $paymentMethod = Label::getLabel('LBL_N/A');
        $teacherId = 0;
        $teacher = [];
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        if (!$cartItems = $cart->getItems()) {
            MyUtility::dieJsonError($cart->getError());
        }
        /* Check Payment Method can be used or not [ */
        $pmethod = PaymentMethod::getAttributesById($pmethodId);
        if ($cart->getNetAmount() > 0 && (empty($pmethod) || empty($pmethod['pmethod_active']))) {
            MyUtility::dieJsonError(Label::getLabel('LBL_PAYMENT_METHOD_NOT_AVAILABLE'));
        }
        $walletBalance = User::getWalletBalance($this->siteUserId);
        if(!empty($pmethod) && $pmethod['pmethod_code'] == 'WalletPay' && $walletBalance < $cart->getNetAmount()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_WALLET_BALANCE_NOT_SUFFICIENT'));
        }
        /* ] */
        $cartType = '';
        $address = '';
        foreach ($cartItems as $key => $item) {
            if (!empty($item)) {
                switch($key) {
                    case "LESSON":
                        $item = current($item);
                        $teacherId = $item['ordles_teacher_id'];
                        $address = $item['ordles_address'];
                        $cartType = $key;
                        break;
                    case "SUBSCR":
                        $teacherId = $item['ordles_teacher_id'];
                        $address = $item['ordles_address'];
                        $cartType = $key;
                        break;
                    case "GCLASS":
                    case "PACKGE":
                        $item = current($item);
                        $teacherId = $item['grpcls_teacher_id'];
                        $address = $item['ordcls_address'];
                        $cartType = $key;
                        break;
                    case "COURSE":
                        $teacherId = current($item)['course_teacher_id'];
                        $cartType = $key;
                        break;
                    case "SUBSCRIPTIONPLAN":
                        $teacherId = 0;
                        break;
                }
            }
        }
        if($teacherId > 0) {
            if(!in_array($cartType, ['COURSE', 'GCLASS', 'PACKGE'])) {
                $user = new User($teacherId);
                if (!$teacher = $user->validateTeacher($this->siteLangId, $this->siteUserId)) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                }
            } else {
                if(!$teacher = User::getDetail($teacherId)) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                }
                if($teacher['user_active'] == AppConstant::INACTIVE || $teacher['user_is_teacher'] == AppConstant::NO || $teacher['user_deleted'] != null || $teacher['user_verified'] == null ) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
                }
            }
        }
        $paymentMethodsArr = $cart->getPaymentMethodsArr();
        if($pmethodId > 0) {
            $paymentMethod = $paymentMethodsArr[$pmethodId];
        }
        if($addAndPay == AppConstant::YES) {
            $this->sets([
                'walletBalance' => $walletBalance,
                'addAndPay' => $addAndPay
            ]);
        }
        $this->sets([
            'totalAmount' => $cart->getNetAmount(),
            'paymentMethod' => $paymentMethod,
            'cartItems' => $cartItems,
            'teacher' => $teacher,
            'address' => $address,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Format Lessons Data
     * 
     * @param array $data
     * @return array
     */
    private function formatLessonData(array $data): array
    {
        $lessonData = [];
        foreach ($data['startTime'] as $key => $value) {
            $lesson = ['ordles_starttime' => null, 'ordles_endtime' => null];
            if (!empty($value) && !empty($data['endTime'][$key])) {
                $lesson['ordles_starttime'] = MyDate::formatToSystemTimezone($value);
                $lesson['ordles_endtime'] = MyDate::formatToSystemTimezone($data['endTime'][$key]);
            }
            array_push($lessonData, $lesson);
        }
        return $lessonData;
    }
}
