<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (empty($iconData)) {
    $frm->getField('icon')->requirement->setRequired();
} else {
    $icon_img_fld = $frm->getField('icon_img');
    $icon_img_fld->value = '<img src="' . MyUtility::makeUrl('Image', 'show', [Afile::TYPE_PWA_APP_ICON, 0, Afile::SIZE_SMALL]) . '" alt="' . Label::getLabel('LBL_App Icon') . '">';
}
if (!$canEdit) {
    $submitBtn = $frm->getField('btn_submit');
    $frm->removeField($submitBtn);
}
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->developerTags = ['colClassPrefix' => 'col-md-', 'fld_default_col' => 12];
$frm->getField('pwa_settings[background_color]')->overrideFldType('color');
$frm->getField('pwa_settings[theme_color]')->overrideFldType('color');
$frm->setFormTagAttribute('onsubmit', 'pwaSetup(this); return(false);');
?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="box -padding-20">
                    <?php echo $frm->getFormHtml(); ?>
                </div>
            </div>
        </div>
    </div>
</main>