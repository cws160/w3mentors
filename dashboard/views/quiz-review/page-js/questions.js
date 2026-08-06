$(function () {
    view = function (id) {
        fcom.updateWithAjax(fcom.makeUrl('QuizReview', 'view'), { id }, function (response) {
            $('.quizPanelJs').html(response.html);
            $('.quesNumJs').html(response.questionNumber);
        });
    };
    getQuestion = function (id, next, quesId) {
        fcom.updateWithAjax(fcom.makeUrl('QuizReview', 'setQuestion'), { 'id': id, 'next': next, 'ques_id': quesId }, function (res) {
            view(id);
        });
    };
    next = function (id) {
        $('.btnNextJs').attr('disabled', 'disabled');
        getQuestion(id, 1);
    };
    previous = function (id) {
        $('.btnPrevJs').attr('disabled', 'disabled');
        getQuestion(id, 0);
    };
    getByQuesId = function (id, quesId) {
        getQuestion(id, 0, quesId);
    };
    finish = function (id) {
        fcom.updateWithAjax(fcom.makeUrl('QuizReview', 'finish'), { 'id': id }, function (res) {
            window.location = fcom.makeUrl('QuizReview', 'index', [id]);
        });
    };
    submitAndFinish = function (id, type) {
        if (!confirm(langLbl.confirmQuizReviewComplete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('QuizReview', 'finish'), { 'id': id, 'submit' : 1 }, function (res) {
            window.location = fcom.makeUrl(type);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('QuizReview', 'setup'), fcom.frmData(frm), function (res) {
            
        });
    };
});