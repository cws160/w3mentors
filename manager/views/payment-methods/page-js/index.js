/* global fcom, langLbl */
$(document).ready(function () {
    searchGateway(document.frmGatewaySearch);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#pMethodListing';
    reloadList = function () {
        var frm = document.frmGatewaySearch;
        searchGateway(frm);
    };
    searchGateway = function (form) {
        fcom.ajax(fcom.makeUrl('PaymentMethods', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    txnfeeForm = function (id) {
        fcom.ajax(fcom.makeUrl('PaymentMethods', 'txnfeeForm'), {'id': id}, function (res) {
            fcom.updatePopupContent(res);
        });
    };
    txnfeeSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('PaymentMethods', 'txnfeeSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
        });
    };
    settingForm = function (id) {
        fcom.ajax(fcom.makeUrl('PaymentMethods', 'settingForm'), {id: id}, function (t) {
            fcom.updatePopupContent(t);
        });
    };
    settingSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('PaymentMethods', 'settingSetup'), fcom.frmData(frm), function (t) {
            reloadList();
            if (t.langId > 0) {
                editGatewayLangForm(t.pMethodId, t.langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    setupPaymentSettings = function (frm, code) {
        if (!$(frm).validate())
            return;
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl(code + '-settings', 'setup'), data, function (t) {
            $.w3mentorsmodal.close();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var pmethodId = parseInt(obj.id);
        var data = 'pmethodId=' + pmethodId + '&status=' + active;
        fcom.ajax(fcom.makeUrl('PaymentMethods', 'changeStatus'), data, function (res) {
            $(obj).removeClass("inactive");
            $(obj).addClass("active");
            $(".status_" + pmethodId).attr('onclick', 'inactiveStatus(this)');
            setTimeout(function () {
                reloadList();
            }, 1000);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var pmethodId = parseInt(obj.id);
        var data = 'pmethodId=' + pmethodId + '&status=' + inActive;
        fcom.ajax(fcom.makeUrl('PaymentMethods', 'changeStatus'), data, function (res) {
            $(obj).removeClass("active");
            $(obj).addClass("inactive");
            $(".status_" + pmethodId).attr('onclick', 'activeStatus(this)');
            setTimeout(function () {
                reloadList();
            }, 1000);
        });
    };
})();