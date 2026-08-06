/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */
$(function () {
    form = function (id) {
        fcom.ajax(fcom.makeUrl('Quizzes', 'basic'), {id}, function (resp) {
            $('#pageContentJs').html(resp);
            var eid = $('textarea[name="quiz_detail"]').attr('id');
            window["oEdit_" + eid].disableFocusOnLoad = true;
            fcom.setEditorLayout(siteLangId);
            getCompletedStatus(id);
        });
    };
    setType = function (value) {
        $('#quizTypeJs').val(value);
    };
    setup = function () {
        var frm = document.frmQuiz;
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('Quizzes', 'setup'), data, function (res) {
            questions(res.quizId);
            getCompletedStatus(res.quizId);
            window.history.pushState('page', document.title, fcom.makeUrl('Quizzes', 'form', [res.quizId]));
        });
    };
    /* Questions [ */
    questions = function (id) {
        fcom.ajax(fcom.makeUrl('Quizzes', 'questions'), { id }, function (resp) {
            $('#pageContentJs').html(resp);
            getCompletedStatus(id);
        });
    };
    addQuestions = function (id) {
        fcom.ajax(fcom.makeUrl('QuizQuestions', 'index'), { id }, function (resp) {
            $.w3mentorsmodal(resp, { 'size': 'modal-xl'});
            searchQuestions(document.frmQuesSearch);
        });
    };
    searchQuestions = function (frm, page = 1) {
        document.frmQuesSearch.pageno.value = page;
        fcom.updateWithAjax(fcom.makeUrl('QuizQuestions', 'search'), fcom.frmData(frm), function (res) {
            if (page > 1) {
                $('#listingJs').append(res.html);
            } else {
                $('#listingJs').html(res.html);
            }
            if (res.loadMore == 1) {
                $('.loadMoreJs a').data('page', res.nextPage);
                $('.loadMoreJs').show();
            } else {
                $('.loadMoreJs').hide();
            }
        });
    };
    goToPage = function (_obj) {
        searchQuestions(document.frmQuesSearch, $(_obj).data('page'));
    }
    clearSearch = function() {
        document.frmQuesSearch.reset();
        getSubcategories(0, "#quesSubCateJs");
        searchQuestions(document.frmQuesSearch);
    };
    attachQuestions = function () {
        var frm = document.frmQuestions;
        fcom.updateWithAjax(fcom.makeUrl('QuizQuestions', 'setup'), fcom.frmData(frm), function (res) {
            questions(res.quizId);
            $.w3mentorsmodal.close();
            getCompletedStatus(res.quizId);
        });
    };
    remove = function(quizId, quesId) {
        if (!confirm(langLbl.confirmRemove)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('QuizQuestions', 'remove'), { quizId, quesId }, function (res) {
            questions(quizId);
            getCompletedStatus(quizId);
        });
    };
    updateOrder = function (id) {
        var order = [''];
        $('.sortableJs tr').each(function () {
            order.push($(this).data('id'));
        });
        fcom.ajax(fcom.makeUrl('QuizQuestions', 'updateOrder'), {
            'order': order,
            'id': id
        }, function (res) {
            questions(id);
            getCompletedStatus(id);
        });
    };
    /* ] */
    /* Settings [ */
    settings = function (id) {
        fcom.ajax(fcom.makeUrl('Quizzes', 'setting'), { id }, function (resp) {
            $('#pageContentJs').html(resp);
            getCompletedStatus(id);
        });
    };
    settingsForm = function (count, id) {
        if (count < 1) {
            fcom.error(langLbl.selectQuestions);
            return;
        }
        settings(id);
    };
    setupSettings = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Quizzes', 'setupSettings'), fcom.frmData(frm), function (res) {
            getCompletedStatus(frm.quiz_id.value);
            window.location = fcom.makeUrl('Quizzes');
        });
    }
    /* ] */
    getCompletedStatus = function (id) {
        fcom.updateWithAjax(fcom.makeUrl('Quizzes', 'getCompletedStatus', [id]), '', function (res) {
            $('.generalTabJs, .questionsTabJs, .settingsTabJs').removeClass('is-completed').addClass('is-progress');
            if (res.general == 1) {
                $('.generalTabJs').removeClass('is-progress').addClass('is-completed');
            }
            if (res.questions == 1) {
                $('.questionsTabJs').removeClass('is-progress').addClass('is-completed');
            }
            if (res.settings == 1) {
                $('.settingsTabJs').removeClass('is-progress').addClass('is-completed');
            }
        }, {'process' : false});
    };
});