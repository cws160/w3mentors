/* global fcom, langLbl, COMPLETED,  endLessonConfirmMsg, TOKEN */
(function () {

    loadIframe = function (meeting, joinFromApp, api) {
        var apiCall = api ? '/api' : '';
        meetUrl = apiCall + meeting.meet_join_url;
        if (joinFromApp) {
            window.open(meeting.meet_app_url, "_blank");
        } else if (parseInt(meeting.metool_iframe) != 1) {
            window.open(meetUrl, "_blank");
        } else {

            let html = '<div id="chat_box_div" style="width:100%;height:100%;max-width:100%;border:1px solid #CCCCCC;border-radius:5px;overflow:hidden;">';
            html += '<iframe  style="width:100%;height:100%;" src="' + meetUrl + '" allow="camera; microphone; fullscreen;display-capture" frameborder="0"></iframe>';
            html += '</div>';
            $("#lessonBox").html(html);
        }
    };

    joinLessonApp = function (lessonId, joinFromApp) {
        var action = fcom.makeUrl('Lessons', 'joinMeeting') + "?token=" + TOKEN;
        fcom.updateWithAjax(action, { lessonId: lessonId, joinFromApp: joinFromApp }, function (res) {
            loadIframe(res.meeting, joinFromApp, false);
            $('#endLesson').removeClass('d-none');
            $('#lessonBox').css('height', '100%');
        });
    };

    endLessonApp = function (lessonId) {
        if (confirm(endLessonConfirmMsg)) {
            var action = fcom.makeUrl('Lessons', 'endMeeting') + "?token=" + TOKEN;
            fcom.ajax(action, { lessonId: lessonId }, function (response) {
                window.location.href = fcom.makeUrl('Lessons', 'end', [lessonId]) + "?token=" + TOKEN;
            });
        }
    };

    checkStatusApp = function (lessonId) {
        setInterval(function () {
            var action = fcom.makeUrl('Lessons', 'checkStatus', [lessonId]) + '?token=' + TOKEN;
            fcom.ajax(action, '', function (response) { }, { process: false });
        }, 10000);
    };

    joinLesson = function (lessonId, joinFromApp) {
        var data = { lessonId: lessonId, joinFromApp: joinFromApp };
        fcom.updateWithAjax(fcom.makeUrl('Lessons', 'joinMeeting'), data, function (res) {
            if (!joinFromApp) {
                $('#lessonBox').removeClass('sesson-window__content');
                $('#lessonBox').addClass('session-window__frame').show();
            }
            $('#endLesson').removeClass('d-none');
            loadIframe(res.meeting, joinFromApp, false);
        });
    };

    checkStatus = function (lessonId) {
        statusInterval = setInterval(function () {
            var action = fcom.makeUrl('Lessons', 'checkStatus', [lessonId]);
            fcom.updateWithAjax(action, '', function (res) { }, { process: false });
        }, 10000);
    };

    playbackLesson = function (lessonId) {
        fcom.updateWithAjax(fcom.makeUrl('Lessons', 'playbackLesson'), { lessonId: lessonId }, function (res) {
            $('.lessonBox').removeClass('sesson-window__content').addClass('session-window__frame').show();
            let html = '<div id="chat_box_div" style="width:100%;height:100%;max-width:100%;border:1px solid #CCCCCC;border-radius:5px;overflow:hidden;">';
            html += '<iframe  style="width:100%;height:100%;" src="' + res.playback_url + '" allow="camera; microphone; fullscreen;display-capture" frameborder="0"></iframe>';
            html += '</div>';
            $("#lessonBox").html(html);
        });
    };
})();