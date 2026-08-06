<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$currency = MyUtility::getSystemCurrency();
$frm->setFormTagAttribute('id', 'frmPaymentForm');
$frm->setFormTagAttribute('class', 'form form--normal');
$frm->setFormTagAttribute('action', MyUtility::makeUrl('Payment', 'return', [$order['order_id']]));
$frm->getField('cc_number')->addFieldTagAttribute('class', 'p-cards');
$frm->getField('cc_number')->addFieldTagAttribute('id', 'cc_number');
$cancelUrl = MyUtility::makeFullUrl('Payment', 'cancel', [$order['order_id']], CONF_WEBROOT_FRONTEND);
$ccNumber = $frm->getField('cc_number');
$ccOwner = $frm->getField('cc_owner');
$ccCVV = $frm->getField('cc_cvv');
$submitBtn = $frm->getField('btn_submit');
$submitBtn->setFieldTagAttribute('class', 'btn btn--primary');
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
                <?php echo $frm->getFormTag(); ?>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-group-label" for=""><?php echo Label::getLabel('LBL_CARD_HOLDER_NAME') . ($ccOwner->requirement->isRequired() ? '<span class="spn_must_field">*</span>' : ''); ?></label>
                            <?php echo $ccOwner->getHtml(); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-group-label" for=""><?php echo Label::getLabel('LBL_ENTER_CREDIT_CARD_NUMBER') . ($ccNumber->requirement->isRequired() ? '<span class="spn_must_field">*</span>' : ''); ?></label>
                            <?php echo $ccNumber->getHtml(); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-group-label" for=""><?php echo Label::getLabel('LBL_EXPIRY_MONTH'); ?></label>
                            <?php
                            $fld = $frm->getField('cc_expire_date_month');
                            $fld->addFieldTagAttribute('id', 'ccExpMonth');
                            $fld->addFieldTagAttribute('class', 'ccExpMonth  combobox required');
                            echo $fld->getHtml();
                            ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-group-label" for=""><?php echo Label::getLabel('LBL_EXPIRY_YEAR'); ?></label>
                            <?php
                            $fld = $frm->getField('cc_expire_date_year');
                            $fld->addFieldTagAttribute('id', 'ccExpYear');
                            $fld->addFieldTagAttribute('class', 'ccExpYear  combobox required');
                            echo $fld->getHtml();
                            ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-group-label" for=""><?php echo Label::getLabel('LBL_CVV_SECURITY_CODE') . ($ccCVV->requirement->isRequired() ? '<span class="spn_must_field">*</span>' : ''); ?></label>
                            <?php echo $ccCVV->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="payment-panel__foot">
            <div class="form-buttons">
                <?php echo $frm->getFieldHtml('order_id'); ?>
                <?php echo $frm->getFieldHtml('btn_submit'); ?>
                <a href="<?php echo $cancelUrl; ?>" class="btn btn--medium"><?php echo Label::getLabel('LBL_Cancel'); ?></a>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJs(); ?>
        <div id="ajax_message"></div>
    </div>
</div>