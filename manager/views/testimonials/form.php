<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupTestimonial(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Testimonial_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a class="active" href="javascript:void(0)" onclick="editTestimonialForm(<?php echo $testimonial_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($testimonial_id == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php if ($testimonial_id > 0) { ?> onclick="editTestimonialLangForm(<?php echo $testimonial_id ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $langName; ?></a></li>
            <?php } ?>
            <li data-id="media" class="<?php echo $inactive; ?>"><a href="javascript:void(0);" <?php if ($testimonial_id > 0) { ?> onclick="testimonialMediaForm(<?php echo $testimonial_id ?>);" <?php } ?>><?php echo Label::getLabel('LBL_Media'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="card-body">
    <?php echo $frm->getFormHtml(); ?>
</div>