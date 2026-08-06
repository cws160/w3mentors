/* global fcom */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    reloadList = function () {
        search(document.srchFormPaging);
    };
    search = function (form) {
        fcom.ajax(fcom.makeUrl('OrderSubscriptionPlans', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    view = function (ordSplanId) {
        fcom.ajax(fcom.makeUrl('OrderSubscriptionPlans', 'view'), {ordSplanId: ordSplanId}, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
})();
