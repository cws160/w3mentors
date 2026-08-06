<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('id', 'paystackPayFrm');
$frm->setFormTagAttribute('class', 'form form--normal');
$frm->setFormTagAttribute('action', $response['data']['authorization_url']);
$btn = $frm->getField('btn_submit');
$btn->setFieldTagAttribute('class', "d-none");
?>
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
                <div class="payable-form__body" id="paymentFormElement-js">
                    <h6><?php echo Label::getLabel('LBL_REDIRECTING_TO_PAYMENT_PAGE...'); ?></h6>
                    <?php echo $frm->getFormHtml(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $("form#paystackPayFrm").submit();
</script>
