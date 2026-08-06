<?php defined('SYSTEM_INIT') or die('INVALID USAGE.'); ?>
<?php
$min = 1;
$max = 99;
if (!empty($activePlan)) {
    $max = $activePlan['subplan_lesson_count'] - $activePlan['ordsplan_used_lesson_count'];
}
if (!$max) {
    $min = 0;
}
?>
<script>
    cart.prop.ordles_duration = parseInt('<?php echo $duration; ?>');
    cart.selectLanguage(parseInt('<?php echo $tlangId; ?>'));
</script>
<div class="modal-header modal-header--checkout">
    <h4 class="flex-1 text-center"><?php echo Label::getLabel('LBL_SELECT_LANGUAGE_AND_DURATION'); ?></h4>
    <button type="button" class="btn-close w3mentorsmodalJs close-checkout-modal" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="chekout-form">
        <form class="form form--checkout" action="javascript:void(0);">
            <div class="row">
                <!-- [ LANGUAGE SELECTION ========= -->
                <div class="col-md-6">
                    <div class="form-group">
                        <select class="form-control" id="ordles_tlang_id" name="ordles_tlang_id" onchange="cart.selectLanguage(this.value);">
                            <?php foreach ($langslots as $key => $langslot) { ?>
                                <option value="<?php echo $key; ?>" <?php echo ($tlangId == $key) ? 'selected' : ''; ?>><?php echo $langslot['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <!-- ] -->
                <!-- [ TIME SLOTS SELECTION ========= -->
                <div class="col-md-6">
                    <div class="form-group">
                        <select class="form-control" id="ordles_duration" name="ordles_duration" onchange="cart.selectDuration(this.value);" <?php echo (!empty($activePlan)) ? 'disabled': ''; ?>>
                            <?php
                            $counter = 0;
                            foreach ($langslots as $key => $langslot) {
                                foreach ($langslot['slots'] as $slot) {
                                    if (!empty($activePlan) &&  $duration != $slot) {
                                        continue;
                                    }
                                    if ($counter == 0) { ?>
                                        <option value="<?php echo $slot; ?>" <?php echo ($duration == $slot) ? 'selected' : ''; ?>><?php echo str_replace('{slot}', $slot, Label::getLabel('LBL_{slot}_MINUTE_LESSON')); ?></option>
                                <?php }
                                }
                                $counter++;
                            } ?>
                        </select>
                    </div>
                </div>
                <!-- ] -->
            </div>
            <div class="row justify-content-center g-4">
                <!-- [ QUANTITY FIELD ========= -->
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="cart-qty form-control">
                            <button class="cart-qty__update decrease" onclick="cart.updateQuantity('-')"></button>
                            <input class="cart-qty__value" type="text" name="ordles_quantity" onchange="cart.prop.ordles_quantity = parseInt(this.value)" min="<?php echo $min; ?>" max="<?php echo $max; ?>" value="<?php echo $quantity; ?>" readonly="readonly" />
                            <button class="cart-qty__update increase" onclick="cart.updateQuantity('+')"></button>
                        </div>
                    </div>
                </div>
                <!-- ] -->
                <!-- [ RECURRING BUY ========= -->
                <?php if (empty($activePlan)) { ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="selector-switch__control">
                                <span class="selector-switch__label"><?php echo Label::getLabel('LBL_RECURRING_BUY'); ?></span>
                                <span class="selector-switch__action">
                                    <span class="switch switch--small">
                                        <input class="switch__label" type="checkbox" name="ordles_type" onclick="cart.selectSubscription();" value="<?php echo Lesson::TYPE_SUBCRIP; ?>" <?php echo (Lesson::TYPE_SUBCRIP == $ordlesType) ? 'checked' : ''; ?> />
                                        <i class="switch__handle bg-green"></i>
                                    </span>
                                </span>
                            </label>
                            <span class="selector-switch__info"><?php echo Label::getLabel('LBL_REPEAT_ON') . ' ' .  str_replace('{number}', $subWeek, Label::getLabel('LBL_EVERY_{NUMBER}_WEEKS')); ?>
                                <span class="selector-switch__info-media is-hover">
                                    <svg class="icon icon--small" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M8,15a7,7,0,1,1,7-7,7,7,0,0,1-7,7m0,1A8,8,0,1,0,0,8a8,8,0,0,0,8,8" />
                                        <path d="M8.93,6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738,3.468c-.194.9.105,1.319.808,1.319A2.071,2.071,0,0,0,8.831,12l.088-.416a1.108,1.108,0,0,1-.686.246c-.275,0-.375-.193-.3-.533ZM9,4.5a1,1,0,1,1-1-1,1,1,0,0,1,1,1" />
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_SUBSCRIPTION_HELP_TEXT'); ?></div>
                                </span>
                            </span>
                        </div>
                    </div>
                <?php } ?>
                <!-- ] -->
                <!-- [ OFFLINE LESSON ========= -->
                <?php if (User::offlineSessionsEnabled($teacher['user_id'])) { ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="selector-switch__control">
                                <span class="selector-switch__label"><?php echo Label::getLabel('LBL_OFFLINE_LESSON'); ?></span>
                                <span class="selector-switch__action">
                                    <span class="switch switch--small">
                                        <input class="switch__label" type="checkbox" name="ordles_offline" onclick="cart.selectOfflineSession('<?php echo $address['usradd_id']; ?>');" value="1" <?php echo ($ordlesOffline) ? 'checked' : ''; ?> />
                                        <i class="switch__handle bg-green"></i>
                                    </span>
                                </span>
                            </label>
                            <span class="selector-switch__info">
                                <?php echo Label::getLabel('LBL_SEE_ADDRESS_INFO'); ?>
                                <span class="selector-switch__info-media is-hover">
                                    <svg class="icon icon--small" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                        <path d="M8,15a7,7,0,1,1,7-7,7,7,0,0,1-7,7m0,1A8,8,0,1,0,0,8a8,8,0,0,0,8,8" />
                                        <path d="M8.93,6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738,3.468c-.194.9.105,1.319.808,1.319A2.071,2.071,0,0,0,8.831,12l.088-.416a1.108,1.108,0,0,1-.686.246c-.275,0-.375-.193-.3-.533ZM9,4.5a1,1,0,1,1-1-1,1,1,0,0,1,1,1" />
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo UserAddresses::format($address); ?></div>
                                </span>
                            </span>
                        </div>
                    </div>
                <?php } ?>
                <!-- ] -->
            </div>
        </form>
    </div>
</div>
<div class="modal-footer">
    <div class="row justify-content-center align-items-center gap-md-5">
        <?php if (empty($activePlan)) { ?>
            <div class="col-auto">
                <div class="cart-price">
                    <span class="cart-price__label"><?php echo Label::getLabel('LBL_TOTAL_PRICE'); ?> :</span>
                    <span class="cart-price__value" id="price-js"></span>
                </div>
            </div>
        <?php } ?>
        <div class="col-auto">
            <button onclick="cart.viewCalendar('<?php echo $teacher['user_id']; ?>', cart.prop.ordles_tlang_id, cart.prop.ordles_duration, cart.prop.ordles_quantity, cart.prop.ordles_type, cart.prop.ordles_offline);" class="btn btn--primary color-white"><?php echo LabeL::getLabel('LBL_NEXT'); ?></button>
        </div>
    </div>
</div>

<script>
    LESSON_TYPE_REGULAR = '<?php echo Lesson::TYPE_REGULAR; ?>';
    LESSON_TYPE_SUBCRIP = '<?php echo Lesson::TYPE_SUBCRIP; ?>';
    cart.prop.ordles_quantity = parseInt('<?php echo $quantity; ?>');
    cart.prop.ordles_type = parseInt('<?php echo $ordlesType; ?>');
    cart.prop.ordles_offline = parseInt('<?php echo $ordlesOffline; ?>');
    var minValue = <?php echo $min; ?>;
    var maxValue = <?php echo $max; ?>;
</script>