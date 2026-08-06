<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="page-panel__head">
    <div class="row align-items-center justify-content-between">
        <div class="col-6">
            <div class="tab-switch">
                <a href="javascript:void(0);" class="tab-switch__item is-active"><?php echo Label::getLabel('LBL_GENERAL'); ?></a>
                <a href="javascript:void(0);" class="tab-switch__item" onclick="weeklyAvailability()"><?php echo Label::getLabel('LBL_WEEKLY'); ?></a>
            </div>
        </div>
        <div class="col-lg-auto col-auto">
            <input type="button" onclick="saveGeneralAvailability();" value="<?php echo Label::getLabel('LBL_SAVE'); ?>" class="btn btn--primary">
        </div>
    </div>
</div>
<div class="page-panel__body availability-setting-calendar" id='calendar-container'>
    <div id='ga_calendar' class="calendar-view availability-calendar general-calendar"></div>
</div>
<script>
    var fecal = new FatEventCalendar(<?php echo $siteUserId; ?>, '<?php echo MyDate::getOffset($siteTimezone); ?>');
    var calendar = fecal.generalAvailaibility('<?php echo MyDate::formatDate(date('Y-m-d H:i:s')); ?>');
</script>