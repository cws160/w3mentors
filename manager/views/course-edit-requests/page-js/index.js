/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);

    $('input[name=\'teacher\']').autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'AutoCompleteJson'), {
                keyword: request,
                user_is_teacher: '1'
            }, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                        value: item['user_id'], name: item['full_name']
                    };
                }));
            }, {process: false});
        },
        'select': function (item) {
            $("input[name='teacher_id']").val(item.value);
            $("input[name='teacher']").val(item.name);
        }
    });
    $('input[name=\'teacher\']').keyup(function () {
        $('input[name=\'teacher_id\']').val('');
    });
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.frmPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (form) {
        fcom.ajax(fcom.makeUrl('CourseEditRequests', 'search'), fcom.frmData(form), function (response) {
            $('#listing').html(response);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        $('input[name="teacher_id"]').val('');
        search(document.srchForm);
    };
    view = function (reqId) {
        fcom.ajax(fcom.makeUrl('CourseEditRequests', 'view', [reqId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    updateStatus = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('CourseEditRequests', 'updateStatus'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            search(document.frmPaging);
        });
    };
    showHideCommentBox = function (val) {
        if (val == EDIT_REQUEST_DECLINED) {
            $('#remarkField').show();
        } else {
            $('#remarkField').hide();
            $('textarea[name="coedre_comment"]').val('');
        }
    };
    changeStatusForm = function (reqId) {
        fcom.ajax(fcom.makeUrl('CourseEditRequests', 'form', [reqId]), '', function (response) {
            $.w3mentorsmodal(response);
            showHideCommentBox();
        });
    };
    userLogin = function (userId, courseId) {
        fcom.updateWithAjax(fcom.makeUrl('Users', 'login', [userId]), '', function (res) {
            window.open(fcom.makeUrl('CoursePreview', 'index', [courseId], SITE_ROOT_DASHBOARD_URL), "_blank");
        });
    };
})();	