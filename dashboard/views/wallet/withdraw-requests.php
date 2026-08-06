<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form--small');
$frm->setFormTagAttribute('onsubmit', 'searchWithdrawRequests(this); return(false);');
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
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_WITHDRAW_REQUESTS'); ?></h1>
            </div>
            <div class="col-sm-auto">
                <div class="buttons-group d-flex align-items-center gap-2">
                    <a href="javascript:void(0)" class="btn btn--secondary slide-toggle-js">
                        <svg class="icon icon--clock icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_Search'); ?>
                    </a>
                    <?php if ($canWithdraw) { ?>
                        <a href="javascript:void(0);" onclick="withdrwalRequestForm();" class="btn btn--bordered btn--transparent color-secondary">
                            <svg class="icon icon--withdraw icon--small me-2">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#withdraw'; ?>"></use>
                            </svg>
                            <?php echo Label::getLabel('LBL_REQUEST_WITHDRAWAL'); ?>
                        </a>
                    <?php } ?>
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
            <div id="listing" class="table-scroll"></div>
        </div>
        <!-- ] -->
    </div>
    <script>
        jQuery(document).ready(function() {
            searchWithdrawRequests();
        });
    </script>