<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$testimonialMediaFrm->setFormTagAttribute('class', 'form form_horizontal');
$testimonialMediaFrm->developerTags['colClassPrefix'] = 'col-md-';
$testimonialMediaFrm->developerTags['fld_default_col'] = 12;
$fld2 = $testimonialMediaFrm->getField('testimonial_image');
$fld2->addFieldTagAttribute('class', 'btn btn--primary btn--sm m-0');
$preferredDimensionsStr = '<small class="text--small">' . sprintf(Label::getLabel('LBL_Preferred_Dimensions_%s'), '275 × 275') . '</small>';
$htmlAfterField = $preferredDimensionsStr;
if (!empty($testimonialImg)) {
    $htmlAfterField .= '<div class="image-listing row g-4">';
    $htmlAfterField .= '<div class="col-md-4"><div class="ratio ratio-1x1 uploaded-media"><img src="' . MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_TESTIMONIAL_IMAGE, $testimonialImg['file_record_id'], Afile::SIZE_SMALL]) . '?' . time() . '"> <a href="javascript:void(0);" onClick="removeTestimonialImage(' . $testimonialImg['file_record_id'] . ',' . $testimonialImg['file_lang_id'] . ')" class="remove--img"><i class="ion-close-round"></i></a></div>';
    $htmlAfterField .= '</div></div>';
}
$fld2->htmlAfterField = $htmlAfterField;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Testimonial_Media_setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li><a href="javascript:void(0)" onclick="editTestimonialForm(<?php echo $testimonialId ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($testimonialId == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" <?php if ($testimonialId > 0) { ?> onclick="editTestimonialLangForm(<?php echo $testimonialId ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $langName; ?></a></li>
            <?php } ?>
            <li><a class="active" href="javascript:void(0)" onclick="testimonialMediaForm(<?php echo $testimonialId ?>);"><?php echo Label::getLabel('LBL_Media'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $testimonialMediaFrm->getFormHtml(); ?>
</div>