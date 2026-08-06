<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'planSearchFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'searchPlans(this); return(false);');
$keywordFld = $frm->getField('keyword');
$keywordFld->addFieldTagAttribute('placeholder', Label::getLabel('LBL_KEYWORD'));
$levelFld = $frm->getField('plan_level');
$btnReset = $frm->getField('btn_reset');
$btnReset->addFieldTagAttribute('onclick', 'clearPlanSearch()');
?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_Manage_Lessons_Plans'); ?></h1>
            </div>
            <div class="col-sm-auto">
                <div class="buttons-group d-flex align-items-center gap-2">
                    <a href="javascript:void(0)" class="btn btn--secondary slide-toggle-js">
                        <svg class="icon icon--search icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_SEARCH'); ?>
                    </a>
                    <?php if (!isset($listing_type)) { ?>
                        <a href="javascript:void(0);" onclick="form(0);" class="btn color-secondary btn--bordered">
                            <svg class="icon icon--add icon--small me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M11 11V7h2v4h4v2h-4v4h-2v-4H7v-2h4zm1 11C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"></path>
                            </svg>
                            <?php echo Label::getLabel('LBL_ADD_PLAN'); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <!-- [ FILTERS ========= -->
        <div id="plan-search" class="search-filter slide-target-js">
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
                                <?php echo $levelFld->getCaption(); ?>
                                <?php if ($levelFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $levelFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"></label></div>
                        <div class="field-wraper form-buttons-group">
                            <div class="field_cover">
                                <?php echo $frm->getFieldHtml('btn_submit'); ?>
                                <?php echo $frm->getFieldHtml('btn_reset'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo $frm->getFieldHtml('pageno'); ?>
            <?php echo $frm->getFieldHtml('pagesize'); ?>
            <?php echo $frm->getFieldHtml('listing_type'); ?>
            <?php echo $frm->getFieldHtml('attached_plan_id'); ?>
            <?php echo $frm->getFieldHtml('recordId'); ?>
            <?php echo $frm->getFieldHtml('planType'); ?>
            <?php echo $frm->getFieldHtml('mainpage'); ?>
            </form>
            <?php echo $frm->getExternalJS(); ?>
        </div>
        <!-- ] ========= -->
    </div>
    <div class="page__body">
        <div class="page-content plan-listing" id="listing"></div>
    </div>
    <?php if (isset($listing_type) && $listing_type) { ?>
        <script>
            $(".slide-toggle-js").on('click', document, function() {
                $("#plan-search").slideToggle();
            });
        </script>
    <?php } ?>