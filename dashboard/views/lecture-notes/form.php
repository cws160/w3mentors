<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupNotes(this); return(false);');
?>
<div class="modal-header">
    <h5 class="mb-0"><?php echo Label::getLabel('LBL_SETUP_NOTES'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormTag(); ?>
    <div class="row">
        <div class="col-md-12">
            <div class="field-set">
                <div class="caption-wraper">
                    <label class="field_label">
                        <?php
                        $fld = $frm->getField('lecnote_notes');
                        echo $fld->getCaption();
                        ?>
                        <span class="spn_must_field">*</span>
                    </label>
                </div>
                <div class="field-wraper">
                    <div class="field_cover">
                        <?php echo $fld->getHtml(); ?>
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
                        echo $frm->getFieldHtml('btn_submit');
                        $fld = $frm->getField('btn_cancel');
                        $fld->setFieldTagAttribute('onclick', "$.w3mentorsmodal.close();");
                        echo $fld->getHtml();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    echo $frm->getFieldHtml('lecnote_course_id');
    echo $frm->getFieldHtml('lecnote_lecture_id');
    echo $frm->getFieldHtml('lecnote_ordcrs_id');
    echo $frm->getFieldHtml('lecnote_id');
    ?>
    </form>
    <?php echo $frm->getExternalJS(); ?>
</div>