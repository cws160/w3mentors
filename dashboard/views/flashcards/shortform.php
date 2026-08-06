<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'flashcardSetup(this); return(false);');
$title = $frm->getField('flashcard_title');
$title->addFieldTagAttribute('placeholder', Label::getLabel('LBL_TITLE'));
$detail = $frm->getField('flashcard_detail');
$detail->addFieldTagAttribute('placeholder', Label::getLabel('LBL_DETAIL'));
$btnSubmit = $frm->getField('btn_submit');
$btnCancel = $frm->getField('btn_cancel');
$btnSubmit->addFieldTagAttribute('style', 'margin-bottom:10px;');
$btnCancel->addFieldTagAttribute('onclick', 'flashcardCancel();');
?>
<div class="flash-card">
<p class="bold-600 color-black"><?php echo Label::getLabel('LBL_SETUP_FLASHCARD'); ?></p>
<?php echo $frm->getFormTag(); ?>
<div class="row">
    <div class="col-md-12">
        <div class="field-set">
            <div class="field-wraper">
                <div class="field_cover"><?php echo $title->getHtml(); ?></div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="field-set">
            <div class="field-wraper">
                <div class="field_cover">
                    <div class="field_cover"><?php echo $detail->getHtml(); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="field-set">
            <div class="field-wraper">
                <div class="field_cover">
                    <?php echo $btnSubmit->getHtml(); ?>
                    <?php echo $btnCancel->getHtml(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo $frm->getFieldHtml('flashcard_id'); ?>
</form>
<?php echo $frm->getExternalJS(); ?>
</div>