<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'bankInfoFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'setupPaypalInfo(this); return(false);');
$ub_paypal_email_address = $frm->getField('ub_paypal_email_address');
$ub_paypal_email_address->developerTags['col'] = 8;
?>
<div class="content-panel__head">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_MANAGE_PAYMENTS'); ?></h5>
        </div>
        <div></div>
    </div>
</div>
<div class="content-panel__body">
    <div class="form">
        <?php echo $frm->getFormTag(); ?>
        <div class="form__body p-0">
            <nav class="tabs tabs--line ps-4 pe-4">
                <ul>
                    <?php if (!empty($payoutMethods[BankPayout::KEY])) { ?>
                        <li><a href="javascript:void(0);" onclick="getPayout('bankPayout');"><?php echo Label::getLabel('LBL_BANK_ACCOUNT'); ?></a></li>
                    <?php } ?>
                    <li class="is-active"><a href="javascript:void(0);" onclick="getPayout('paypalPayout');"><?php echo Label::getLabel('LBL_PAYPAL_EMAIL'); ?></a></li>
                </ul>
            </nav>
            <div class="tabs-data">
                <div class="padding-6 pb-0">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label"><?php echo $ub_paypal_email_address->getCaption(); ?>
                                        <?php if ($ub_paypal_email_address->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $ub_paypal_email_address->getHtml(); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form__actions">
            <div class="d-flex align-items-center gap-1">
                <?php echo $frm->getFieldHTML('btn_submit'); ?>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJS(); ?>
    </div>
</div>