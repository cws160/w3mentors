<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$checkoutForm->addFormTagAttribute('onsubmit', 'cart.reviewOrder(this.form); return false;');
$orderType = $checkoutForm->getField('order_type');
$orderType->addFieldTagAttribute('class', 'd-none');
$pmethodField = $checkoutForm->getField('order_pmethod_id');
$couponField = $checkoutForm->getField('coupon_code');
$couponField->addFieldTagAttribute('id', 'coupon_code');
$couponField->addFieldTagAttribute('onkeypress', 'cart.disableEnter(event);');
$couponField->addFieldTagAttribute('placeholder', Label::getLabel('LBL_ENTER_COUPON_CODE'));
$submitField = $checkoutForm->getField('submit');
$submitField->addFieldTagAttribute('onclick', 'cart.reviewOrder(this.form);');
$submitField->addFieldTagAttribute('class', 'btn btn--primary color-white');
$submitField->addFieldTagAttribute('form', 'checkoutForm');
$rewardField = $checkoutForm->getField('apply_reward');
$rewardField->addFieldTagAttribute('id', 'apply_reward');
$rewardField->addFieldTagAttribute('class', 'selection-tabs__input');
$rewardField->addFieldTagAttribute('onclick', 'cart.applyRewards(this);');
$minimumCreditUseLimit = FatApp::getConfig('CONF_REWARD_POINT_MINIMUM_USE');
$disabled = $cartNetAmount == 0 ? 'disabled' : '';
$disabledReward = false;
if (!empty($appliedCoupon['coupon_id'])) {
    if ($cartTotal == $appliedCoupon['coupon_discount']) {
        $appliedReward = 0;
        $disabledReward = true;
    }
}
?>
<div class="modal-header modal-header--checkout">
    <?php if ($orderTypeUsed == Order::TYPE_LESSON || $orderTypeUsed == Order::TYPE_SUBSCR) { ?>
        <button class="btn-back" onclick="cart.viewCalendar(cart.prop.ordles_teacher_id, cart.prop.ordles_tlang_id, cart.prop.ordles_duration, cart.prop.ordles_quantity, cart.prop.ordles_type, cart.prop.ordles_offline);"></button>
    <?php } ?>
    <h4 class="flex-1 text-center"><?php echo Label::getLabel('LBL_SELECT_PAYMENT_METHOD'); ?></h4>
    <button type="button" class="btn-close w3mentorsmodalJs close-checkout-modal" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="checkout-body">
        <div class="payment-checkout">
            <div class="payment-checkout__body">
                <?php echo $checkoutForm->getFormTag(); /* <form> */ ?>
                <?php echo $orderType->getHTML(); ?>
                <div class="checkout-panel">
                    <div class="checkout-panel__method">
                        <div class="selection-tabs d-block selection--payment">
                            <?php if ($walletBalance > 0 && $walletBalance < $cartNetAmount) { ?>
                                <label class="selection-tabs__label payment-method-js">
                                    <input type="checkbox" name="add_and_pay" class="selection-tabs__input" value="1" <?php echo ($addAndPay == 1) ? 'checked="checked"' : ''; ?> onclick="cart.selectWallet(this.checked)" />
                                    <div class="selection-tabs__title">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <g>
                                                <path d="M12,22A10,10,0,1,1,22,12,10,10,0,0,1,12,22Zm-1-6,7.07-7.071L16.659,7.515,11,13.172,8.174,10.343,6.76,11.757Z" transform="translate(-2 -2)"></path>
                                            </g>
                                        </svg>
                                        <div class="payment-type">
                                            <p><?php echo str_replace(['{remaining}'], [MyUtility::formatMoney($walletBalance)], Label::getLabel('LBL_USE_WALLET_BALANCE_({remaining})')); ?></p>
                                        </div>
                                    </div>
                                </label>
                            <?php } ?>
                            <?php foreach ($pmethodField->options as $id => $name) { ?>
                                <label class="selection-tabs__label payment-method-js <?php echo $disabled; ?>">
                                    <input name="order_pmethod_id" type="radio" class="selection-tabs__input" value="<?php echo $id; ?>" <?php echo ($pmethodField->value == $id) ? 'checked' : ''; ?> <?php echo $disabled; ?> />
                                    <div class="selection-tabs__title">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <g>
                                                <path d="M12,22A10,10,0,1,1,22,12,10,10,0,0,1,12,22Zm-1-6,7.07-7.071L16.659,7.515,11,13.172,8.174,10.343,6.76,11.757Z" transform="translate(-2 -2)"></path>
                                            </g>
                                        </svg>
                                        <div class="payment-type">
                                            <p><?php echo ($id != $walletPayId) ? $name : str_replace(['{balance}'], [MyUtility::formatMoney($walletBalance)], Label::getLabel('LBL_WALLET_BALANCE_({balance})')); ?></p>
                                        </div>
                                    </div>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="checkout-panel__summary">
                        <div class="checkout-coupons">
                            <div class="checkout-coupons__head">
                                <h6><?php echo Label::getLabel('LBL_HAVE_A_COUPON?'); ?></h6>
                                <?php if (count($availableCoupons) > 0) { ?>
                                    <a href="javascript:void(0);" class="text-uppercase link js--coupons-trigger"><?php echo Label::getLabel('LBL_VIEW_COUPONS'); ?></a>
                                <?php } ?>
                            </div>
                            <div class="checkout-coupons__body">
                                <div class="apply-coupon">
                                    <?php echo $couponField->getHTML(); ?>
                                    <a href="javascript:voiid(0);" onclick="cart.applyCoupon();" class="btn btn--secondary btn--small color-white"><?php echo Label::getLabel('LBL_APPLY'); ?></a>
                                </div>
                                <?php if (!empty($appliedCoupon['coupon_id'])) { ?>
                                    <div class="coupon-applied">
                                        <div class="coupon-type">
                                            <span class="bold-600 coupon-code"><?php echo $appliedCoupon['coupon_code']; ?></span>
                                            <p><?php echo Label::getLabel('LBL_COUPON_APPLIED'); ?></p>
                                        </div>
                                        <a href="javascript:void(0);" onclick="cart.removeCoupon();" class="btn-close"></a>
                                    </div>
                                <?php } ?>
                                <?php if (count($availableCoupons) > 0) { ?>
                                    <div class="coupon-box js--coupons-target" id="COUPONS">
                                        <div class="coupon-box__head">
                                            <h6><?php echo Label::getLabel('LBL_AVAILABLE_COUPONS'); ?></h6>
                                            <button type="button" class="btn-close js--close-coupons" data-bs-dismiss="coupon-box"></button>
                                        </div>
                                        <div class="coupon-box__body">
                                            <div class="coupons-wrapper">
                                                <?php foreach ($availableCoupons as $key => $coupon) { ?>
                                                    <div class="coupon-list">
                                                        <div class="coupon-list__head">
                                                            <span class="badge color-secondary"><?php echo $coupon['coupon_code']; ?></span>
                                                            <a href="javascript:void(0);" onclick="cart.applyCoupon('<?php echo $coupon['coupon_code']; ?>');" class="link text-uppercase"><?php echo Label::getLabel('LBL_APPLY'); ?></a>
                                                        </div>
                                                        <div class="coupon-list__content">
                                                            <p class="bold-600"><?php echo $coupon['coupon_title']; ?></p>
                                                            <?php if (!empty($coupon['coupon_description'])) { ?>
                                                                <p><?php echo nl2br($coupon['coupon_description']); ?> </p>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="payment-options">
                            <?php if ((FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS') && $rewardBalance >= $minimumCreditUseLimit && RewardPoint::convertToPoints($cartNetAmount) >= $minimumCreditUseLimit) || $appliedReward == 1) { ?>
                                <label class="checkbox">
                                    <input type="checkbox" name="apply_reward" id="apply_reward" onclick="cart.applyRewards(this);" value="1" <?php echo ($appliedReward == 1) ? 'checked="checked"' : ''; ?> <?php echo ($disabledReward == 1) ? 'disabled' : ''; ?> />
                                    <i class="input-helper"></i>
                                    <?php
                                    $rewardBalanceValue = MyUtility::formatMoney(round(RewardPoint::convertToValue($rewardBalance), 2));
                                    $rewardBalanceLabel = Label::getLabel('LBL_REWARD_BALANCE_{rewards}_({balance})');
                                    ?>
                                    <b><?php echo str_replace(['{rewards}', '{balance}'], [$rewardBalance, $rewardBalanceValue], $rewardBalanceLabel); ?></b>
                                </label>
                            <?php } ?>
                        </div>

                        <div class="payment-summary">
                            <?php if (!empty($appliedCoupon['coupon_id']) || !empty($appliedReward) || $addAndPay == AppConstant::YES) { ?>
                                <div class="payment-row">
                                    <span class="payment-row__label"><?php echo Label::getLabel('LBL_TOTAL_AMOUNT'); ?>:</span>
                                    <span class="payment-row__value"><?php echo MyUtility::formatMoney($cartTotal); ?></span>
                                </div>
                            <?php } ?>
                            <?php if (!empty($appliedCoupon['coupon_id'])) { ?>
                                <div class="payment-row">
                                    <span class="payment-row__label"><?php echo Label::getLabel('LBL_COUPON_DISCOUNT'); ?></span>
                                    <span class="payment-row__value"><?php echo MyUtility::formatMoney(-$appliedCoupon['coupon_discount']); ?></span>
                                </div>
                            <?php } ?>
                            <?php if (!empty($appliedReward)) { ?>
                                <div class="payment-row">
                                    <span class="payment-row__label"><?php echo Label::getLabel('LBL_REWARD_DISCOUNT'); ?></span>
                                    <span class="payment-row__value"><?php echo MyUtility::formatMoney(-$rewardDiscount); ?></span>
                                </div>
                            <?php } ?>
                            <?php if ($addAndPay == AppConstant::YES && $walletBalance > 0 && $walletBalance < $cartNetAmount) { ?>
                                <div class="payment-row">
                                    <span class="payment-row__label"><?php echo Label::getLabel('LBL_WALLET_DEDUCTION'); ?></span>
                                    <span class="payment-row__value"><?php echo MyUtility::formatMoney(-$walletBalance); ?></span>
                                </div>
                                <div class="payment-row">
                                    <span class="payment-row__label"><?php echo Label::getLabel('LBL_NET_TOTAL'); ?></span>
                                    <span class="payment-row__value"><?php echo MyUtility::formatMoney($cartNetAmount - $walletBalance); ?></span>
                                </div>
                            <?php } else { ?>
                                <div class="payment-row">
                                    <span class="payment-row__label"><?php echo Label::getLabel('LBL_NET_TOTAL'); ?></span>
                                    <span class="payment-row__value"><?php echo MyUtility::formatMoney($cartNetAmount); ?></span>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php echo $checkoutForm->getFieldHTML('ordles_type') ?>
                </form>
                <?php echo $checkoutForm->getExternalJS(); ?>
            </div>
            <div class="payment-checkout__foot">
                <p class="payment-note">
                    <?php echo str_replace("{currencycode}", $currencyData['currency_code'], Label::getLabel('LBL_*_ALL_PURCHASES_ARE_IN_{currencycode}._FOREIGN_TRANSACTION_FEES_MIGHT_APPLY,_ACCORDING_TO_YOUR_BANK_POLICIES')); ?>
                </p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <div class="row justify-content-center align-items-center gap-md-5">
        <div class="col-auto">
            <?php echo $submitField->getHTML(); ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.js--coupons-trigger').click(function() {
            $('.js--coupons-target').toggleClass('is-visible');
            $(this).toggleClass('is-open');
        });

        $('.js--close-coupons').click(function() {
            $('.js--coupons-target').removeClass('is-visible');
            $('.js--coupons-trigger').removeClass('is-open');
        });
    });
    $('.apply-coupon-js').click(function() {
        let couponCode = $('#coupon_code').val();
        cart.applyPromoCode(couponCode);
    });
    $('input[type=radio][name=order_pmethod_id]').on('change', function() {
        if ($(this).val() == <?php echo $walletPayId; ?>) {
            $('.renew-payment').show();
        } else {
            $('.renew-payment').hide();
        }
    });
</script>