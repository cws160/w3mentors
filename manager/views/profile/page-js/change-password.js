/* global fcom */
$(function () {
    checkPassword = function (str) {
        var re = /^(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z])(?=.*).{8,}$/;
        return re.test(str);
    };
    changePassword = function (frm, v) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.ajax(fcom.makeUrl("profile", "updatePassword"), fcom.frmData(frm), function (t) {
            setTimeout(function () {
                location.href = fcom.makeUrl('profile', 'changePassword');
            }, 3000);
        });
        return false;
    };
});