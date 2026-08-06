/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    search = function (form) {
        fcom.ajax(fcom.makeUrl('Forum', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };

    reloadList = function () {
        search(document.srchFormPaging);
    };

    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };

    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };


    view = function (quesId) {
        fcom.ajax(fcom.makeUrl('Forum', 'view', [quesId]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };

    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Forum', 'deleteRecord'), {id: id}, function (res) {
            reloadList();
        });
    };
})();
