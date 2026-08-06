<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$pageLangFrm->setFormTagAttribute('class', 'form layout--' . $formLayout);
#$pageLangFrm->setFormTagAttribute('onsubmit', 'setupLangPageData(this); return(false);');
$pageLangFrm->setFormTagAttribute('id', 'page-lang-data');
$pageLangFrm->developerTags['colClassPrefix'] = 'col-md-';
$pageLangFrm->developerTags['fld_default_col'] = 12;
$edFld = $pageLangFrm->getField('plang_helping_text');
$edFld->htmlBeforeField = '<br/><a class="btn btn-primary btn-outline-brand" onClick="resetToDefaultContent();" href="javascript:void(0)">' . Label::getLabel('LBL_Reset_Editor_Content_to_default') . '</a>';
$fld = $pageLangFrm->getField('plang_key');
$fld->addFieldTagAttribute("disabled", true);
?>
<!-- editor's default content[ -->
<div id="editor_default_content" style="display:none;">
    <?php echo (isset($defaultContent['pdata_helping_text'])) ? html_entity_decode($defaultContent['pdata_helping_text']) : ''; ?>
</div>
<!-- ] -->

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_PAGE_LANG_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <?php foreach ($languages as $langId => $langName) { ?>
                <li>
                    <a class="<?php echo ($plang_lang_id == $langId) ? 'active' : '' ?>" data-id="<?php echo $langId; ?>" href="javascript:void(0);" onclick="langForm(<?php echo $plang_id ?>, <?php echo $langId; ?>);">
                        <?php echo $langName; ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php
    echo $pageLangFrm->getFormTag();
    echo $pageLangFrm->getFormHtml(false);
    echo '</form>';
    ?>
</div>