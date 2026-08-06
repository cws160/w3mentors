<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$typeFld = $frm->getField('prefer_type');
$typeFld->addFieldTagAttribute('class', 'hide');
$typeFld->setWrapperAttribute('class', 'hide');
$preferenceId = $frm->getField('prefer_id')->value;
$preferenceType = $frm->getField('prefer_type')->value;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_PREFERENCE_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li><a class="active" href="javascript:void(0)" onclick="preferenceForm(<?php echo $preferenceId ?>,<?php echo $preferenceType ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($preferId == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
                $langForm = (intval($preferenceId) > 0) ? 'onclick="langForm(' . $preferenceId . ',' . $langId . ');"' : '';
            ?>
                <li class=" lang-li-js <?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php echo $langForm; ?>><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>