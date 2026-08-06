/* global fcom */
$(document).ready(function () {
    searchClass(document.srchForm);
    $("input[name='ordcls_tlang']").autocomplete({
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
            $("input[name='ordcls_tlang_id']").val(item.value);
            $("input[name='ordcls_tlang']").val(item.name);
        }
    });
    $("input[name='ordcls_tlang']").keyup(function () {
        $("input[name='ordcls_tlang_id']").val('');
    });
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        searchClass(frm);
    };
    reloadList = function () {
        searchClass(document.srchFormPaging);
    };
    searchClass = function (form) {
        var data = data = fcom.frmData(form);
        fcom.ajax(fcom.makeUrl('Classes', 'search'), data, function (res) {
            $(dv).html(res);
        });
    };
    viewClass = function (ordclsId) {
        fcom.ajax(fcom.makeUrl('Classes', 'view'), {ordclsId: ordclsId}, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        $("input[name='ordcls_tlang_id']").val('');
        searchClass(document.srchForm);
    };
})();
