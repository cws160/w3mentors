$(function () {
    start = function (id) {
        fcom.updateWithAjax(fcom.makeUrl('QuizReview', 'start'), { id }, function (response) {
            window.location = fcom.makeUrl('QuizReview', 'questions', [id]);
        });
    };
});