<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header modal-header--checkout">
    <?php if($ordlesType == Lesson::TYPE_FTRAIL) { ?>
        <h4 class="flex-1 text-center"><?php echo Label::getLabel('LBL_SCHEDULE_YOUR_FREE_TRIAL'); ?></h4>
    <?php } else { ?>
        <button onclick="cart.langSlots(cart.prop.ordles_teacher_id); $('body > .tooltipevent').remove();" class="btn-back"></button>
        <h4 class="flex-1 text-center"><?php echo Label::getLabel('LBL_SCHEDULE_YOUR_LESSONS'); ?></h4>
    <?php } ?>
    <button type="button" class="btn-close w3mentorsmodalJs close-checkout-modal" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0 viewCalendarJs">
    <div class="checkout-body">
        <div class="schedule-calendar">
            <div class="schedule-calendar__left">
                <div class="calendar-wrapper">
                    <div class="calendar availability-calendar-js" id='booking-calendar'>
                    </div>
                </div>
            </div>
            <?php if ($quantity) { ?>
                <div class="schedule-calendar__right">
                    <div class="calendar-panel calendarPanelJs" style="height: 260px">
                        <div class="calendar-panel-head">
                            <div class="drop-action">
                                <div class="drop-action__head" id="lesson-drop-action">
                                    <div class="drop-action__label">
                                        <span class="unscheduled-lessson-js"><?php echo $quantity; ?></span>
                                        <span class="drop-action__value"><?php echo Label::getLabel('LBL_LESSON(S)_TO_SCHEDULE'); ?></span>
                                    </div>
                                    <span class="is-delete single-quantity-js d-none" style="cursor:pointer;" data-id=""></span>
                                    <?php if ($quantity > 1) { ?>
                                        <a href="javascript:void(0);" class="drop-action__trigger drop-trigger-js link"><?php echo Label::getLabel('LBL_Click_here'); ?></a>
                                    <?php } ?>
                                </div>
                                <div class="drop-action__target drop-target-js">
                                    <div class="schedule-lessions">
                                        <div class="schedule-lessions__head">
                                            <h6><?php echo Label::getLabel('LBL_Scheduled_Lesson(s)'); ?></h6>
                                            <button type="button" class="btn-close js--close-popup"></button>
                                        </div>
                                        <div class="schedule-lessions__body">
                                            <div class="listing-wrapper">
                                                <div class="numbers-list" id="cal-lesson-list">
                                                    <?php for ($i = 1; $i <= $quantity; $i++) { ?>
                                                        <span class="numbers-list__item">
                                                            <span class="number-list__value"><?php echo Label::getLabel('LBL_LESSON(S)_TO_SCHEDULE'); ?></span>
                                                            <span class="is-delete d-none" data-id=""></span>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="calendar-panel-body">
                            <div class="timeslot-picker <?php echo (isset($calendarType) && $calendarType != AppConstant::AVAIL_VIEW_ONLY) ? 'cslots-available-js' : 'slots-available-js'; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<div class="modal-footer">
    <div class="row justify-content-center align-items-center gap-md-5">
        <?php if (empty($activePlan) && $ordlesType != Lesson::TYPE_FTRAIL) { ?>
            <div class="col-auto">
                <div class="cart-price">
                    <span class="cart-price__label"><?php echo Label::getLabel('LBL_TOTAL_PRICE'); ?>:</span>
                    <span class="cart-price__value" id="price-js"></span>
                </div>
            </div>
        <?php } ?>
        <div class="col-auto">
            <?php if ($ordlesType == Lesson::TYPE_REGULAR) {
                $clickEvent = 'onclick="cart.addLesson();"';
                $lbl = LabeL::getLabel('LBL_NEXT');
                if (!empty($activePlan)) {
                    $clickEvent = 'onclick="cart.addSubscriptionLesson();"';
                    $lbl = LabeL::getLabel('LBL_CONFIRM');
                    $form->addFormTagAttribute('class', 'd-none');
                    echo $form->getFormHtml();
                } ?>
                <button <?php echo $clickEvent; ?> class="btn btn--primary color-white" id="lesson-checkout-btn-js"><?php echo $lbl; ?></button>
            <?php } else if ($ordlesType == Lesson::TYPE_FTRAIL) {
                $form->addFormTagAttribute('class', 'd-none');
                echo $form->getFormHtml();
                ?>
                <button onclick="cart.addLesson();" class="btn btn--primary color-white book-freetrial-js btn--disabled "><?php echo LabeL::getLabel('LBL_CONFIRM_FREE_TRIAL_SLOT'); ?></button>
            <?php } else { ?>
                <button class="btn btn--primary color-white btn--disabled " id="subcrip-checkout-btn-js"><?php echo LabeL::getLabel('LBL_NEXT'); ?></button>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    var labelToSchedule = '<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_LESSON(S)_TO_SCHEDULE')); ?>';
    var labelAllScheduled = '<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_ALL_SCHEDULED')); ?>';
    var sub = (TYPE_SUBCRIP == cart.prop.ordles_type) ? 1 : 0;
    let teacherId = <?php echo $teacherId; ?>;
    cart.prop.ordles_teacher_id = teacherId;
    cart.prop.ordles_duration = '<?php echo $duration; ?>';
    var calendarType = '<?php echo $calendarType; ?>';

    TYPE_SUBCRIP = '<?php echo Lesson::TYPE_SUBCRIP ?>';
    TYPE_FTRAIL = '<?php echo Lesson::TYPE_FTRAIL ?>';
    AVAIL_VIEW_ONLY = '<?php echo AppConstant::AVAIL_VIEW_ONLY; ?>';
    AVAIL_VIEW_BOOKING = '<?php echo AppConstant::AVAIL_VIEW_BOOKING; ?>';
    AVAIL_VIEW_SCHEDULE = '<?php echo AppConstant::AVAIL_VIEW_SCHEDULE; ?>';

    $('.drop-trigger-js').click(function() {
        $('.drop-target-js').toggleClass('is-visible');
        $(this).toggleClass('is-open');
    });
    $('.js--close-popup').click(function() {
        $('.drop-trigger-js').trigger('click');
    });
    $('.is-delete').click(function() {
        deleteSlot($(this).attr('data-id'));
    });

    $(document).ready(() => {
        initializeAvailabilityCalendar('<?php echo $minDateToShow; ?>' , '<?php echo $subEndDate ?? ''; ?>', teacherId, calendarType);
        getSlots(teacherId, $('.availability-calendar-js#booking-calendar').datepicker('getDate'), calendarType);
        window.addEventListener('resize', adjustMaxHeight);
        setTimeout(adjustMaxHeight, 200);
    });
</script>