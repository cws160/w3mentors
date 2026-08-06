<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'setupLangFaq(' . $langFrm->getFormTagAttribute('name') . '); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Faq_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="editFaqForm(<?php echo $faqId ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            if ($faqId > 0) {
                foreach ($languages as $langId => $langName) {
            ?>
                    <li><a class="lang-form-js <?php echo ($lang_id == $langId) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $langId; ?>" onclick="editFaqLangForm(<?php echo $faqId ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
            <?php
                }
            }
            ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $langFrm->getFormHtml(); ?>
</div>