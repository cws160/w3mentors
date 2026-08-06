<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$bibleLangFrm->setFormTagAttribute('onsubmit', 'setupLang(this); return(false);');
$bibleLangFrm->developerTags['colClassPrefix'] = 'col-md-';
$bibleLangFrm->developerTags['fld_default_col'] = 12;
$bibleLangFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_VIDEO_CONTENT') ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="addForm(<?php echo $biblecontent_id ?>);"><?php echo Label::getLabel('LBL_GENERAL') ?></a></li>
            <?php
            if ($biblecontent_id > 0) {
                foreach ($languages as $langId => $langName) {
            ?>
                    <li><a class="lang-form-js <?php echo ($bible_lang_id == $langId) ? 'active' : '' ?>" href="javascript:void(0);" data-id="<?php echo $langId; ?>" onclick="addLangForm(<?php echo $biblecontent_id ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
            <?php
                }
            }
            ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php
    echo $bibleLangFrm->getFormTag();
    echo $bibleLangFrm->getFormHtml(false);
    echo '</form>';
    ?>
</div>