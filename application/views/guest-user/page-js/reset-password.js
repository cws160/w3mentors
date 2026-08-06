/* global fcom */

(function () {
    resetpwd = function (frm, v) {
        v.validate();
        if (!v.isValid()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'resetPasswordSetup'), fcom.frmData(frm), function (t) {
            setTimeout(function () {
                location.href = fcom.makeUrl('GuestUser', 'loginForm');
            }, 3000);
        });
    };
    toggleResetPassword = function (e) {
        var passType = $("input[name='new_password']").attr("type");
        if (passType == "text") {
            $("input[name='new_password']").attr("type", "password");
            $(e).html($(e).attr("data-show-caption"));
        } else {
            $("input[name='new_password']").attr("type", "text");
            $(e).html($(e).attr("data-hide-caption"));
        }
    };
})();
