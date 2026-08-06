/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    search = function (form) {
        fcom.ajax(fcom.makeUrl('Forum', 'SearchComment'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };


    goToSearchPage = function (pageno) {
         var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };

})();
