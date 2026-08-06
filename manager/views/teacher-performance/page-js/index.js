/* global fcom, SITE_ROOT_URL */
$(document).ready(function () {
    search(document.srchForm);
    $("input[name='keyword']").autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'AutoCompleteJson'),
                    {keyword: request, user_is_teacher:$("input[name='user_is_teacher']").val()}, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                        value: item['user_id'], name: item['full_name']
                    };
                }));
            }, {process: false});
        },
        'select': function (item) {
            $("input[name='user_id']").val(item.value);
            $("input[name='keyword']").val(item.name);
        }
    });
    $("input[name='keyword']").keyup(function () {
        $("input[name='user_id']").val('');
    });
});
(function () {
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('TeacherPerformance', 'search'), fcom.frmData(frm), function (res) {
            $('#listing').html(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        document.srchForm.user_id.value = '';
        search(document.srchForm);
    };
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
})();	