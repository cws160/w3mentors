/* global fcom, moment, moreLinkTextLabel, FullCalendar, i, confFrontEndUrl, langLbl, userType */
var dayShortNames = weekDayNames.shortName.slice(0);
var lastValue = dayShortNames[6];
dayShortNames.pop();
dayShortNames.unshift(lastValue);

var dayLongNames= weekDayNames.longName.slice(0);
var lastValueLong = dayLongNames[6];
dayLongNames.pop();
dayLongNames.unshift(lastValueLong);

var timeInterval;
var freeTrial = 0;
var tFmtJsCal = tFmtJs;
var timerFormatMoment = 'HH:mm:ss';
var timerFormatJs = 'HH:mm:ss';
if(tFmtJsCal.includes('A')) {
    tFmtJsCal = tFmtJsCal.toLowerCase();
    timerFormatMoment = 'hh:mm:ss A';
    timerFormatJs = 'hh:mm:ss a';
}
var FatEventCalendar = function (teacherId, offset) {
    this.teacherId = teacherId;
    this.offset = offset;
    var seconds = 2;
    teacherId = teacherId;
    this.calDefaultConf = {
        height: 'auto',
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'time', center: 'title', right: 'prev,next today' },
        slotDuration: '00:15',
        buttonText: { today: decodeHtmlCharCodes(langLbl.today) },
        direction: layoutDirection,
        nowIndicator: true,
        navLinks: false,
        eventOverlap: false,
        slotEventOverlap: false,
        selectable: false,
        editable: false,
        selectLongPressDelay: 400,
        eventLongPressDelay: 30,
        longPressDelay: 30,
        allDaySlot: false,
        eventTimeFormat: tFmtJsCal,
        slotLabelFormat: tFmtJsCal,
        loading: function (isLoading) {
            if (isLoading == true) {
                jQuery("#loaderCalendar").show();
            } else {
                jQuery("#loaderCalendar").hide();
            }
        }
    };
    updateTime = function (time, calendarObj) {
        currentTimeStr = moment(time).add(seconds, 'seconds').format(timerFormatMoment);
        jQuery('body').find(".fc-toolbar-ltr h6 span.timer").html(currentTimeStr);
    };
    this.setLocale = function (locale) {
        this.calDefaultConf.locale = locale;
    };
    this.startTimer = function (currentTime, calendarObj) {
        clearInterval(timeInterval);
        timeInterval = setInterval(function () {
            this.updateTime(currentTime, calendarObj);
            seconds++;
        }, 1000);
    };
    getSlotBookingConfirmationBox = function (calEvent, calendar) {
        var startDateTime = calendar.formatDate(calEvent.start, 'LLL d, yyyy');
        var start = calendar.formatDate(calEvent.start, tFmtJsCal);
        var end = calendar.formatDate(calEvent.end, tFmtJsCal);
        var selectedStartDateTime = moment(calEvent.start).format('YYYY-MM-DD HH:mm:ss');
        var selectedEndDateTime = moment(calEvent.end).format('YYYY-MM-DD HH:mm:ss');
        let tooltip = jQuery('.tooltipevent-wrapper-js')
        let tooltipevent = jQuery('.tooltipevent-wrapper-js')
        tooltip.find('#lesson_starttime').val(selectedStartDateTime);
        tooltip.find('#lesson_endtime').val(selectedEndDateTime);
        tooltip.find('.displayEventDate').html(startDateTime);
        tooltip.find('.displayEventTime').html(start + ' - ' + end);
        tooltipevent.css({ 'position': 'absolute', 'top': '50%', 'left': '50%', 'transform': 'translate(-50%, -50%)' });
        tooltip.css('z-index', 10000);
        tooltip.removeClass('d-none');
    };
    validateStartEnd = function (info, calendar) {
        let calendarStartDateTime = calendar.view.currentStart;
        let calendarEndDateTime = calendar.view.currentEnd;
        var start = info.event.start;
        var end = info.event.end;
        if (moment(calendarStartDateTime) > moment(end) || moment(calendarEndDateTime) < moment(start)) {
            info.event.remove();
        }
        if (moment(calendarStartDateTime) > moment(start)) {
            info.event.setStart(calendarStartDateTime);
        }
        if (moment(calendarEndDateTime) < moment(info.event.end)) {
            info.event.setEnd(calendarEndDateTime);
        }
    };
    eventMerging = function (info, events, calendar) {
        validateStartEnd(info, calendar);
        var start = info.event.start;
        var end = info.event.end;
        for (i in events) {
            if (events[i]._instance.instanceId == info.oldEvent._instance.instanceId && events[i]._instance.defId == info.oldEvent._instance.defId) {
                continue;
            }
            if (moment(events[i].start) < moment(calendar.view.currentStart) || moment(events[i].end) > calendar.view.currentEnd) {
                continue;
            }
            if (moment(end) >= moment(events[i].start) && moment(start) <= moment(events[i].end)) {
                if (moment(start) > moment(events[i].start)) {
                    start = events[i].start;
                    info.event.setStart(events[i].start);
                }
                if (moment(end) < moment(events[i].end)) {
                    end = events[i].end;
                    info.event.setEnd(events[i].end);
                }
                events[i].remove();
            }
        }
    };
    removeCloseIcon = function () {
        $('.fc-timegrid-event').each(function () {
            if (!$(this).hasClass('fc-event-start')) {
                $(this).find(".closeon").remove();
            }
        });
    };
};
FatEventCalendar.prototype.generalAvailaibility = function (currentTime) {
    var calConf = {
        selectable: true,
        editable: true,
        initialDate: '2018-01-21',
        slotEventOverlap: false,
        now: currentTime,
        headerToolbar: { left: 'time', center: '', right: '' },
        firstDay: 0,
        dayHeaderFormat: '{EEE}',
        eventResizableFromStart: true,
        eventSources: [{
            events: function (fetchInfo, successCallback, failureCallback) {
                var postData = "start=" + moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss') + "&end=" + moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss');
                fcom.updateWithAjax(fcom.makeUrl('Teacher', 'generalAvblJson'), postData, function (res) {
                    successCallback(res.data);
                }, { process: false });
            }
        }],
        select: function (arg) {
            var start = arg.start;
            var end = arg.end;
            if (moment(start).format('d') != moment(end).format('d') && moment(end).format('YYYY-MM-DD HH:mm') != moment(start).add(1, 'days').format('YYYY-MM-DD 00:00')) {
                calendar.unselect();
                return false;
            }
            var events = calendar.getEvents();
            for (i in events) {
                if (moment(end) >= moment(events[i].start) && moment(start) <= moment(events[i].end)) {
                    if (moment(start) > moment(events[i].start)) {
                        start = moment(events[i].start).format('YYYY-MM-DD') + "T" + moment(events[i].start).format('HH:mm:ss');
                    }
                    if (moment(end) < moment(events[i].end)) {
                        end = moment(events[i].end).format('YYYY-MM-DD') + "T" + moment(events[i].end).format('HH:mm:ss');
                    }
                    events[i].remove();
                }
            }
            calendar.addEvent({ title: '', start: start, end: end, className: 'slot_available', allDay: false });
        },
        eventDrop: function (info) {
            eventMerging(info, calendar.getEvents(), calendar);
        },
        eventResize: function (info) {
            eventMerging(info, calendar.getEvents(), calendar);
        },
        eventDidMount: function (arg) {
            let event = arg.event;
            validateStartEnd(arg, calendar);
            element = arg.el;
            $(element).find(".fc-event-main-frame").prepend("<span class='closeon'>X</span>");
            $(element).find(".closeon").click(function () {
                if (confirm(langLbl.confirmRemove)) {
                    event.remove();
                }
            });
            removeCloseIcon();
        }
    };
    var defaultConf = this.calDefaultConf;
    var conf = { ...defaultConf, ...calConf };
    var calendarEl = document.getElementById('ga_calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, conf);
    calendar.render();
    jQuery('body').find(".fc-time-button").parent().html("<h6><span>" + langLbl.myTimeZoneLabel +
        " :-</span> <span class='timer'>" + calendar.formatDate(currentTime, timerFormatJs) +
        "</span><span class='timezoneoffset'>(" + langLbl.timezoneString + " " + this.offset + ")</span></h6>");
    seconds = 2;
    this.startTimer(currentTime, calendar);
    return calendar;
};
FatEventCalendar.prototype.weeklyAvailaibility = function (currentTime, initialDate) {
    var calConf = {
        selectable: true,
        editable: true,
        now: currentTime,
        dayHeaderFormat: '{EEE {L/d}}',
        views: { timeGridWeek: { titleFormat: '{LLL {d}}, yyyy' } },
        eventResizableFromStart: true,
        events: function (fetchInfo, successCallback, failureCallback) {
            if (calendar) {
                calendar.removeAllEvents()
            }
            var postData = "start=" + moment(fetchInfo.start).subtract(1, "weeks").format('YYYY-MM-DD HH:mm:ss') + "&end=" + moment(fetchInfo.end).add(1, "weeks").format('YYYY-MM-DD HH:mm:ss');
            fcom.updateWithAjax(fcom.makeUrl('Teacher', 'avalabilityJson'), postData, function (response) {
                let events = response.data;
                for (i in events) {
                    if (moment(fetchInfo.start) > moment(events[i].start) && moment(fetchInfo.end) < moment(events[i].end)) {
                        let newEvent = {
                            start: moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss'),
                            end: moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss'),
                            className: 'slot_available'
                        }
                        events.push(newEvent);
                        newEvent = {
                            start: moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss'),
                            end: events[i].end,
                            className: 'slot_available'
                        }
                        events.push(newEvent);
                        events[i].end = moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss');
                    } else if (moment(fetchInfo.start) > moment(events[i].start) && moment(fetchInfo.start) < moment(events[i].end)) {
                        let newEvent = {
                            start: moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss'),
                            end: events[i].end,
                            className: 'slot_available'
                        }
                        events[i].end = moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss');
                        events.push(newEvent);
                    } else if (moment(fetchInfo.end) > moment(events[i].start) && moment(fetchInfo.end) < moment(events[i].end)) {
                        let newEvent = {
                            start: events[i].start,
                            end: moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss'),
                            className: 'slot_available'
                        }
                        events[i].start = moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss');
                        events.push(newEvent);
                    }
                }
                successCallback(events);
            });
        },
        select: function (arg) {
            var start = arg.start;
            var end = arg.end;
            if (start < moment(calendar.view.currentStart)) {
                start = calendar.view.currentStart;
            }
            if (end > calendar.view.currentEnd) {
                end = calendar.view.currentEnd;
            }
            var events = calendar.getEvents();
            for (i in events) {
                if (moment(events[i].start) < moment(calendar.view.currentStart) || moment(events[i].end) > calendar.view.currentEnd) {
                    continue;
                }
                if (moment(end) >= moment(events[i].start) && moment(start) <= moment(events[i].end)) {
                    if (moment(start) > moment(events[i].start)) {
                        start = events[i].start;
                    }
                    if (moment(end) < moment(events[i].end)) {
                        end = events[i].end;
                    }
                    events[i].remove();
                }
            }
            calendar.addEvent({ end: end, start: start, className: 'slot_available' });
        },
        eventDrop: function (info) {
            eventMerging(info, calendar.getEvents(), calendar);
        },
        eventResize: function (info) {
            eventMerging(info, calendar.getEvents(), calendar);
        },
        eventDidMount: function (arg) {
            let event = arg.event;
            let element = arg.el;
            $(element).find(".fc-event-main-frame").prepend("<span class='closeon'>X</span>");
            $(element).find(".closeon").click(function () {
                if (confirm(langLbl.confirmRemove)) {
                    event.remove();
                }
            });
            removeCloseIcon();
        }
    };
    var defaultConf = this.calDefaultConf;
    var conf = { ...defaultConf, ...calConf };
    if (initialDate && initialDate != '') {
        conf.initialDate = initialDate;
    }
    var calendarEl = document.getElementById('w_calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, conf);
    calendar.render();
    jQuery('body').find(".fc-time-button").parent().html("<h6><span>" + langLbl.myTimeZoneLabel + " :-</span> <span class='timer'>" + calendar.formatDate(currentTime, timerFormatJs) + "</span><span class='timezoneoffset'>(" + langLbl.timezoneString + " " + this.offset + ")</span></h6>");
    seconds = 2;
    this.startTimer(currentTime, calendar);
    return calendar;
};
FatEventCalendar.prototype.TeacherDashboardCalendar = function (currentTime, userId) {
    var calConf = {
        initialView: 'dayGridMonth',
        now: currentTime,
        headerToolbar: { left: 'time', center: 'title', right: 'prev,next' },
        views: { dayGridMonth: { titleFormat: '{LLL}, yyyy' } },
        dayHeaderFormat: '{EEE}',
        moreLinkText: moreLinkTextLabel,
        eventColor: 'green',
        events: function (fetchInfo, successCallback, failureCallback) {
            var postData = "start=" + moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss') + "&end=" + moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss');
            postData += "&user_type=" + userType;
            fcom.updateWithAjax(fcom.makeUrl('Teachers', 'getTeacherScheduledSessions', [userId], confFrontEndUrl), postData, function (res) {
                successCallback(res.data);
            }, { process: false });
        },
        dayMaxEvents: 1
    }
    var defaultConf = this.calDefaultConf;
    var conf = { ...defaultConf, ...calConf };
    var calendarEl = document.getElementById('d_calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, conf);
    calendar.render();
    return calendar;
};
FatEventCalendar.prototype.LessonMonthlyCalendar = function (currentTime) {
    var calConf = {
        initialView: 'dayGridMonth',
        now: currentTime,
        headerToolbar: { left: 'time', center: 'title', right: 'prev,next' },
        views: { dayGridMonth: { titleFormat: '{LLL}, yyyy' } },
        dayHeaderFormat: '{EEE}',
        moreLinkText: moreLinkTextLabel,
        eventColor: 'green',
        eventTimeFormat: tFmtJsCal,
        events: function (fetchInfo, successCallback, failureCallback) {
            postData = "start=" + moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss') + "&end=" + moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss');
            if (document.frmLessonSearch) {
                postData = postData + "&" + fcom.frmData(document.frmLessonSearch);
            }
            fcom.updateWithAjax(fcom.makeUrl('Lessons', 'calendarJson'), postData, function (res) {
                successCallback(res.data);
                setTimeout(function () {
                    $.appalert.close();
                }, 0);
            });
        },
        dayMaxEvents: 3
    };
    var defaultConf = this.calDefaultConf;
    var conf = { ...defaultConf, ...calConf };
    var calendarEl = document.getElementById('d_calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, conf);
    calendar.render();
};
FatEventCalendar.prototype.ClassesMonthlyCalendar = function (currentTime) {
    var calConf = {
        initialView: 'dayGridMonth',
        now: currentTime,
        headerToolbar: { left: 'time', center: 'title', right: 'prev,next' },
        views: { dayGridMonth: { titleFormat: '{LLL}, yyyy' } },
        dayHeaderFormat: '{EEE}',
        moreLinkText: moreLinkTextLabel,
        eventColor: 'green',
        eventTimeFormat: tFmtJsCal,
        events: function (fetchInfo, successCallback, failureCallback) {
            postData = "start=" + moment(fetchInfo.start).format('YYYY-MM-DD HH:mm:ss') + "&end=" + moment(fetchInfo.end).format('YYYY-MM-DD HH:mm:ss');
            if (document.frmClassSearch) {
                postData = postData + "&" + fcom.frmData(document.frmClassSearch);
            }
            fcom.updateWithAjax(fcom.makeUrl('Classes', 'calendarJson'), postData, function (res) {
                successCallback(res.data);
                setTimeout(function () {
                    $.appalert.close();
                }, 0);
            });
        },
        dayMaxEvents: 3
    };
    var defaultConf = this.calDefaultConf;
    var conf = { ...defaultConf, ...calConf };
    var calendarEl = document.getElementById('d_calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, conf);
    calendar.render();
};

/* Datepicker Related Functions for Scheduling and Re-scheduling */

initializeAvailabilityCalendar = function (minDateToShow, maxDateToShow, teacherId, slotViewType) {
    setMomentLocale();
    var minAvailDate = new Date(minDateToShow);
    var maxAvailDate = null;
    if (maxDateToShow != '') {
        maxAvailDate = new Date(maxDateToShow);
    }
    var dayShortNames = weekDayNames.shortName.slice(0);
    var lastValue = dayShortNames[6];
    dayShortNames.pop();
    dayShortNames.unshift(lastValue);
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
            getScheduleSlots(teacherId, $(this).datepicker('getDate'), slotViewType);
        },
        onChangeMonthYear : function(year, month, inst) {
            setTimeout(adjustMaxHeight, 100);
        }
    });
};

adjustMaxHeight = function() {
    var calendarWrapper = $('.calendar-wrapper');
    var calendarPanel = $('.calendarPanelJs');
    if ($(calendarWrapper).is(':visible') && calendarPanel) {
        calendarPanel.height(calendarWrapper.outerHeight() + 'px');
    } else {
        window.removeEventListener('resize', adjustMaxHeight);
    }
};

getScheduleSlots = function(teacherId, selectedDate, slotViewType = parseInt(AVAIL_VIEW_SCHEDULE)) {
    selectedDate = $.datepicker.formatDate("yy-mm-dd", selectedDate);
    fcom.ajax(fcom.makeUrl('Teachers', 'getAvailabilitySlots', [teacherId, selectedDate, slotViewType], confFrontEndUrl), cart.prop, function(ans) {
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

checkSlotAvailability = function(startTime, endTime, teacherId, form) {
    var event = { start: moment(startTime).format('YYYY-MM-DD HH:mm:ss'), end: moment(endTime).format('YYYY-MM-DD HH:mm:ss'), };
    fcom.ajax(fcom.makeUrl('Teachers', 'checkSlotAvailability', [teacherId], confFrontEndUrl), event, function (response) {
        if (response.status == 0) {
            deleteScheduledSlot(form);
            return false;
        }
    }, { failed: true });
};