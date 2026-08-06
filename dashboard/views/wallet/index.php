<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form--small');
$frm->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$keywordFld = $frm->getField('keyword');
$keywordFld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_Keyword'));
$datefromFld = $frm->getField('date_from');
$datefromFld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_From_Date'));
$datetoFld = $frm->getField('date_to');
$datetoFld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_To_Date'));
$submitBtn = $frm->getField('btn_submit');
$btnReset = $frm->getField('btn_reset');
$btnReset->addFieldTagAttribute('onclick', 'clearSearch()');
?>
<!-- [ PAGE ========= -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_MY_WALLET'); ?></h1>
            </div>
            <div class="col-sm-auto">
                <div class="buttons-group d-flex align-items-center gap-2">
                    <a href="javascript:void(0)" class="btn btn--secondary slide-toggle-js">
                        <svg class="icon icon--clock icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_Search'); ?>
                    </a>
                </div>
            </div>
        </div>
        <!-- [ FILTERS ========= -->
        <div class="search-filter slide-target-js">
            <?php echo $frm->getFormTag(); ?>
            <div class="row">
                <div class="col-lg-4 col-sm-6">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $keywordFld->getCaption(); ?>
                                <?php if ($keywordFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $keywordFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $datefromFld->getCaption(); ?>
                                <?php if ($datefromFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $datefromFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $datetoFld->getCaption(); ?>
                                <?php if ($datetoFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $datetoFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"></label></div>
                        <div class="field-wraper form-buttons-group">
                            <div class="field_cover">
                                <?php echo $frm->getFieldHtml('pageno'); ?>
                                <?php echo $frm->getFieldHtml('pagesize'); ?>
                                <?php echo $frm->getFieldHtml('btn_submit'); ?>
                                <?php echo $frm->getFieldHtml('btn_reset'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </form>
            <?php echo $frm->getExternalJS(); ?>
        </div>
        <!-- ] ========= -->
    </div>
    <div class="page__body">
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content">
            <div class="wallet-box page-container mb-4 p-4">
                <div class="row justify-content-between align-items-center">
                    <div class="col-sm-4">
                        <div class="wallet d-flex">
                            <div class="wallet__media">
                                <svg class="icon icon--wallet icon--large me-3">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#wallet-large'; ?>"></use>
                                </svg>
                            </div>
                            <div class="wallet__content">
                                <span class="m-0"><?php echo Label::getLabel('LBL_Wallet_Balance'); ?></span>
                                <h3 class="bold-700"><?php echo MyUtility::formatMoney($balance); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-auto col-lg-8  col-12">
                        <div class="buttons-group d-flex align-items-center gap-2">
                            <?php if ($siteUserType != User::AFFILIATE) { ?>
                                <a href="javascript:void(0);" onclick="addMoney();" class="btn btn--transparent color-primary">
                                    <svg class="icon icon--issue icon--small me-2">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#plus'; ?>"></use>
                                    </svg>
                                    <?php echo Label::getLabel('LBL_RECHARGE_WALLET'); ?>
                                </a>
                                <a href="javascript:void(0);" onclick="redeemGiftcardForm();" class="btn btn--transparent color-primary">
                                    <svg class="icon icon--gift me-1">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#giftcards'; ?>"></use>
                                    </svg>
                                    <?php echo Label::getLabel('LBL_Redeem_Gift_Card'); ?>
                                </a>
                            <?php
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div id="listing" class="table-scroll"></div>
        </div>
        <!-- ] -->
    </div>
    <script>
        jQuery(document).ready(function() {
            search();
        });
    </script>