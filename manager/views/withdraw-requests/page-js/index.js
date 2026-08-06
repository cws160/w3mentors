/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        search(frm);
    };

    searchRequest = function (form) {
        if (!$(form).validate()) {
            return;
        }
        search(form);
    };
    search = function (form) {
        fcom.ajax(fcom.makeUrl('WithdrawRequests', 'search'), fcom.frmData(form), function (res) {
            $('#listing').html(res);
        });
    };
    updateStatus = function (id, status, statusName) {
        var data = 'id=' + id + '&status=' + status;
        if (confirm(langLbl.DoYouWantTo + ' ' + statusName + ' ' + langLbl.theRequest)) {
            fcom.updateWithAjax(fcom.makeUrl('WithdrawRequests', 'updateStatus'), data, function (t) {
                document.srchForm.page.value = document.srchFormPaging.page.value;
                search(document.srchForm);
            });
        }
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchRequest(document.srchForm);
    };
})();
