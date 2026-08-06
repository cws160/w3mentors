<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header modal-header--checkout">
    <button class="btn-back" onclick="cart.paymentSummary();"></button>
    <h4 class="flex-1 text-center"><?php echo Label::getLabel('LBL_Booking_Summary'); ?></h4>
    <button type="button" class="btn-close w3mentorsmodalJs close-checkout-modal" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-0">
    <div class="checkout-body">
        <div class="payment-checkout">
            <div class="payment-checkout__body">
                <div class="summary-layout">
                    <?php if (!empty($teacher)) { ?>
                        <div class="summary-layout__col">
                            <div class="avatar-card">
                                <div class="avatar-card__media">
                                    <span class="avtar avtar--round">
                                        <?php
                                        $img = FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME);
                                        ?>
                                        <img src="<?php echo $img; ?>" alt="<?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?>">
                                    </span>
                                </div>
                                <div class="avatar-card__details">
                                    <h4><?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?></h4>
                                    <?php
                                    if (!empty($address)) { ?>
                                        <p><?php echo $address; ?></p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="summary-layout__col">
                        <div class="checkout-summary">
                            <div class="checkout-summary__body">
                                <?php foreach ($cartItems[Cart::LESSON] as $key => $value) { ?>
                                    <p><b><?php echo Label::getLabel('LBL_Lesson_Count') ?>: </b><?php echo $value['ordles_quantity']; ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_Lesson(s)_Duration') ?>: </b><?php echo $value['ordles_duration'] . ' ' . Label::getLabel('LBL_Mins/Lesson'); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_Item_Price') ?>: </b><?php echo MyUtility::formatMoney($value['ordles_amount']) . Label::getLabel('LBL_/Lesson'); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_TEACH_LANGUAGE') ?>: </b><?php echo $value['ordles_tlang']; ?></p>
                                <?php } ?>
                                <?php if (!empty($cartItems[Cart::SUBSCR])) { ?>
                                    <p><b><?php echo Label::getLabel('LBL_Lesson_Count') ?>: </b><?php echo $cartItems[Cart::SUBSCR]['ordles_quantity']; ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_Lesson(s)_Duration') ?>: </b><?php echo $cartItems[Cart::SUBSCR]['ordles_duration'] . ' ' . Label::getLabel('LBL_Mins/Lesson'); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_Item_Price') ?>: </b><?php echo MyUtility::formatMoney($cartItems[Cart::SUBSCR]['ordles_amount']) . Label::getLabel('LBL_/Lesson'); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_TEACH_LANGUAGE') ?>: </b><?php echo $cartItems[Cart::SUBSCR]['ordles_tlang']; ?></p>
                                <?php } ?>
                                <?php foreach ($cartItems[Cart::GCLASS] as $key => $class) { ?>
                                    <h5 class="mb-3"><?php echo $class['grpcls_title']; ?></h5>
                                    <p><b><?php echo Label::getLabel('LBL_ITEM_PRICE') ?>: </b><?php echo MyUtility::formatMoney($class['ordcls_amount']) . Label::getLabel('LBL_/CLASS'); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_START_TIME'); ?>: </b><?php echo MyDate::showDate(MyDate::formatDate($class['grpcls_start_datetime']), true); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_END_TIME'); ?>: </b><?php echo MyDate::showDate(MyDate::formatDate($class['grpcls_end_datetime']), true); ?></p>
                                <?php } ?>
                                <?php foreach ($cartItems[Cart::PACKGE] as $key => $package) { ?>
                                    <h5 class="mb-3"><?php echo $package['grpcls_title']; ?></h5>
                                    <p><b><?php echo Label::getLabel('LBL_ITEM_PRICE') ?>: </b><?php echo MyUtility::formatMoney($package['grpcls_amount']) . Label::getLabel('LBL_/PACKAGE'); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_START_TIME'); ?>: </b><?php echo MyDate::showDate(MyDate::formatDate($package['grpcls_start_datetime']), true); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_END_TIME'); ?>: </b><?php echo MyDate::showDate(MyDate::formatDate($package['grpcls_end_datetime']), true); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_TOTAL_CLASSES'); ?>: </b><?php echo count($package['classes']); ?></p>
                                <?php } ?>
                                <?php foreach ($cartItems[Cart::COURSE] as $key => $course) { ?>
                                    <h5 class="mb-3"><?php echo $course['course_title']; ?></h5>
                                    <p><b><?php echo Label::getLabel('LBL_ITEM_PRICE') ?>: </b><?php echo CourseUtility::formatMoney($course['course_price']) . Label::getLabel('LBL_/COURSE'); ?></p>
                                    <p><b><?php echo Label::getLabel('LBL_CART_COURSE_LANGUAGE') ?>: </b><?php echo $course['course_clang_name']; ?></p>
                                <?php } ?>
                                <?php foreach ($cartItems[Cart::SUBSCRIPTIONPLAN] as $key => $plan) { ?>
                                    <div>
                                        <h5 class="mb-3"><?php echo $plan['plan_name']; ?></h5>
                                        <p><b><?php echo Label::getLabel('LBL_ITEM_PRICE') ?>: </b><?php echo MyUtility::formatMoney($plan['subplan_price']); ?></p>
                                        <p><b><?php echo Label::getLabel('LBL_PLAN_VALIDITY'); ?>: </b><?php echo $plan['subplan_validity']; ?></p>
                                        <p><b><?php echo Label::getLabel('LBL_Lesson_Count'); ?>: </b><?php echo $plan['subplan_lesson_count']; ?></p>
                                        <p><b><?php echo Label::getLabel('LBL_Lesson_duration'); ?>: </b><?php echo $plan['subplan_lesson_duration']; ?></p>
                                    </div>
                                <?php } ?>
                                <p><b><?php echo Label::getLabel('LBL_Payment_Method') ?>: </b><?php echo $paymentMethod; ?></p>

                            </div>
                            <div class="checkout-summary__footer">
                                <span><?php echo Label::getLabel('LBL_NET_TOTAL'); ?></span>
                                <?php if (isset($addAndPay) && $addAndPay == AppConstant::YES && $walletBalance > 0 && $walletBalance < $totalAmount) { ?>
                                    <span><?php echo MyUtility::formatMoney($totalAmount - $walletBalance); ?></span>
                                <?php } else { ?>
                                    <span><?php echo MyUtility::formatMoney($totalAmount); ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="payment-checkout__foot">
                <p class="payment-note"><b>* <?php echo str_replace(['{DURATION}'], [FatApp::getConfig('CONF_CANCEL_ORDER_DURATION')], Label::getLabel('LBL_ORDER_AUTO_CANCEL_AFTER_{DURATION}')); ?></b></p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn--primary color-white" onclick="cart.confirmOrder(cart.prop.checkout_form_data);"><?php echo ($totalAmount > 0) ? Label::getLabel('LBL_PROCEED_TO_PAYMENT') : Label::getLabel('LBL_CONFIRM'); ?></button>
</div>