$(function () {
    retakeQuiz = function (id) {
        if (!confirm(langLbl.confirmRetake)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('UserQuiz', 'retake'), { id }, function (response) {
            window.location = fcom.makeUrl('UserQuiz', 'index', [response.id]);
        });
    };
});