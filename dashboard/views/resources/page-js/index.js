$(function () {
    goToSearchPage = function (page) {
        var frm = document.frmPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Resources', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.frmSearch.reset();
        search(document.frmSearch);
    };
    form = function () {
        fcom.ajax(fcom.makeUrl('Resources', 'form'), '', function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-md' });
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = new FormData(frm);
        fcom.ajaxMultipart(fcom.makeUrl('Resources', 'setup'), data, function (res) {
            $.w3mentorsmodal.close();
            search(document.frmSearch);
        }, { fOutMode: 'json' });
    };

    remove = function (resrcId) {
        if (confirm(langLbl.confirmRemove)) {
            fcom.ajax(fcom.makeUrl('Resources', 'delete', [resrcId]), '', function (res) {
                search(document.frmSearch);
            });
        }
    }
    search(document.frmSearch);
});
