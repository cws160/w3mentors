/* global fcom */
$(document).ready(function () {
    search();
});
(function () {
    var dv = '#listing';
    search = function () {
        fcom.ajax(fcom.makeUrl('Certificates', 'search'), '', function (res) {
            $(dv).html(res);
        });
    };
    updateStatus = function (certpl_code, status) {
        if (confirm(langLbl.confirmUpdateStatus)) {
            fcom.updateWithAjax(fcom.makeUrl('Certificates', 'updateStatus', [certpl_code, status]), '', function (res) {
                search();
            });
        }
    };
})();
