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
        fcom.ajax(fcom.makeUrl('Subscriptions', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    viewLesson = function (ordlesId) {
        fcom.ajax(fcom.makeUrl('Subscriptions', 'view'), {ordlesId: ordlesId}, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        $("input[name='ordles_tlang_id']").val('');
        search(document.srchForm);
    };
})();
