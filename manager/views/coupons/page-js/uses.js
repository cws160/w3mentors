/* global fcom, langLbl, dv */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    search = function (form) {
        fcom.ajax(fcom.makeUrl('Coupons', 'searchUses'), fcom.frmData(form), function (res) {
            $('#listing').html(res);
        });
    };
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.page).val(pageno);
        search(frm);
    };
})();