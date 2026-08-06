<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h4 class="flex-1 text-center"><?php echo str_replace('{teacher}', $teacherName, Label::getLabel('LBL_{teacher}\'s_AVAILABILITY')); ?></h4>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-0 viewAvCalendarJs">    
    <div class="mb-4"><?php echo Label::getLabel('LBL_MY_TIMEZONE') . ': ' . strtoupper(Label::getLabel('LBL_GMT')) . ' ' . MyDate::getOffset($userTimeZone); ?></div>
    <div class="calendar-availablity">
        <div class="calendar-availablity__col">
            <div class="calendar-wrapper">
                <div class="availability-calendar-js"></div>
            </div>
        </div>
        <div class="calendar-availablity__col">
            <div class="timeslots-wrapper slots-available-js calendarPanelJs"> </div>
        </div>
    </div>
</div>
<script>
    let teacherId = '<?php echo $teacherId; ?>';
    AVAIL_VIEW_ONLY = <?php echo AppConstant::AVAIL_VIEW_ONLY; ?>;
    AVAIL_VIEW_BOOKING = <?php echo AppConstant::AVAIL_VIEW_BOOKING; ?>;
    AVAIL_VIEW_SCHEDULE = <?php echo AppConstant::AVAIL_VIEW_SCHEDULE; ?>;
    $(document).ready(() => {
        initializeAvailabilityCalendar('<?php echo $minDateToShow; ?>', '<?php echo $subEndDate ?? ''; ?>', teacherId, AVAIL_VIEW_ONLY);
        getSlots(teacherId, $('.availability-calendar-js').datepicker('getDate'), AVAIL_VIEW_ONLY);
        window.addEventListener('resize', adjustAvMaxHeight);
        setTimeout(adjustAvMaxHeight, 200);
    });
</script>