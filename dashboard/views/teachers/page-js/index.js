/* global fcom */
$(function () {
    var dv = '#listItems';
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Teachers', 'search'), fcom.frmData(frm), function (res) {
            $(dv).html(res);
        });
    };
    clearSearch = function () {
        document.frmSrch.reset();
        search(document.frmSrch);
    }
    goToSearchPage = function (page) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(page);
        search(frm);
    };
    messageForm = function (id) {
        fcom.ajax(fcom.makeUrl('Teachers', 'messageForm', [id]), '', function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    messageSetup = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        fcom.updateWithAjax(fcom.makeUrl('Teachers', 'messageSetup'), fcom.frmData(frm), function (res) {
            search(document.frmSrch);
            $.w3mentorsmodal.close();
        });
    };
    search(document.frmSrch);
});
