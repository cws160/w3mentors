
/* global fcom, SITE_ROOT_FRONT_URL */
$(document).ready(function () {
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Quizzes', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        document.srchForm.teacher_id.value = '';
        search(document.srchForm);
    };
    $("input[name='teacher']").autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'autoCompleteJson'), {
                keyword: request, user_is_teacher : 1
            }, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                        value: item['user_id'], name: item['full_name']
                    };
                }));
            });
        },
        'select': function (item) {
            $("input[name='teacher_id']").val(item.value);
            $("input[name='teacher']").val(item.name);
        }
    });
    $("input[name='teacher']").keyup(function () {
        $("input[name='teacher_id']").val('');
    });
    goToSearchPage = function (page) {
        var frm = document.frmPaging;
        $(frm.pageno).val(page);
        search(frm);
    };
    view = function (id) {
        fcom.ajax(fcom.makeUrl('Quizzes', 'view', [id]), '', function (res) {
            $.w3mentorsmodal(res);
        });
    };
    search(document.srchForm);
});