/* global fcom, langLbl */
$(function () {
    var dv = '#listItems';
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('GroupClasses', 'searchLearners'), fcom.frmData(frm), function (t) {
            $(dv).html(t);
        });
    };

    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search(document.srchForm);
});