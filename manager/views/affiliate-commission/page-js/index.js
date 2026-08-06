/* global fcom, langLbl */
$(document).ready(function () {
    search(document.commSearch);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.frmCommPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (form) {
        fcom.ajax(fcom.makeUrl('AffiliateCommission', 'search'), fcom.frmData(form), function (response) {
            $('#listing').html(response);
        });
    };
    commissionForm = function (commissionId) {
        fcom.ajax(fcom.makeUrl('AffiliateCommission', 'form', [commissionId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('AffiliateCommission', 'setup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            search(document.frmCommPaging);
        });
    };
    viewHistory = function (userId) {
        fcom.ajax(fcom.makeUrl('AffiliateCommission', 'viewHistory', [userId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    clearSearch = function () {
        document.commSearch.reset();
        search(document.commSearch);
    };
    remove = function (id) {
        if (confirm(langLbl.confirmRemove)) {
            fcom.updateWithAjax(fcom.makeUrl('AffiliateCommission', 'remove'), { id }, function (res) {
                search(document.frmCommPaging);
            });
        }
    };
})();	