<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-sm-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'setupTeacherQualification(this); return(false);');
$fldExpType = $frm->getField('uqualification_experience_type');
$fldExpType->developerTags['col'] = 6;
$fldUqualificationTitle = $frm->getField('uqualification_title');
$fldUqualificationTitle->developerTags['col'] = 6;
$fldUqualificationInstituteName = $frm->getField('uqualification_institute_name');
$fldUqualificationInstituteName->developerTags['col'] = 6;
$fldUqualificationInstituteAddress = $frm->getField('uqualification_institute_address');
$fldUqualificationInstituteAddress->developerTags['col'] = 6;
$fldUqualificationStartYear = $frm->getField('uqualification_start_year');
$fldUqualificationStartYear->developerTags['col'] = 6;
$fldUqualificationEndYear = $frm->getField('uqualification_end_year');
$fldUqualificationEndYear->developerTags['col'] = 6;
$fldCertificate = $frm->getField('certificate');
$fldCertificate->developerTags['col'] = 6;
$exts = implode(", ", Afile::getAllowedExts(Afile::TYPE_USER_QUALIFICATION_FILE));
$fileSize = Afile::getAllowedUploadSize(Afile::TYPE_USER_QUALIFICATION_FILE);
$fileSize = MyUtility::convertBitesToMb($fileSize) . ' ' . Label::getLabel('LBL_MB');
$label = str_replace(['{size}', '{ext}'], [$fileSize, $exts], Label::getLabel('LBL_CERTIFICATE_MAX_SIZE_{size}_AND_ALLOWED_EXT_{ext}'));
$fldCertificate->htmlAfterField = "<small>" . $label . "</small>";
$fldBtnSubmit = $frm->getField('btn_submit');
$fldBtnSubmit->developerTags['col'] = 6;

?>
<div class="modal-header">
    <h5><?php
        $heading = Label::getLabel('LBL_{type}_YOUR_EXPERIENCE');
        $type = ($qualificationId > 0) ? Label::getLabel('LBL_EDIT') :  Label::getLabel('LBL_ADD');
        echo str_replace(['{type}'], [$type], $heading);
        ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $frm->getFormHtml(); ?>
</div>