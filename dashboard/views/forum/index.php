<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$srchFrmObj->setFormTagAttribute('id', 'srchQuestionForm');
$srchFrmObj->setFormTagAttribute('onsubmit', 'search(this); return false;');
$srchFrmObj->setFormTagAttribute('class', 'form');
$fld = $srchFrmObj->getField('keyword');
$fld->addFieldTagAttribute('placeholder', $fld->getCaption());
$fld = $srchFrmObj->getField('btn_clear');
$fld->setFieldTagAttribute('onClick', 'clearSearch()');
$srchFrmObj->developerTags['colClassPrefix'] = 'col-sm-6 col-lg-';
$srchFrmObj->developerTags['fld_default_col'] = 4;
$fld = $srchFrmObj->getField('btn_submit');
$fld->setWrapperAttribute('class', 'col-lg-4 col-sm-6 form-buttons-group');
?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-7">
                <h1><?php echo Label::getLabel('LBL_My_Questions'); ?></h1>
                <p class="m-0"><?php echo Label::getLabel('LBL_My_Questions_subheading'); ?></p>
            </div>
            <div class="col-sm-auto">
                <div class="buttons-group d-flex align-items-center gap-2">
                    <a href="javascript:void(0)" class="btn bg-secondary slide-toggle-js">
                        <svg class="icon icon--clock icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#search"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_SEARCH'); ?>
                    </a>
                    <a href="<?php echo MyUtility::generateUrl('Forum', 'form'); ?>" class="btn color-secondary btn--bordered">
                        <svg class="icon icon--add icon--small me-2" viewBox="0 0 24 24" width="24" height="24">
                            <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/sprite.svg#add"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_ASK_QUESTION'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="page-filter">
        <div class="search-filter slide-target-js" style="display:none;">
            <?php echo $srchFrmObj->getFormHtml(); ?>
        </div>
    </div>
    <div class="page__body">
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content">
            <div class="table-scroll" id="listing"></div>
        </div>
        <!-- ] -->
    </div>
    <script>
        var totalRecords = 0;
    </script>