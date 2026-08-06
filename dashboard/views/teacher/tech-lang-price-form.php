<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$nextBtn = $frm->getField('nextBtn');
$nextBtn->addFieldTagAttribute('onclick', "setupLangPrice(this.form, true); return(false);");
$submitBtn = $frm->getField('btn_submit');
$submitBtn->addFieldTagAttribute('onclick', "setupLangPrice(this.form, false); return(false);");
$frm->addFormTagAttribute('class', 'form');
$slots = $frm->getField('slots');
$activeSlots = $slots->options;
$systemCurrency = MyUtility::getSystemCurrency();
?>
<div class="content-panel">
    <div class="content-panel__head">
        <h5><?php echo Label::getLabel('LBL_Manage_Prices'); ?></h5>
        <p class="mt-1 mb-0 style-italic">
            <?php echo Label::getLabel('LBL_MANAGE_PRICES_INFO_TEXT'); ?>
        </p>
        <p class="alert alert--small alert--info mt-3">
            <?php
            if (FatApp::getConfig('CONF_MANAGE_PRICES') == AppConstant::YES) {
                echo Label::getLabel('LBL_NOTE:_PRICES_ARE_MANAGED_BY_ADMIN_AND_IN_BASE_CURRENCY') . ' [' . $systemCurrency['currency_code'] . ']';
            } else {
                echo Label::getLabel('LBL_NOTE:_ENTER_ALL_PRICES_IN_BASE_CURRENCY') . ' [' . $systemCurrency['currency_code'] . ']';
            }
            ?>
        </p>
    </div>
    <div class="content-panel__body">
        <?php echo $frm->getFormTag(); ?>
        <div class="form__body p-0">
            <div class="pricing-panel">
                <div class="table-controls">
                    <table class="table-sticky-scroll">
                        <thead>
                            <tr class="table-controls__row ">
                                <th class="table-controls__colum first-child"><?php echo Label::getLabel('LBL_Subjects'); ?></th>
                                <th class=" table-controls__colum color1"><label class="position-relative"><?php echo Label::getLabel('LBL_HOURLY_PRICE'); ?></label></th>
                                <?php foreach ($slots->options as $slot => $label) { ?>
                                    <th class="table-controls__colum duration_<?php echo $slot; ?> <?php echo in_array($slot, $slots->value) ? 'is-selected' : 'color1' ?>">
                                        <label class="position-relative">
                                            <span class="checkbox">
                                                <input type="checkbox" name="slots[]" <?php echo in_array($slot, $slots->value) ? 'checked' : ''; ?> value="<?php echo $slot; ?>" onclick="updateStatus(this);"><i class="input-helper"></i>
                                            </span>
                                            <?php echo $label; ?>
                                        </label>
                                    </th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userLangs as $lang) { ?>
                                <tr class="table-controls__row has-price" style="height: 50px;">
                                    <td class="table-controls__colum first-child">
                                        <?php echo $frm->getFieldHtml('tlang_name[' . $lang['utlang_id'] . ']'); ?>
                                    </td>
                                    <td class="table-controls__colum color1">
                                        <div class="small-field">
                                            <?php if (FatApp::getConfig('CONF_MANAGE_PRICES') == AppConstant::YES) {
                                                echo MyUtility::formatSystemMoney($lang['utlang_price']);
                                            } else {
                                                echo $frm->getFieldHtml('utlang_price[' . $lang['utlang_id'] . ']');
                                            } ?>
                                        </div>
                                    </td>
                                    <?php foreach ($slots->options as $slot => $label) { ?>
                                        <td id="duration_<?php echo $lang['utlang_id']; ?>_<?php echo $slot; ?>" class="table-controls__colum duration_<?php echo $slot; ?> <?php echo in_array($slot, $slots->value) ? 'is-selected' : 'color1' ?>">
                                            <div class="small-field">
                                                <?php
                                                $langPrice = FatUtility::float(($frm->getField('utlang_price[' . $lang['utlang_id'] . ']'))->value);
                                                echo MyUtility::formatSystemMoney(MyUtility::slotPrice($langPrice, $slot));
                                                ?>
                                            </div>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="form__actions">
            <div class="d-flex align-items-center gap-1">
                <?php
                echo $submitBtn->getHTML();
                echo $nextBtn->getHTML();
                ?>
            </div>
        </div>
        </form>
        <?php echo $frm->getExternalJs(); ?>
    </div>
</div>
<script>
    var activeSlots = <?php echo json_encode(array_keys($activeSlots)); ?>;
    var currencySymbol = `<?php echo MyUtility::getCurrencySymbol() ?>`;
    confirmLabel = `<?php echo Label::getLabel('LBL_ARE_YOU_SURE_TO_UPDATE_THIS_PRICE!'); ?>`;
    (function() {
        updateStatus = function(slotFieldObj) {
            let slot = $(slotFieldObj).val();
            let isChecked = $(slotFieldObj).is(":checked");
            if (isChecked) {
                $('.duration_' + slot).removeClass('color1');
                $('.duration_' + slot).addClass('is-selected');
            } else {
                $('.duration_' + slot).removeClass('is-selected');
                $('.duration_' + slot).addClass('color1');
            }
        };
        updatePrice = function(fieldObj) {
            let price = $(fieldObj).val();
            let langID = $(fieldObj).data('lang_id');
            $.each(activeSlots, function(index, duration) {
                price = isNaN(price) ? 0 : price;
                $('#duration_' + langID + '_' + duration).find('.small-field').html(formatMoneySystem(price / 60 * duration));
            });
        };
    })();
</script>