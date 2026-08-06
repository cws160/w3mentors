<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php $currency = MyUtility::getSystemCurrency(); ?>
<div class="checkout-payment bg-gradiant min-vh-100">
    <div class="payment-panel">
        <div class="payment-panel__head">
            <a href="<?php echo MyUtility::makeUrl(''); ?>" class="logo">
                <?php echo MyUtility::getLogo(); ?>
            </a>
        </div>
        <div class="payment-panel__body">
            <div class="payment-order-details my-5">
                <div class="order-info">
                    <span class="order-info__label"><?php echo Label::getLabel('LBL_PAYABLE_AMOUNT'); ?></span>
                    <span class="order-info__value"><?php echo MyUtility::formatMoney($order['order_net_amount']) ?></span>
                </div>
                <div class="order-info">
                    <span class="order-info__label"><?php echo Label::getLabel('LBL_ORDER_NUMBER'); ?></span>
                    <span class="order-info__value"><?php echo Order::formatOrderId($order["order_id"]); ?></span>
                </div>
            </div>
            <div class="payment-form">
                <div class="payable-form__body">
                    <div class="message-display message-display--small">
                        <div class="loader"></div>
                        <h1><?php echo Label::getLabel('LBL_WE_ARE_REDIRECTING_YOU'); ?></h1>
                        <h4><?php echo Label::getLabel('LBL_PLEASE_WAIT..'); ?></h4>
                        <?php if ($order['order_currency_code'] != $currency['currency_code']) { ?>
                            <p><?php echo MyUtility::getCurrencyDisclaimer($order['order_net_amount']); ?></p>
                        <?php } ?>
                    </div>
                    <div class="-align-center">
                        <?php if (!isset($error)) { ?>
                            <?php echo $frm->getFormHtml() ?>
                        <?php } else { ?>
                            <div class="alert alert--danger"><?php echo $error ?></div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('form[name="payfastPayForm"]').submit();
    });
</script>