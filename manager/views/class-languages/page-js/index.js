/* global fcom, SITE_ROOT_URL */
$(document).ready(function () {
    search(document.srchForm);
    $("input[name='grpcls_tlang']").autocomplete({
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
            $("input[name='grpcls_tlang_id']").val(item.value);
            $("input[name='grpcls_tlang']").val(item.name);
        }
    });
    $("input[name='grpcls_tlang']").keyup(function () {
        $("input[name='grpcls_tlang_id']").val('');
    });
});
(function () {
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('ClassLanguages', 'search'), fcom.frmData(frm), function (res) {
            $('#listing').html(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        document.srchForm.grpcls_tlang_id.value = '';
        search(document.srchForm);
    };
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    viewAll = function (teachlangId) {
        var newForm = $('<form>', {'method': 'POST', 'action': fcom.makeUrl('Classes'), 'target': '_top'});
        newForm.append($('<input>', {'name': 'ordcls_tlang_id', 'value': teachlangId, 'type': 'hidden'}));
        newForm.append($('<input>', {'name': 'order_payment_status', 'value': 1, 'type': 'hidden'}));
        newForm.appendTo('body');
        newForm.submit();
    };
})();	