<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$blockLangFrm->setFormTagAttribute('class', 'form layout--' . $formLayout);
$blockLangFrm->developerTags['colClassPrefix'] = 'col-md-';
$blockLangFrm->developerTags['fld_default_col'] = 12;
if ($cpage_layout == ContentPage::CONTENT_PAGE_LAYOUT1_TYPE) {
    $fld = $blockLangFrm->getField('cpage_bg_image');
    $fld->addFieldTagAttribute('class', 'btn btn--primary btn--sm m-0');
    $label = Label::getLabel('LBL_PREFERRED_DIMENSIONS_{DIMENSIONS}');
    $dimensions = implode('x', (new Afile(Afile::TYPE_CPAGE_BACKGROUND_IMAGE))->getImageSizes('LARGE'));
    $label = str_replace('{dimensions}', $dimensions, $label);
    $preferredDimensionsStr = '<small class="text--small"> ' . Label::getLabel('LBL_This_will_be_displayed_on_your_cms_Page') . '<br> ' . $label . '</small>';
    $htmlAfterField = $preferredDimensionsStr;
    if (!empty($bgImage)) {
        $htmlAfterField .= '<div class="image-div-js"><div class="image-listing row">';
        $htmlAfterField .= '<div class="col-md-6"><div class="uploaded--image"><img src="' . MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_CPAGE_BACKGROUND_IMAGE, $cpage_id, Afile::SIZE_LARGE, $cpage_lang_id]) . '" class="bg-image-js"> <a href="javascript:void(0);" onClick="removeBgImage(' . $bgImage['file_record_id'] . ',' . $bgImage['file_lang_id'] . ',' . $cpage_layout . ')" class="remove--img"><i class="ion-close-round"></i></a></div>';
        $htmlAfterField .= '</div></div></div>';
    } else {
        $htmlAfterField .= '<div class="hide image-div-js"><div class="image-listing row"><div class="col-md-6"><div class="uploaded--image"></div></div></div></div>';
    }
    $fld->htmlAfterField = $htmlAfterField;
}
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Content_Pages_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="addForm(<?php echo $cpage_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            if ($cpage_id > 0) {
                foreach ($languages as $langId => $langName) {
            ?>
                    <li><a class="<?php echo ($cpage_lang_id == $langId) ? 'active' : '' ?>" data-id="<?php echo $langId; ?>" href="javascript:void(0);" onclick="langForm(<?php echo $cpage_id ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
            <?php
                }
            }
            ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php
    echo $blockLangFrm->getFormTag();
    echo $blockLangFrm->getFormHtml(false);
    echo '</form>';
    ?>
</div>