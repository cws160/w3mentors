/* global fcom, currentPage */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (form) {
        var dv = $('#ordersListing');
        fcom.ajax(fcom.makeUrl('Wallet', 'search'), fcom.frmData(form), function (res) {
            dv.html(res);
        });
    };
    reloadOrderList = function () {
        search(document.srchFormPaging);
    };
    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
})();
