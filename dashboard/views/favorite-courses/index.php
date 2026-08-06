<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-7">
                <h1> <?php echo Label::getLabel('LBL_MY_FAVORITE_COURSES'); ?></h1>
            </div>
        </div>
        <!-- [ FILTERS ========= -->
        <div class="search-filter slide-target-js">
            <?php
            $frm->setFormTagAttribute('class', 'd-none');
            echo $frm->getFormHtml();
            ?>
        </div>
        <!-- ] ========= -->
    </div>
    <div class="page__body">
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content" id="listItems"></div>
        <!-- ] -->
    </div>