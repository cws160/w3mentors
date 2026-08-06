<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$fld = $frm->getField('coupon_discount_type');
$fld->setFieldTagAttribute('onchange', 'toggleMaxDiscount(this.value);');

$fld = $frm->getField('coupon_max_discount');
$fld->addFieldTagAttribute('id', 'coupon_max_discount');
$fld->setWrapperAttribute('id', 'coupon_max_discount_div');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_COUPON_SETUP'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul>
            <li><a class="active" href="javascript:void(0)" onclick="form(<?php echo $couponId; ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($couponId == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" <?php if ($couponId > 0) { ?> onclick="langForm(<?php echo $couponId ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>

<script>
    $(document).ready(function() {
        bindDatetimePicker(".dateTimeFld");
    });
</script>