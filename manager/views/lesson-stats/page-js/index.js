/* global fcom */

$(document).ready(function () {
    search(document.srchForm);
    $('input[name="user"]').autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'AutoCompleteJson'),
                    {keyword: request}, function (result) {
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
            $("input[name='user']").val(item.name);
        }
    });
    $("input[name='user']").keyup(function () {
        $("input[name='user_id']").val('');
    });
});
(function () {
    search = function (form) {
        fcom.ajax(fcom.makeUrl('LessonStats', 'search'), fcom.frmData(form), function (response) {
            $("#listing").html(response);
        });
    };
    clearSearch = function () {
        document.srchForm.user_id.value = '';
        document.srchForm.reset();
        search(document.srchForm);
    };
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    goToViewNextPage = function (pageno) {
        var frm = document.viewLogPaging;
        $(frm.pageno).val(pageno);
        getLogData(fcom.frmData(frm));
    };

  
})();