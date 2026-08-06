
/* global fcom, grecaptcha */
(function () {
    forgotPassword = function () {
        fcom.ajax(fcom.makeUrl('GuestUser', 'forgotPassword'), '', function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    forgotPasswordSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.process();
        fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'forgotPasswordSetup'), fcom.frmData(frm), function (res) {
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.reset();
            }
            if (res.status == 1) {
                frm.reset();
            }
        }, {failed: true});
    };
})();