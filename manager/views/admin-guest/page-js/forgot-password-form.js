/* global grecaptcha, fcom */
(function () {
    forgotPassword = function (frm, v) {
        if (!$(frm).validate()) {
            return;
        }
        if (!v.isValid()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl("adminGuest", "forgotPassword"), data, function (response) { });
        if ($(".g-recaptcha").html()) {
            grecaptcha.reset();
        }
        return false;
    };
})();
