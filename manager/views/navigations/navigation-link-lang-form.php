<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'setupNavigationLinksLang(this); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_navigation_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="navigationLinkForm(<?php echo $nav_id . ',' . $nlink_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            if ($nlink_id > 0) {
                foreach ($languages as $langId => $langName) {
            ?>
                    <li><a class="lang-form-js <?php echo ($nav_lang_id == $langId) ? 'active' : '' ?>" data-id="<?php echo $langId; ?>" href="javascript:void(0);" onclick="navigationLinkLangForm(<?php echo $nav_id ?>, <?php echo $nlink_id; ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
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