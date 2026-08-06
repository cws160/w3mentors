<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$msg = str_replace('{contacturl}', '<a href="' . MyUtility::makeUrl('Contact') . '" class="underline color-primary">' . Label::getLabel('LBL_CLICK_HERE') . '</a>', Label::getLabel('MSG_LEARNER_FAILURE_ORDER_{contacturl}'));
?>
<div class="checkout-payment bg-gradiant min-vh-100">
    <div class="payment-panel">
        <div class="payment-panel__head">
            <a href="<?php echo MyUtility::makeUrl(''); ?>" class="logo">
                <?php echo MyUtility::getLogo(); ?>
            </a>
        </div>
        <div class="payment-panel__body">
            <div class="checkout-thanku">
                <h4><?php echo Label::getLabel('LBL_PAYMENT_FAILED'); ?></h4>
                <div class="checkout-thanku__media"><img src="<?php echo CONF_WEBROOT_URL; ?>images/payment_failed.svg" alt=""></div>
            </div>
            <div class="payment-order-details my-5">
                <div class="order-info">
                    <span class="order-info__label"><?php echo Label::getLabel('LBL_PAYABLE_AMOUNT'); ?></span>
                    <span class="order-info__value"><?php echo MyUtility::formatMoney($order['order_net_amount']); ?></span>
                </div>
                <div class="order-info">
                    <span class="order-info__label"><?php echo Label::getLabel('LBL_ORDER_NUMBER'); ?></span>
                    <span class="order-info__value"><?php echo Order::formatOrderId($order["order_id"]); ?></span>
                </div>
            </div>
        </div>
        <div class="payment-panel__footer">
            <div class="text-center">
                <?php $msgs = array_merge($messageData['msgs'], $messageData['errs'], $messageData['info'], $messageData['dialog']); ?>
                <?php if (count($msgs) > 0) { ?>
                    <p><?php echo current($msgs); ?></p>
                <?php } ?>
                <p><?php echo $msg; ?></p>
            </div>
        </div>
    </div>
</div>