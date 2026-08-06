var $skip = true;
$(function () {
    view = function (id) {
        fcom.updateWithAjax(fcom.makeUrl('UserQuiz', 'view'), { id }, function (response) {
            if (response.expired == 1) {
                setTimeout(function () {
                    window.location = fcom.makeUrl('UserQuiz', 'completed', [id]);
                }, 2000);
            } else {
                $('.quizPanelJs').html(response.html);
                $('.quesNumJs').html(response.questionNumber);
                $('.totalMarksJs').html(response.totalMarks);
                $('.progressJs').html(response.progressPercent);
                $('.progressBarJs').css({ 'width': response.progressPercent });
                $('.progressBarJs').hide();
                if (response.progress > 0) {
                    $('.progressBarJs').show();
                }
            }
        });
    };
    save = function (frm) {
        saveAndNext(frm, 0);
    };
    saveAndNext = function (frm, next = 1) {
        if (!$(frm).validate()) {
            return;
        }
        $('.btnNextJs').attr('disabled', 'disabled');
        fcom.updateWithAjax(fcom.makeUrl('UserQuiz', 'saveAndNext', [next]), fcom.frmData(frm), function (res) {
            (res.status == 1) ? view(res.id) : $('.btnNextJs').attr('disabled', false);
        }, { 'failed': true });
    };
    skipAndNext = function (id) {
        if ($skip == true) {
            $skip = false;
            fcom.updateWithAjax(fcom.makeUrl('UserQuiz', 'setQuestion'), { 'id': id, 'next': 1 }, function (res) {
                view(id);
                $skip = true;
            });
        }
    };
    previous = function (id) {
        $('.btnPrevJs').attr('disabled', 'disabled');
        fcom.updateWithAjax(fcom.makeUrl('UserQuiz', 'setQuestion'), { 'id': id, 'next': 0 }, function (res) {
            (res.status == 1) ? view(id) : $('.btnPrevJs').attr('disabled', false);
        }, { 'failed': true });
    };
    getByQuesId = function (id, quesId) {
        fcom.updateWithAjax(fcom.makeUrl('UserQuiz', 'setQuestion'), { 'id': id, 'next': 0, 'ques_id': quesId }, function (res) {
            view(id);
        });
    };
    saveAndFinish = function (prompt = true) {
        if (prompt) {
            if (!confirm(langLbl.confirmQuizComplete)) {
                return;
            }
        }
        var frm = document.frmQuiz;
        finish(fcom.frmData(frm));
    };
    finish = function (data = '') {
        if (data == '') {
            data = 'ques_attempt_id=' + document.frmQuiz.ques_attempt_id.value;
        }
        fcom.updateWithAjax(fcom.makeUrl('UserQuiz', 'saveAndFinish'), data, function (res) {
            window.location = fcom.makeUrl('UserQuiz', 'completed', [res.id]);
        });
    };
});