/* global fcom */
$(document).ready(function () {
    searchOrder(document.srchForm);
    $('input[name=\'order_user\']').autocomplete({
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
            $("input[name='order_user_id']").val(item.value);
            $("input[name='order_user']").val(item.name);
        }
    });
    $('input[name=\'order_user\']').keyup(function () {
        $('input[name=\'order_user_id\']').val('');
    });
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        searchOrder(frm);
    }
    reloadList = function () {
        searchOrder(document.srchFormPaging);
    };
    searchOrder = function (form) {
        fcom.ajax(fcom.makeUrl('Orders', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    cancelOrder = function (orderId) {
        if (!confirm(langLbl.confirmCancel)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Orders', 'cancelOrder'), {orderId: orderId}, function (res) {
            searchOrder(document.srchForm);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        $('input[name=\'order_user_id\']').val('');
        searchOrder(document.srchForm);
    };
})();
