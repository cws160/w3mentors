<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;

$fld = $frm->getField('certpl_image');
$fld->setFieldTagAttribute('onchange', 'setupMedia();');

$fld = $frm->getField('certpl_image');
$fld->htmlAfterField = '<div id="image-listing">';
$fld->htmlAfterField .= '<div class="row"><div class="col-md-4"><div class="logothumb"> <img src="' . MyUtility::makeUrl('image', 'show', [Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE, $certId, Afile::SIZE_SMALL, $langId], CONF_WEBROOT_BACKEND) . '?' . time() . '"> </div></div></div>';

$fld->htmlAfterField .= '</div>';
$fld->htmlAfterField .= '<div style="margin-top:15px;" >' . str_replace('{dimensions}', implode('x', $dimensions), Label::getLabel('LBL_PREFERRED_DIMENSIONS_{dimensions}')) . '</div>';
$fld->htmlAfterField .= '<div style="margin-top:15px;">' . str_replace('{ext}', $imageExts, Label::getLabel('LBL_ALLOWED_FILE_EXTS_{ext}')) . '</div>';


$fld = $frm->getField('certpl_lang_id');
$fld->setFieldTagAttribute('onchange', 'mediaForm("' . $certTplCpde . '", this.value); return false;');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_CERTIFICATE_SETUP'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li>
                <a class="" href="javascript:void(0)" onclick="edit('<?php echo $certTplCpde ?>', '<?php echo $langId ?>');"><?php echo Label::getLabel('LBL_General'); ?></a>
            </li>
            <li>
                <a class="active" href="javascript:void(0)" onclick="mediaForm('<?php echo $certTplCpde ?>', '<?php echo $langId ?>');"><?php echo Label::getLabel('LBL_Media'); ?></a>
            </li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>