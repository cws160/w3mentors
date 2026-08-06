<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('onsubmit', 'setup(this);return false;');
$frm->setFormTagAttribute('class', 'form form--normal');
$frm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-';
$frm->developerTags['fld_default_col'] = 12;
?>
<div class="checkout-payment bg-gradiant min-vh-100">
    <div class="payment-panel">
        <div class="payment-panel__head">
            <a href="<?php echo MyUtility::makeUrl(''); ?>" class="logo">
                <?php echo MyUtility::getLogo(); ?>
            </a>
        </div>
        <div class="payment-panel__body">
            <div class="bank-details">
                <p><b><?php echo Label::getLabel('LBL_COMPLETE_BANK_TRANSFER_ORDER_HEADING'); ?></b></p>
                <div class="banking-info">
                    <p><?php echo nl2br($accountDetails ?? ''); ?></p>
                </div>
            </div>
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
                <?php echo $frm->getFormHtml(); ?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        setup = function (frm) {
            if (!$(frm).validate()) {
                return;
            }
            fcom.process();
            var data = new FormData(frm);
            var action = fcom.makeUrl('Payment', 'return', [<?php echo $order['order_id']; ?>]);
            fcom.ajaxMultipart(action, data, function (res) {
                if (res.url) {
                    window.location.href = res.url;
                }
            }, {fOutMode: 'json'});
        };
    });
</script>