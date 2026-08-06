<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'txnfeeSetup(this); return(false);');
$frm->setFormTagAttribute('id', 'gatewayFeeForm');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>

<div class="card">
    <div class="card-head" id="pmFeesectionhead-js">
        <div class="card-head-label">
            <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Method_Fee_Setups'); ?></h3>
        </div>
    </div>
    <div class="card-body">
        <div id="pmFeeForm-js">
            <?php echo $frm->getFormHtml(); ?>
        </div>
    </div>
</div>