/* global fcom */

$(function () {
    var dv = '#listItems';
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Issues', 'search'), fcom.frmData(frm), function (t) {
            $(dv).html(t);
        });
    };
    clearSearch = function () {
        document.frmSrch.reset();
        search(document.frmSrch);
    }
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging
        frm.pageno.value = pageno;
        search(frm);
    };
    search(document.frmSrch);
});
