<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$currency = MyUtility::getSystemCurrency();
$frm->setFormTagAttribute('id', 'frmPaymentForm');
$frm->setFormTagAttribute('class', 'form form--normal');
$frm->setFormTagAttribute('action', MyUtility::makeUrl('Payment', 'return', [$order['order_id']]));
$frm->getField('cc_number')->addFieldTagAttribute('class', 'p-cards');
$frm->getField('cc_number')->addFieldTagAttribute('id', 'cc_number');
$cancelUrl = MyUtility::makeFullUrl('Payment', 'cancel', [$order['order_id']], CONF_WEBROOT_FRONTEND);
?>
<div class="payment-page">
    <div class="cc-payment">
        <div class="logo-payment">
            <?php echo MyUtility::getLogo(); ?>
        </div>
        <div class="reff row">
            <div class="col-lg-8 col-md-8 col-sm-12">
                <p class=""><?php echo Label::getLabel('LBL_PAYABLE_AMOUNT'); ?> : <strong><?php echo MyUtility::formatMoney($order['order_net_amount']); ?></strong> </p>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12">
                <p class=""><?php echo Label::getLabel('LBL_ORDER_INVOICE'); ?>: <strong><?php echo Order::formatOrderId($order["order_id"]); ?></strong></p>
            </div>
        </div>
        <div id="body" class="body">
            <div class="payment-from">
                <?php echo $frm->getFormTag(); ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo Label::getLabel('LBL_CARD_HOLDER_NAME'); ?></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"> <?php echo $frm->getFieldHtml('cc_owner'); ?> </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo Label::getLabel('LBL_ENTER_CREDIT_CARD_NUMBER'); ?></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"> <?php echo $frm->getFieldHtml('cc_number'); ?> </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"> <?php echo Label::getLabel('LBL_EXPIRY_MONTH'); ?> </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php
                                    $fld = $frm->getField('cc_expire_date_month');
                                    $fld->addFieldTagAttribute('id', 'ccExpMonth');
                                    $fld->addFieldTagAttribute('class', 'ccExpMonth  combobox required');
                                    echo $fld->getHtml();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="caption-wraper">
                            <label class="field_label"> <?php echo Label::getLabel('LBL_EXPIRY_YEAR'); ?> </label>
                        </div>
                        <div class="field-set">
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php
                                    $fld = $frm->getField('cc_expire_date_year');
                                    $fld->addFieldTagAttribute('id', 'ccExpYear');
                                    $fld->addFieldTagAttribute('class', 'ccExpYear  combobox required');
                                    echo $fld->getHtml();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo Label::getLabel('LBL_CVV_SECURITY_CODE'); ?></label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover"> <?php echo $frm->getFieldHtml('cc_cvv'); ?> </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="payment-order-details">
                    <div class="order-info">
                        <span class="order-info__label"><?php echo Label::getLabel('LBL_TOTAL_PAYABLE'); ?></span>
                        <span class="order-info__value"><?php echo MyUtility::formatMoney($order['order_net_amount']) ?> </span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"></label></div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $frm->getFieldHtml('order_id'); ?>
                                    <?php echo $frm->getFieldHtml('btn_submit'); ?>
                                    <a href="<?php echo $cancelUrl; ?>" class="btn btn--medium"><?php echo Label::getLabel('LBL_Cancel'); ?></a>
                                </div>
                            </div>
                            <span class="-gap -hide-mobile"></span>
                            <?php if ($order['order_currency_code'] != $currency['currency_code']) { ?>
                                <p><?php echo MyUtility::getCurrencyDisclaimer($order['order_net_amount']); ?></p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                </form>
                <?php echo $frm->getExternalJs(); ?>
                <div id="ajax_message"></div>
            </div>
        </div>
    </div>
</div>
<div class="loading-wrapper" style="display: none;">
    <div class="loading">
        <div class="inner rotate-one"></div>
        <div class="inner rotate-two"></div>
        <div class="inner rotate-three"></div>
    </div>
</div>