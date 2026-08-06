/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.frmSearchPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (form) {
        fcom.ajax(fcom.makeUrl('Commission', 'searchHistory'), fcom.frmData(form), function (response) {
            $('#listing').html(response);
        });
    };
})();	