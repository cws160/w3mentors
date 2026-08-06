<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'bankInfoFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 6;
$frm->setFormTagAttribute('onsubmit', 'setUpBankInfo(this); return(false);');
$bankNameField = $frm->getField('ub_bank_name');
$accountHolderName = $frm->getField('ub_account_holder_name');
$accountNumber = $frm->getField('ub_account_number');
$ifscCodeField = $frm->getField('ub_ifsc_swift_code');
$ubBankAddress = $frm->getField('ub_bank_address');
?>
<div class="content-panel__head">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_Manage_Payments'); ?></h5>
        </div>
        <div>
            <p class="color-secondary mb-0"><?php echo Label::getLabel('LBL_MANAGE_PAYMENT_INFO_TEXT'); ?></p>
        </div>
    </div>
</div>
<div class="content-panel__body">
    <div class="form">
        <?php echo $frm->getFormTag(); ?>
        <div class="form__body p-0">
            <nav class="tabs tabs--line ps-4 pe-4">
                <ul>
                    <li class="is-active"><a href="javascript:void(0);" onclick="getPayout('bankPayout');"><?php echo Label::getLabel('LBL_BANK_ACCOUNT'); ?></a></li>
                    <?php if (!empty($payoutMethods[PaypalPayout::KEY])) { ?>
                        <li><a href="javascript:void(0);" onclick="getPayout('paypalPayout');"><?php echo Label::getLabel('LBL_PAYPAL_EMAIL'); ?></a></li>
                    <?php } ?>
                </ul>
            </nav>
            <div class="tabs-data">
                <div class="padding-6 pb-0" id="paymentInfoDiv">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label"><?php echo $bankNameField->getCaption(); ?>
                                        <?php if ($bankNameField->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $bankNameField->getHtml(); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $accountHolderName->getCaption(); ?>
                                        <?php if ($accountHolderName->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $accountHolderName->getHtml(); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $accountNumber->getCaption(); ?>
                                        <?php if ($accountNumber->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $accountNumber->getHtml(); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $ifscCodeField->getCaption(); ?>
                                        <?php if ($ifscCodeField->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover"><?php echo $ifscCodeField->getHtml(); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $ubBankAddress->getCaption(); ?>
                                        <?php if ($ubBankAddress->requirement->isRequired()) { ?><span class="spn_must_field">*</span><?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $ubBankAddress->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form__actions">
            <div class="d-flex">
                <?php echo $frm->getFieldHTML('btn_submit'); ?>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJS(); ?>
    </div>
</div>