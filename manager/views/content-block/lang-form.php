<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$blockLangFrm->setFormTagAttribute('class', 'form layout--' . $formLayout);
$blockLangFrm->setFormTagAttribute('onsubmit', 'setupBlockLang(this); return(false);');
$blockLangFrm->developerTags['colClassPrefix'] = 'col-md-';
$blockLangFrm->developerTags['fld_default_col'] = 12;
if ($epageData['epage_editable'] == AppConstant::YES) {
    $edFld = $blockLangFrm->getField('epage_content');
    $edFld->htmlBeforeField = '<br/><a class="btn btn-primary btn-outline-brand" onClick="resetToDefaultContent();" href="javascript:void(0)">' . Label::getLabel('LBL_Reset_Editor_Content_to_default', $epage_lang_id) . '</a>';
}
?>
<!-- editor's default content[ -->
<div id="editor_default_content" style="display:none;">
    <?php echo (isset($epageData)) ? CommonHelper::renderHtml($epageData['epage_default_content']) : ''; ?>
</div>
<!-- ] -->

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Content_Block_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0)" onclick="addBlockForm(<?php echo $epage_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php foreach ($languages as $langId => $langName) { ?>
                <li>
                    <a class="<?php echo ($epage_lang_id == $langId) ? 'active' : '' ?>" data-id="<?php echo $langId; ?>" href="javascript:void(0);" onclick="langForm(<?php echo $epage_id ?>, <?php echo $langId; ?>);">
                        <?php echo $langName; ?>
                    </a>
                </li>
            <?php } ?>
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