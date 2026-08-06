
(function () {
    updateEmail = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.ajax(fcom.makeUrl('GuestUser', 'updateEmail'), data, function (ans) {
            if (ans.redirectUrl) {
                setTimeout(function () {
                    window.location.href = ans.redirectUrl
                }, 1000);
            }
            frm.reset();
        }, {fOutMode: 'json'});
    };
})();
