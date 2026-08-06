<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$langFrm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'langSetup(this); return(false);');
$langFrm->developerTags['colClassPrefix'] = 'col-md-';
$langFrm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Coupon_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li><a href="javascript:void(0);" onclick="form(<?php echo $couponId ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($couponId == 0) ? 'is-inactive' : '';
            if ($couponId > 0) {
                foreach ($languages as $langId => $langName) {
            ?>
                    <li><a class="<?php echo ($coupon_lang_id == $langId) ? 'active' : '' ?>" href="javascript:void(0);" onclick="langForm(<?php echo $couponId ?>, <?php echo $langId; ?>);"><?php echo $langName; ?></a></li>
                <?php } ?>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $langFrm->getFormHtml(); ?>
</div>