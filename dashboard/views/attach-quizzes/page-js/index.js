$(function () {
    searchQuizzes = function (frm, page = 1) {
        document.srchForm.pageno.value = page;
        fcom.updateWithAjax(fcom.makeUrl('AttachQuizzes', 'search'), fcom.frmData(frm), function (res) {
            if (page > 1) {
                $('#quiz-listing tbody').append(res.html);
            } else {
                $('#quiz-listing tbody').html(res.html);
            }
            if (res.loadMore == 1) {
                $('.loadMoreJs a').data('page', res.nextPage);
                $('.loadMoreJs').show();
            } else {
                $('.loadMoreJs').hide();
            }
        });
    };
    clearQuizSearch = function () {
        document.srchForm.reset();
        searchQuizzes(document.srchForm);
    };
    attachQuizzes = function () {
        var frm = document.frmQuizLink;
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('AttachQuizzes', 'setup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            if (document.frmSearchPaging) {
                search(document.frmSearchPaging);
                return;
            }
            window.location.reload();
        });
    };
    goToQuizPage = function (_obj) {
        searchQuizzes(document.srchForm, $(_obj).data('page'));
    }
    quizListing = function (recordId, recordType) {
        fcom.ajax(fcom.makeUrl('AttachQuizzes', 'index'), { recordId, recordType }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-xl'});
            searchQuizzes(document.srchForm);
        });
    };
    viewQuizzes = function (recordId, recordType) {
        $.w3mentorsmodal.close();
        fcom.ajax(fcom.makeUrl('AttachQuizzes', 'view'), { recordId, recordType }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-xl'});
        });
    };
    removeQuiz = function (id) {
        if (!confirm(langLbl.confirmRemove)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('AttachQuizzes', 'delete'), { id }, function (response) {
            $('.quizRow' + id + ', .userListJs' + id).remove();
            $('.noRecordJS').hide();
            if ($('.quizRowJs').length == 0) {
                $('.noRecordJS').show();
            }
            if (document.frmSearchPaging) {
                search(document.frmSearchPaging);
                return;
            }
            window.location.reload();
        });
    };
    view = function (id) {
        if ($('.userListJs' + id).hasClass('is-active')) {
            $('.userListJs' + id).hide().removeClass('is-active').removeClass('is-expanded');
            $('.quizRowJs').find('.action-trigger').removeClass('is-active');
            return;
        } else {
            $('.userListJs').removeClass('is-active').removeClass('is-expanded').hide();
            $('.quizRowJs').find('.action-trigger').removeClass('is-active');
            $('.userListJs' + id).addClass('is-active').addClass('is-expanded').show();
            $('.quizRow' + id).find('.action-trigger').addClass('is-active');
        }
    };
    setQuiz = function (id, obj) {
        $('.quizTitleJs').text($(obj).data('title'));
        $('input[name="course_quilin_id"]').val(id);
        $('.attachedQuizJs').show();
        $.w3mentorsmodal.close();
    };
});
