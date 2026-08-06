/* global fcom, langLbl */
(function () {
    login = function (frm, v) {
        if (!$(frm).validate()) {
            return;
        }
        if (!v.isValid()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('AdminGuest', 'login'), fcom.frmData(frm), function (response) {
            window.location.href = response.redirectUrl;
        });
    };
})();
