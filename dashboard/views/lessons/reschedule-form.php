<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$form->addFormTagAttribute('onsubmit', 'rescheduleSetup(this); return false;');
$form->setFormTagAttribute('id', 'reschedule');
$startTime = $form->getField('ordles_lesson_starttime');
$startTime->addFieldTagAttribute('id', 'lesson_starttime');
$endTime = $form->getField('ordles_lesson_endtime');
$endTime->addFieldTagAttribute('id', 'lesson_endtime');
$commentField = $form->getField('comment');
$commentField->addFieldTagAttribute('placeholder', Label::getLabel('LBL_RESCHEDULE_REASON_*'));
$commentField->addFieldTagAttribute('id', 'reschedule-reason-js');
$commentField->addFieldTagAttribute('form', 'reschedule');
$submit = $form->getField('submit');
if ($siteUserType == User::TEACHER) {
    $submit->addFieldTagAttribute('class', 'btn btn--primary');
} else {
    $submit->addFieldTagAttribute('class', 'btn btn--primary confirm-lesson-js btn--disabled');
    $submit->addFieldTagAttribute('disabled', 'disabled');
}
$quantity = 1;
if ($siteUserType == User::TEACHER) {
    echo $form->getFormTag();
?>
    <div class="modal-header modal-header--checkout">
        <h4 class="flex-1 text-center"><?php echo Label::getLabel('LBL_Request_Reschedule'); ?></h4>
        <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
    </div>
    <div class="modal-body p-0">
        <label class="field_label mt-3"><?php echo Label::getLabel('LBL_Reschedule_Reason'); ?><span class="spn_must_field">*</span></label>
        <div class="mt-2">
            <?php
            echo $commentField->getHTML();
            ?>
            <div class="d-none">
                <?php
                echo $form->getFieldHTML('ordles_id');
                echo $startTime->getHTML();
                echo $endTime->getHTML();
                ?>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <div class="row justify-content-center align-items-center gap-md-5">
            <?php echo $submit->getHTML(); ?>
        </div>
    </div>
    </form>
<?php
    echo $form->getExternalJS();
    return;
}
echo $form->getFormTag();
?>
<div id="loaderCalendar" class="calendar-loader" style="display: none;">
    <div class="loader"></div>
</div>
<div class="modal-header modal-header--checkout">
    <h4 class="flex-1 text-center"><?php echo Label::getLabel('LBL_RESCHEDULE_LESSON'); ?></h4>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>

<div class="modal-body p-0">
    <div class="checkout-body">
        <div class="schedule-calendar">
            <div class="schedule-calendar__left">
                <div class="calendar-wrapper">
                    <div class="calendar availability-calendar-js" id='booking-calendar'>
                    </div>
                </div>
            </div>
            <div class="schedule-calendar__right">
                <div class="calendar-panel calendarPanelJs" style="height: 290px;">
                    <div class="calendar-panel-head">
                        <div class="drop-action">
                            <div class="drop-action__head" id="lesson-drop-action">
                                <div class="drop-action__label">
                                    <span class="unscheduled-lessson-js"><?php echo $quantity; ?></span>
                                    <span class="drop-action__value"><?php echo Label::getLabel('LBL_LESSONS_TO_SCHEDULE'); ?></span>
                                </div>
                                <span class="is-delete single-quantity-js d-none" style="cursor:pointer;" data-id=""></span>
                            </div>
                        </div>
                    </div>
                    <div class="calendar-panel-body">
                        <div class="timeslot-picker cslots-available-js">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <label class="field_label mt-3"><?php echo Label::getLabel('LBL_Reschedule_Reason'); ?><span class="spn_must_field">*</span></label>
        <div class="mt-2">
            <?php
            echo $commentField->getHTML();
            ?>
        </div>
    </div>
    <div class="d-none">
        <?php
        echo $form->getFieldHTML('ordles_id');
        echo $startTime->getHTML();
        echo $endTime->getHTML();
        ?>
    </div>
</div>
<div class="modal-footer">
    <div class="row justify-content-center align-items-center gap-md-5">
        <?php echo $submit->getHTML(); ?>
    </div>
</div>
</form>
<?php echo $form->getExternalJS(); ?>
<script>
    AVAIL_VIEW_ONLY = <?php echo AppConstant::AVAIL_VIEW_ONLY; ?>;
    AVAIL_VIEW_BOOKING = <?php echo AppConstant::AVAIL_VIEW_BOOKING; ?>;
    AVAIL_VIEW_SCHEDULE = <?php echo AppConstant::AVAIL_VIEW_SCHEDULE; ?>;

    var teacherId = <?php echo $teacherId; ?>;
    var slotDuration = <?php echo $lesson['ordles_duration'] ?? 15; ?>;
    var checkSlotAvailabiltAjaxRun = true;
    var formName = 'rescheduleFrm';
    var isReschedule = true;
    cart.prop.ordles_duration = slotDuration;
    cart.prop.ordles_subenddate = '<?php echo $subEndDate ?? ''; ?>';
    cart.prop.ordles_type = <?php echo $lesson['ordles_type'] ?? ''; ?>;
    $(document).ready(function() {
        initializeAvailabilityCalendar('<?php echo $minDateToShow; ?>', '<?php echo $subEndDate ?? ''; ?>', teacherId, AVAIL_VIEW_SCHEDULE);
        getScheduleSlots(teacherId, $('.availability-calendar-js').datepicker('getDate'), AVAIL_VIEW_SCHEDULE);
        $('.is-delete').click(function() {
            deleteScheduledSlot(document.rescheduleFrm);
        });
        setTimeout(adjustMaxHeight, 200);
    });
</script>