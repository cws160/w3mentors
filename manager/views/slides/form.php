<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$slideFrm->setFormTagAttribute('class', 'form form_horizontal');
$slideFrm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$slideFrm->developerTags['colClassPrefix'] = 'col-md-';
$slideFrm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_SLIDE_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a class="active" href="javascript:void(0)" onclick="slideForm(<?php echo $slide_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($slide_id == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
            ?>
                <li class=" lang-li-js <?php echo $inactive; ?>">
                    <a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php if ($slide_id > 0) { ?>  onclick="slideMediaForm(<?php echo $slide_id; ?>, <?php echo $langId ?>);" <?php } ?>>
                        <?php echo $langName; ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $slideFrm->getFormHtml(); ?>
</div>