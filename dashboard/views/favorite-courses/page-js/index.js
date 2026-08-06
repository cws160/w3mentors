/* global fcom */
$(document).ready(function () {
    search();

    goToSearchPage = function (page) {
        document.frmSrch.pageno.value = page;
        search();
    }

    unfavoriteCourse = function(id) {
        fcom.updateWithAjax(fcom.makeUrl('Courses', 'toggleFavorite'), {'course_id' : id, 'status' : 1}, function (t) {
            search();
        });
    }
});
dv = '#listItems';
function search() {
    var frm = document.frmSrch;
    fcom.ajax(fcom.makeUrl('FavoriteCourses', 'search'), fcom.frmData(frm), function (t) {
        $(dv).html(t);
    });
}