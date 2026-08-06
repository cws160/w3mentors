<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('id', 'frmCourses');
$frm->setFormTagAttribute('onsubmit', 'setupPrice(this); return false;');
$typeFld = $frm->getField('course_type');
$currencyFld = $frm->getField('course_currency_id');
$priceFld = $frm->getField('course_price');
?>
<?php echo $frm->getFormTag(); ?>
<div class="page-layout">
    <div class="page-layout__small">
        <?php echo $this->includeTemplate('courses/sidebar.php', ['frm' => $frm, 'active' => 3, 'courseId' => $courseId]) ?>
    </div>
    <div class="page-layout__large">
        <div class="box-panel">
            <div class="box-panel__head border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4><?php echo Label::getLabel('LBL_MANAGE_PRICING'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="box-panel__body">
                <div class="box-panel__container">
                    <div class="box-min-height">
                        <p><?php echo Label::getLabel('LBL_PRICING_SHORT_INFO_1'); ?></p>
                        <p><?php echo Label::getLabel('LBL_PRICING_SHORT_INFO_2'); ?></p>
                        <div class="max-width-80 mt-5">
                            <div class="form form--horizontal">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label">
                                                    <?php echo $typeFld->getCaption(); ?>
                                                    <span class="spn_must_field">*</span>
                                                </label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <ul class="list-inline list-inline--onehalf">
                                                        <?php
                                                        $selected = ($typeFld->value > 0) ? $typeFld->value : Course::TYPE_PAID;
                                                        foreach ($typeFld->options as $val => $option) { ?>
                                                            <li>
                                                                <label>
                                                                    <span class="radio">
                                                                        <input type="radio" <?php echo ($selected == $val) ? 'checked="checked"' : '' ?> data-fatreq='{"required":true}' onchange="updatePriceForm(this.value);" name="course_type" value="<?php echo $val; ?>">
                                                                        <i class="input-helper"></i>
                                                                    </span>
                                                                    <?php echo $option; ?>
                                                                </label>
                                                            </li><?php
                                                                }
                                                                    ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row reqFldsJs">
                                    <div class="col-md-12">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label">
                                                    <?php echo $currencyFld->getCaption(); ?>
                                                    <span class="spn_must_field">*</span>
                                                </label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <?php echo $currencyFld->getHtml('course_currency_id'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row reqFldsJs">
                                    <div class="col-md-12">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label">
                                                    <?php echo $priceFld->getCaption(); ?>
                                                    <span class="spn_must_field">*</span>
                                                </label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <?php echo $priceFld->getHtml('course_price'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $frm->getFieldHtml('course_id'); ?>
</form>
<?php echo $frm->getExternalJS(); ?>
<script>
    var TYPE_FREE = "<?php echo Course::TYPE_FREE; ?>";
    var TYPE_PAID = "<?php echo Course::TYPE_PAID; ?>";
</script>