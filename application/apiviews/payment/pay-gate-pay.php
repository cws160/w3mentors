<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php $currency = MyUtility::getSystemCurrency(); ?>
<script src="https://js.stripe.com/v3/"></script>
<script>
    var stripe = Stripe('<?php echo $stripe['publishable_key']; ?>');
</script>
<div class="payment-page">
    <div class="cc-payment">
        <div class="logo-payment">
            <?php echo MyUtility::getLogo(); ?>
        </div>
        <div class="reff row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <p><?php echo Label::getLabel('LBL_PAYABLE_AMOUNT'); ?> : <strong><?php echo MyUtility::formatMoney($order['order_net_amount']) ?></strong> </p>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12">
                <p><?php echo Label::getLabel('LBL_ORDER_INVOICE'); ?>: <strong><?php echo Order::formatOrderId($order["order_id"]); ?></strong></p>
            </div>
        </div>
        <div class="payment-from">
            <div class="payable-form__body">
                <div class="message-display message-display--small">
                    <div class="loader"></div>
                    <h1><?php echo Label::getLabel('LBL_WE_ARE_REDIRECTING_YOU'); ?></h1>
                    <h4><?php echo Label::getLabel('LBL_PLEASE_WAIT..'); ?></h4>
                    <?php if ($order['order_currency_code'] != $currency['currency_code']) { ?>
                        <p><?php echo MyUtility::getCurrencyDisclaimer($order['order_net_amount']); ?></p>
                    <?php } ?>
                </div>
                <div class="text-center">
                    <div class="payment-from">
                        <?php echo $frm->getFormHtml(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function() {
        $('form[name="payGatePayForm"]').submit();
    });
</script>