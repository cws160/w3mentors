<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'courseFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'submitImportUploadedCourse(this); return(false);');

$reviewFrm->setFormTagAttribute('id', 'reviewFrm');
$reviewFrm->setFormTagAttribute('class', 'form');
$reviewFrm->developerTags['colClassPrefix'] = 'col-md-';
$reviewFrm->developerTags['fld_default_col'] = 12;
$reviewFrm->setFormTagAttribute('onsubmit', 'submitImportUploadedReviews(this); return(false);');
?>
<div class="col-lg-8 col-md-8 col-sm-8">
    <div class="card">
        <div class="card-body">
            <div class="repeated-row">
                <h5><?php echo Label::getLabel('LBL_COURSE_IMPORT'); ?></h5>
                <?php echo $frm->getFormHtml(); ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="repeated-row">
                <h5><?php echo Label::getLabel('LBL_REVIEWS_IMPORT'); ?></h5>
                <?php echo $reviewFrm->getFormHtml(); ?>
            </div>
        </div>
    </div>
</div>