<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form layout--' . $formLayout);
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = '12';
$frm->setFormTagAttribute('onsubmit', 'setupContactReq(this); return(false);');
?>
<div class="card">
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title"><?php echo Label::getLabel('LBL_CONTACT_TEAM'); ?></h3>
        </div>
    </div>
    <div class="card-body">
        <?php echo $frm->getFormHtml(); ?>
    </div>
</div>