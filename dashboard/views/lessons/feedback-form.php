<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form--horizontal web_form');
$frm->setFormTagAttribute('onsubmit', 'feedbackSetup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_LESSON_FEEDBACK'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>
<div class="gap"></div>

<script>
    $(document).ready(function() {
        $('.star-rating').barrating({
            showSelectedRating: false,
            deselectable: false
        });
    });
</script>