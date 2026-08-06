<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupCurrency(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
if ($defaultCurrency) {
    $fld = $frm->getField('currency_value');
    $fld->setFieldTagAttribute('disabled', true);
    $fld->htmlAfterField = '<small>' . Label::getLabel('LBL_THIS_IS_YOUR_DEFAULT_CURRENCY') . '</small>';
    $frm->getField('currency_code')->setFieldTagAttribute('disabled', true);
    $frm->getField('currency_active')->setFieldTagAttribute('disabled', true);
}
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_Currency_Setup'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a class="active" href="javascript:void(0)" onclick="currencyForm(<?php echo $currency_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($currency_id == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php if ($currency_id > 0) { ?> onclick="editCurrencyLangForm(<?php echo $currency_id ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>