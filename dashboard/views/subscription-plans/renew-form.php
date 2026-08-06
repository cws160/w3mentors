<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'cancelSetup(this); return(false);');
$subId = $frm->getField('ordsub_id')->value;
$frm->addButton('', 'btn_renew', Label::getLabel('LBL_RENEW'));
$frm->addResetButton('', 'btn_upgrade', Label::getLabel('LBL_UPGRADE'));
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_RENEW_SUBSCRIPTION'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-sm-12">
            <div class="field-set mb-0">
                <div class="field-wraper form-buttons-group">
                    <div class="field_cover">
                        <h5><?php echo Label::getLabel('LBL_DO_YOU_WANT_TO_RENEW_OR_UPGRADE'); ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="field-set mb-0">
                <div class="field-wraper form-buttons-group">
                    <div class="field_cover">
                        <?php
                        $fldRenew = $frm->getField('btn_renew');
                        $fldRenew->setFieldTagAttribute('onclick', "renew(" . $subId . ");");
                        echo $fldRenew->getHtml();
                        $fldupgrade = $frm->getField('btn_upgrade');
                        $fldupgrade->setFieldTagAttribute('onclick', "upgrade(" . $subId . ");");
                        echo $fldupgrade->getHtml();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>