<?php if (!empty(current($availabilityData)['slots'])) { ?>
    <?php foreach ($availabilityData as $slots) { ?>
        <div class="timeslot-picker__head text-center">
            <h6 class="selected-day"><?php echo MyDate::showDate($dateForHeading); ?></h6>
        </div>
        <div class="timeslot-picker__body">
            <div class="calendar-timeslots" style="display:flex;">
                <?php foreach ($slots['slots'] as $key => $slot) { ?>
                    <label class="radio-option">
                        <input type="checkbox" onclick="cart.updateSlot(this);" name="slots[]" value="<?php echo $slot['start']; ?>">
                        <span class="calendar-time"><?php echo MyDate::showTime($slot['start']); ?></span>
                    </label>
            <?php } ?>
            </div>
        </div>
    <?php }
} else { ?>
    <div class="noslot-available">
        <h4><?php echo Label::getLabel('LBL_OOPS!'); ?></h4>
        <div>
            <?php echo Label::getLabel('LBL_NO_SLOT_AVAILABLE_FOR_THIS_DATE'); ?>
        </div>
    </div>
<?php } ?>