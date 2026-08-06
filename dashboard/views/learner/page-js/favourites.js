/* global fcom */

$(document).ready(function () {
    searchfavorites(document.frmFavSrch);
});
searchfavorites = function (frm) {
    fcom.ajax(fcom.makeUrl('Learner', 'getFavourites'), fcom.frmData(frm), function (t) {
        $('#listItems').html(t);
    });
};
clearSearch = function () {
    document.frmFavSrch.reset();
    searchfavorites(document.frmFavSrch);
};
goToSearchPage = function (page) {
    var frm = document.frmFavSearchPaging;
    $(frm.page).val(page);
    searchfavorites(frm);
};