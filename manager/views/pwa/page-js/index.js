(function () {
    pwaSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = new FormData(frm)
        fcom.ajaxMultipart(fcom.makeUrl('Pwa', 'setup'), data, function (res) {
            setTimeout(function () {
                window.location.reload();
            }, 1000);
        });
    };
})();