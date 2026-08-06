/* global fcom, moment, calendar */
(function () {
    var dv = "#availability-calendar-js";
    generalAvailability = function () {
        fcom.ajax(fcom.makeUrl("Teacher", "generalAvailability"), "", function (response) {
            $(dv).html(response);
            getProfileProgress();
        });
    };
    weeklyAvailability = function (initialDate) {
        fcom.ajax(fcom.makeUrl("Teacher", "weeklyAvailability"), "initialDate=" + initialDate, function (response) {
            $(dv).html(response);
        });
    };
    setUpWeeklyAvailability = function () {
        let start = moment(calendar.view.activeStart).format("YYYY-MM-DD HH:mm:ss");
        let end = moment(calendar.view.activeEnd).format("YYYY-MM-DD HH:mm:ss");
        var availability = JSON.stringify(mergeEvents());
        var data = "start=" + start + "&end=" + end + "&availability=" + availability;
        fcom.updateWithAjax(fcom.makeUrl("Teacher", "setupAvailability"), data);
    };
    mergeEvents = function () {
        allevents = calendar.getEvents().map(function (e) {
            return {
                start: moment(e.start).format("YYYY-MM-DD HH:mm:ss"),
                end: moment(e.end).format("YYYY-MM-DD HH:mm:ss"),
            };
        });
        for (let index = 0; index < allevents.length; index++) {
            const element = allevents[index];
            if (element == null) {
                continue;
            }
            let start = element.start;
            let end = element.end;
            for (let i = 0; i < allevents.length; i++) {
                const event = allevents[i];
                if (index == i || event == null) {
                    continue;
                }
                if (moment(end) >= moment(event.start) && moment(start) <= moment(event.end)) {
                    if (moment(start) > moment(event.start)) {
                        start = event.start;
                        allevents[index].start = event.start;
                    }
                    if (moment(end) < moment(event.end)) {
                        end = event.end;
                        allevents[index].end = event.end;
                    }
                    allevents[i] = null;
                }
            }
        }
        return allevents.filter(function (el) {
            return el != null;
        });
    };
    saveGeneralAvailability = function () {
        var allEvents = calendar.getEvents();
        let data = allEvents.map(function (e) {
            return {
                start: moment(e.start).format("YYYY-MM-DD HH:mm:ss"),
                end: moment(e.end).format("YYYY-MM-DD HH:mm:ss"),
            };
        });
        fcom.updateWithAjax(fcom.makeUrl("Teacher", "setupGeneralAvailability"), "data=" + JSON.stringify(data), function (t) {
            getProfileProgress();
        });
    };
    getProfileProgress = function () {
        fcom.updateWithAjax(fcom.makeUrl("Teacher", "profileProgress", []), "", function (data) {
            let tpp = data.PrfProg;
            $.each(tpp, function (key, value) {
                switch (key) {
                    case "isProfileCompleted":
                        if (value) {
                            $(".is-profile-complete-js").removeClass("infobar__media-icon--alert").addClass("infobar__media-icon--tick");
                            $(".is-profile-complete-js").html("");
                            $(".aside--progress--menu").addClass("is-completed");
                        } else {
                            $(".is-profile-complete-js").removeClass("infobar__media-icon--tick").addClass("infobar__media-icon--alert");
                            $(".is-profile-complete-js").html("!");
                        }
                        break;
                    case "generalAvailabilityCount":
                        value = parseInt(value);
                        if (0 >= value) {
                            $(".availability-setting-js").removeClass("is-completed");
                        } else {
                            $(".availability-setting-js").addClass("is-completed");
                        }
                        break;
                    case "totalFilledFields":
                        $(".progress__step").removeClass("is-active");
                        for (let totalFilledFields = 0; totalFilledFields < value; totalFilledFields++) {
                            $(".progress__step").eq(totalFilledFields).addClass("is-active");
                        }
                        $(".progress-count-js").text(tpp.totalFilledFields + "/" + tpp.totalFields);
                        if (parseInt(tpp.isProfileCompleted) == 1 || (parseInt(tpp.totalFilledFields) ==
                                parseInt(tpp.totalFields) - 1 && parseInt(tpp.generalAvailabilityCount) == 0)) {
                            $(".profile-setting-js").addClass("is-completed");
                        } else {
                            $(".profile-setting-js").removeClass("is-completed");
                        }
                        break;
                }
            });
        });
    };
})();
