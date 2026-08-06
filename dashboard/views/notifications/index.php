<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_My_Notifications'); ?></h1>
                <?php echo $frm->getFormHtml(); ?>
            </div>
        </div>
    </div>
    <div class="page__body">
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content" id="ordersListing"></div>
        <!-- ] -->
    </div>