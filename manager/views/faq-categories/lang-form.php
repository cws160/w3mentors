<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$faqCatLangFrm->setFormTagAttribute('id', 'faqCat');
$faqCatLangFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$faqCatLangFrm->setFormTagAttribute('onsubmit', 'setupLang(this); return(false);');
$faqCatLangFrm->developerTags['colClassPrefix'] = 'col-md-';
$faqCatLangFrm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_Faq_Category_Setup'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="faqCatForm(<?php echo $faqcat_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            if ($faqcat_id > 0) {
                foreach ($languages as $langId => $langName) {
            ?>
                    <li><a class="lang-form-js <?php echo ($faqcat_lang_id == $langId) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $langId; ?>" onclick="faqCatLangForm(<?php echo $faqcat_id ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
            <?php
                }
            }
            ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $faqCatLangFrm->getFormHtml(); ?>
</div>