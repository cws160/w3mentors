<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_ISSUE_REPORT_OPTIONS_SETUP'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a class="active" href="javascript:void(0)"><?php echo Label::getLabel('LBL_GENERAL'); ?></a></li>
            <?php
            $inactive = ($optId == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
                $langForm = (intval($optId) > 0) ? 'onclick="langForm(' . $optId . ',' . $langId . ');"' : '';
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php echo $langForm; ?>><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>