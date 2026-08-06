<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$idFld = $frm->getField('tlang_id');
$idFld->addFieldTagAttribute('id', 'tlang_id');
$tlangParentFld = $frm->getField('tlang_parent');
$tlangParentFld->addFieldTagAttribute('onchange', 'updateFeatured(this.value)');
$fldFeatured = $frm->getField('tlang_featured');
$fldFeatured->setWrapperAttribute('class', 'fldFeaturedJs');
$tLangId = $idFld->value;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_TEACHING_LANGUAGE_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li><a class="active" href="javascript:void(0);" ><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php
            $inactive = ($tLangId == 0) ? 'is-inactive' : '';
            $mediaForm = ($tLangId > 0) ? 'onclick="mediaForm(' . $tLangId . ');"' : '';
            foreach ($languages as $langId => $langName) {
                $langForm = (intval($tLangId) > 0) ? 'onclick="langForm(' . $tLangId . ',' . $langId . ');"' : '';
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php echo $langForm; ?>><?php echo $langName; ?></a></li>
            <?php } ?>
            <?php if($canUploadMedia){ ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" <?php echo $mediaForm; ?>><?php echo Label::getLabel('LBL_MEDIA'); ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>