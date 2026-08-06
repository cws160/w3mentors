/* global fcom */
$(document).ready(function () {
    searchLesson(document.srchForm);
    $("input[name='ordles_tlang']").autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('TeachLanguage', 'autoCompleteJson'),
                    {keyword: request}, function (result) {
                        response($.map(result.data, function (item, key) {
                            return {
                                label: escapeHtml(item),
                                value: key,
                                name: item
                            };
                        }));
            }, {process: false});
        },
        'select': function (item) {
            $("input[name='ordles_tlang_id']").val(item.value);
            $("input[name='ordles_tlang']").val(item.name);
        }
    });
    $("input[name='ordles_tlang']").keyup(function () {
        $("input[name='ordles_tlang_id']").val('');
    });
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        searchLesson(frm);
    };
    reloadList = function () {
        searchLesson(document.srchFormPaging);
    };
    searchLesson = function (form) {
        fcom.ajax(fcom.makeUrl('Lessons', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    viewLesson = function (ordlesId) {
        fcom.ajax(fcom.makeUrl('Lessons', 'view'), {ordlesId: ordlesId}, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        $("input[name='ordles_tlang_id']").val('');
        searchLesson(document.srchForm);
    };
})();
