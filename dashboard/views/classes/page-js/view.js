
/* global fcom, langLbl, COMPLETED, LEARNER, CANCELLED, endClassConfirmMsg, TOKEN */
(function () {

    loadIframe = function (meeting, joinFromApp) {
        if (joinFromApp) {
            window.open(meeting.meet_app_url, "_blank");
        } else if (parseInt(meeting.metool_iframe) != 1) {
            window.open(meeting.meet_join_url, "_blank");
        } else {
            let html = '<div id="chat_box_div" style="width:100%;height:100%;max-width:100%;border:1px solid #CCCCCC;border-radius:5px;overflow:hidden;">';
            html += '<iframe  style="width:100%;height:100%;" src="' + meeting.meet_join_url + '" allow="camera; microphone; fullscreen;display-capture" frameborder="0"></iframe>';
            html += '</div>';
            $("#classBox").html(html);
        }
    };

    joinMeetingApp = function (classId, joinFromApp) {
        var action = fcom.makeUrl('Classes', 'joinMeeting') + "?token=" + TOKEN;
        fcom.updateWithAjax(action, {classId: classId}, function (res) {
            loadIframe(res.meeting, joinFromApp);
            $("#endClass").removeClass('d-none');
            $("#classBox").css('height', '100%');
        });
    };

    endMeetingApp = function (classId) {
        if (confirm(endClassConfirmMsg)) {
            var action = fcom.makeUrl('Classes', 'endMeeting') + "?token=" + TOKEN;
            fcom.ajax(action, {classId: classId}, function (response) {
                window.location.href = fcom.makeUrl('Classes', 'end', [classId]) + "?token=" + TOKEN;
            });
        }
    };

    checkStatusApp = function (classId) {
        setInterval(function () {
            var action = fcom.makeUrl('Classes', 'checkStatus', [classId]) + '?token=' + TOKEN;
            fcom.updateWithAjax(action, '', function (response) {}, {process: false});
        }, 10000);
    };

    joinMeeting = function (classId, joinFromApp) {
        fcom.updateWithAjax(fcom.makeUrl('Classes', 'joinMeeting'), {classId: classId}, function (res) {
            if (!joinFromApp) {
                $('#classBox').removeClass('sesson-window__content');
                $('#classBox').addClass('session-window__frame').show();
            }
            $("#endClass").removeClass('d-none');
            loadIframe(res.meeting, joinFromApp);
        });
    };

    checkStatus = function (classId) {
        statusInterval = setInterval(function () {
            var action = fcom.makeUrl('Classes', 'checkStatus', [classId]);
            fcom.updateWithAjax(action, '', function (response) { }, {process: false});
        }, 10000);
    };

    playbackClass = function (classId) {
        fcom.updateWithAjax(fcom.makeUrl('Classes', 'playbackClass'), {classId: classId}, function (res) {
            $('.classBox').removeClass('sesson-window__content').addClass('session-window__frame').show();
            let html = '<div id="chat_box_div" style="width:100%;height:100%;max-width:100%;border:1px solid #CCCCCC;border-radius:5px;overflow:hidden;">';
            html += '<iframe  style="width:100%;height:100%;" src="' + res.playback_url + '" allow="camera; microphone; fullscreen;display-capture" frameborder="0"></iframe>';
            html += '</div>';
            $("#classBox").html(html);
        });
    };

})();
