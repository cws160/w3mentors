<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if(empty(current($availabilityData)['slots'])) { ?>
    <div class="noslot-available">
        <h4><?php echo Label::getLabel('LBL_OOPS!'); ?></h4>
        <div>
            <?php echo Label::getLabel('LBL_NO_SLOT_AVAILABLE_FOR_THIS_DATE'); ?>
        </div>
    </div>
<?php } else { ?>
    <div class="timeslots">
        <?php foreach ($availabilityData as $slots) { 
            foreach($slots['slots'] as $key => $slot) { ?>
                <div class="timeslot">
                    <?php echo MyDate::showTime($slot['start']); ?>
                </div>
        <?php }
        } ?>
    </div>
<?php } ?>