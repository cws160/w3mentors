<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$frmFavSrch->setFormTagAttribute('class', 'form form--small');
$frmFavSrch->setFormTagAttribute('onSubmit', 'searchfavorites(this); return false;');
$frmFavSrch->developerTags['colClassPrefix'] = 'col-md-';
$frmFavSrch->developerTags['fld_default_col'] = 4;
$fld = $frmFavSrch->getField('keyword');
$fld->setWrapperAttribute('class', 'col-md-5');
$fld->developerTags['col'] = 4;
?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1> <?php echo Label::getLabel('LBL_MY_FAVOURITE_TEACHERS'); ?></h1>
            </div>
        </div>
        <!-- [ FILTERS ========= -->
        <div class="search-filter slide-target-js">
            <?php echo $frmFavSrch->getFormHtml(); ?>
        </div>
        <!-- ] ========= -->
    </div>
    <div class="page__body">
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content" id="listItems"></div>
        <!-- ] -->
    </div>