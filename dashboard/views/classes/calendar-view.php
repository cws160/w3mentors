<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="results">
    <div class="-align-right">
        <div class="list-inline-item">
            <span class="box-hint badge--round bg-info  m-0 -no-border">&nbsp;</span>
            <?php echo Label::getLabel('LBL_ONLINE_SESSION'); ?>
        </div>
        <div class="list-inline-item">
            <span class="box-hint badge--round bg-yellow  m-0 -no-border">&nbsp;</span>
            <?php echo Label::getLabel('LBL_IN-PERSON_SESSION'); ?>
        </div>
    </div>
    <div class="page-panel montly-lesson-calendar mt-4">
        <div id='calendar-container'>
            <div id='d_calendar' class="calendar-view"></div>
        </div>
    </div>
</div>
<script>
    moreLinkTextLabel = '<?php echo Label::getLabel('LBL_VIEW_MORE'); ?>';
    var fecal = new FatEventCalendar(0, '<?php echo MyDate::getOffset($siteTimezone); ?>');
    fecal.ClassesMonthlyCalendar('<?php echo $nowDate; ?>');
</script>