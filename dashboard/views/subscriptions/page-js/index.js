/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */
$(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Subscriptions', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.frmSubsSearch.reset();
        search(document.frmSubsSearch);
    };
    cancelForm = function (ordsubId) {
        fcom.ajax(fcom.makeUrl('Subscriptions', 'cancelForm'), { ordsubId: ordsubId }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg' });
        });
    };
    cancelSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.ajax(fcom.makeUrl('Subscriptions', 'cancelSetup'), fcom.frmData(frm), function (response) {
            $.w3mentorsmodal.close();
            search(document.frmSearchPaging);
        });
    };
    cancelSubscriptionPlan = function () {
        fcom.ajax(fcom.makeUrl('Subscriptions', 'cancelSubscriptionPlan'), {}, function (response) {
            $.w3mentorsmodal.close();
        });
    };
    search(document.frmSubsSearch);
});
