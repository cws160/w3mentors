var AVAIL_VIEW_ONLY;
var AVAIL_VIEW_BOOKING;
var AVAIL_VIEW_SCHEDULE;

/* global moment, confFrontEndUrl, cart, langLbl, i, fcom, layoutDirection, calendar, id, id, TYPE_SUBCRIP */
initializeAvailabilityCalendar = function (minDateToShow, maxDateToShow, teacherId, slotsType) {
    setMomentLocale();
    var minAvailDate = new Date(minDateToShow);
    var maxAvailDate = null;
    if (maxDateToShow != '') {
        maxAvailDate = new Date(maxDateToShow);
    }
    $('.availability-calendar-js').datepicker({
        minDate: minAvailDate,
        maxDate: maxAvailDate,
        monthNames: monthNames.longName,
        monthNamesShort: monthNames.shortName,
        dayNamesMin: dayShortNames,
        dayNamesShort: dayShortNames,
        firstDay: 1,
        dateFormat: "DD, MM d",
        onSelect: function(dateText, inst) {
            if(slotsType == AVAIL_VIEW_SCHEDULE) {
                getScheduleSlots(teacherId, $(this).datepicker('getDate'), slotsType);
            } else {
                getSlots(teacherId, $(this).datepicker('getDate'), slotsType);
            }
        },
        onChangeMonthYear : function(year, month, inst) {
            setTimeout(adjustMaxHeight, 100);
            setTimeout(adjustAvMaxHeight, 100);
        }
    });
};
adjustMaxHeight = function() {
    var calendarWrapper = $('.viewCalendarJs .calendar-wrapper');
    var calendarPanel = $('.viewCalendarJs .calendarPanelJs');
    if ($(calendarWrapper).is(':visible') && calendarPanel) {
        calendarPanel.height(calendarWrapper.outerHeight() + 'px');
    } else {
        window.removeEventListener('resize', adjustMaxHeight);
    }
};
adjustAvMaxHeight = function() {
    var calendarWrapper = $('.viewAvCalendarJs .calendar-wrapper');
    var calendarPanel = $('.viewAvCalendarJs .calendarPanelJs');
    if ($(calendarWrapper).is(':visible') && calendarPanel) {
        calendarPanel.height(calendarWrapper.outerHeight() + 'px');
    } else {
        window.removeEventListener('resize', adjustMaxHeight);
    }
};

getSlots = function(teacherId, selectedDate, slotsType = AVAIL_VIEW_ONLY) {
    selectedDate = $.datepicker.formatDate("yy-mm-dd", selectedDate);
    fcom.ajax(fcom.makeUrl('Teachers', 'getAvailabilitySlots', [teacherId, selectedDate, slotsType]), cart.prop, function(ans) {
        
        if (slotsType != AVAIL_VIEW_ONLY) {
            $('.cslots-available-js').html(ans);
        } else {
            $('.slots-available-js').html(ans);
        }
        if(slotsType == AVAIL_VIEW_BOOKING) {
            slotsSelected = Object.keys(cart.prop.slots).length;
            if (slotsSelected >= 1) {
                $("input[name='slots[]']").each(function() {
                    let slotValue = $(this).val();
                    if (cart.prop.slots[slotValue]) {
                        $(this).prop('checked', true);
                        $(this).addClass('is-checked');
                    }
                });
                if(slotsSelected == cart.prop.ordles_quantity && cart.prop.ordles_type == LESSON_TYPE_SUBCRIP) {
                    $('#subcrip-checkout-btn-js').removeClass('btn--disabled').attr('onclick', ' cart.addSubscription();');
                }
                updateLessonList();
            }
        }
    });
};

deleteSlot = function(eventId) {
    if (cart.prop.slots[eventId]) {
        delete cart.prop.slots[eventId];
        $("input[name='slots[]']").each(function() {
            if ($(this).val() == eventId) {
                $(this).prop('checked', false);
                $(this).removeClass('is-checked');
            }
        });
        if (cart.prop.ordles_type == TYPE_SUBCRIP) {
            $('#subcrip-checkout-btn-js').addClass('btn--disabled').removeAttr('onclick');
        }
        if(cart.prop.ordles_type == TYPE_FTRAIL) {
            $('.book-freetrial-js').addClass('btn--disabled').removeAttr('onclick');
        }
        updateLessonList();
    }
};

updateLessonList = function () {
    let index = 0;
    let timeText = '';
    for (id in cart.prop.slots) {
        timeText = moment(cart.prop.slots[id].ordles_starttime).format('dddd MMM D, YYYY ' + tFmtJs);
        timeText += " - " + moment(cart.prop.slots[id].ordles_endtime).format(tFmtJs);
        $('#cal-lesson-list .number-list__value').eq(index).text(timeText);
        $('#cal-lesson-list .numbers-list__item').eq(index).addClass('is-selected');
        $('#cal-lesson-list .is-delete').eq(index).removeClass('d-none').attr('data-id', id);
        if(cart.prop.ordles_quantity == 1) {
           $('.single-quantity-js').removeClass('d-none').attr('data-id', id);
        }
        index++;
    }
    for (let i = index; i < cart.prop.ordles_quantity; i++) {
        $('#cal-lesson-list .number-list__value').eq(i).text(labelToSchedule);
        $('#cal-lesson-list .numbers-list__item').eq(index).removeClass('is-selected');
        $('#cal-lesson-list .is-delete').eq(i).addClass('d-none').attr('data-id', '');
        if(cart.prop.ordles_quantity == 1) {
            $('.single-quantity-js').addClass('d-none').attr('data-id', '');
        }
    }
    let unscheduledLessons = cart.prop.ordles_quantity - Object.keys(cart.prop.slots).length;
    if (unscheduledLessons > 0) {
        $('.unscheduled-lessson-js').text(unscheduledLessons);
        $('.drop-action__label .drop-action__value').html(labelToSchedule);
    } else {
        if(cart.prop.ordles_quantity > 1) {
            $('.unscheduled-lessson-js').text('');
            $('.drop-action__label .drop-action__value').html(labelAllScheduled);
        } else {
            $('.unscheduled-lessson-js').text('');
            $('.drop-action__label .drop-action__value').html(timeText);
        }
    }
};

/* Datepicker Functions for Schedule/Re-schedule [ */
getScheduleSlots = function(teacherId, selectedDate, slotsType = AVAIL_VIEW_SCHEDULE) {
    selectedDate = $.datepicker.formatDate("yy-mm-dd", selectedDate);
    fcom.ajax(fcom.makeUrl('Teachers', 'getAvailabilitySlots', [teacherId, selectedDate, slotsType], confFrontEndUrl), cart.prop, function(ans) {
        $('.cslots-available-js').html(ans);
        let form = document[formName];
        let selectedSlot = form.ordles_lesson_starttime.value;
        if(selectedSlot != '') {
            $("input[name='slots[]']").each(function() {
                if(selectedSlot == $(this).val()){
                    $(this).prop('checked', true);
                    $(this).addClass('is-checked');
                }
            });
        }
    });
};

selectScheduledSlot = function(slot) {
    let form = document[formName];
    let val = slot.value;
    if($(slot).hasClass('is-checked')) {
        deleteScheduledSlot(form);
    } else {
        if (form.ordles_lesson_starttime.value != '' || form.ordles_lesson_endtime.value != '') {
            $(slot).prop('checked', false);
            fcom.error(langLbl.maxSlotsSelected);
            return false;
        } else {
            let startTime = moment(val).format('YYYY-MM-DD HH:mm:ss');
            let endTime = moment(val).add(slotDuration, 'minutes').format('YYYY-MM-DD HH:mm:ss');
            if(checkSlotAvailabiltAjaxRun) {
                checkSlotAvailability(startTime, endTime, teacherId, form);
            }
            form.ordles_lesson_starttime.value = startTime;
            form.ordles_lesson_endtime.value = endTime;
            $(slot).addClass('is-checked');
            if(isReschedule) {
                $('.confirm-lesson-js').removeClass('btn--disabled').removeAttr('disabled');
            } else {
                $('.confirm-lesson-js').removeClass('btn--disabled').attr('onclick', 'scheduleSetup(document.scheduleFrm)');
            }
            timeText = moment(startTime).format('dddd MMM D, YYYY ' + tFmtJs);
            timeText += " - " + moment(endTime).format(tFmtJs);
            $('.single-quantity-js').removeClass('d-none').attr('data-id', startTime);
            $('.unscheduled-lessson-js').text('');
            $('.drop-action__label .drop-action__value').html(timeText);
        }
    }
};

deleteScheduledSlot = function(form) {
    form.ordles_lesson_starttime.value = '';
    form.ordles_lesson_endtime.value = '';
    if(isReschedule) {
        $('.confirm-lesson-js').addClass('btn--disabled').attr('disabled', 'disabled');
    } else {
        $('.confirm-lesson-js').addClass('btn--disabled').removeAttr('onclick');
    }
    $('.unscheduled-lessson-js').text(1);
    $('.drop-action__label .drop-action__value').html(labelToSchedule);
    $('.single-quantity-js').addClass('d-none').attr('data-id', '');
    $("input[name='slots[]']").each(function() {
        $(this).prop('checked', false);
        $(this).removeClass('is-checked');
    });
};
/* ] */