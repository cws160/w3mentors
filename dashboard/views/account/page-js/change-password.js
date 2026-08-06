/* global fcom */

$(document).ready(function () {
    changePasswordForm();
});
(function () {
    var dv = '#changePassFrmBlock';
    changePasswordForm = function () {
        fcom.ajax(fcom.makeUrl('Account', 'changePasswordForm'), '', function (t) {
            $(dv).html(t);
        });
    };
    updatePassword = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Account', 'updatePassword'), fcom.frmData(frm), function (t) {
            changePasswordForm();
        });
    };
})();