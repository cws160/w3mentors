/* global fcom, langLbl */
$(document).ready(function () {
    searchCurrency(document.frmCurrencySearch);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    reloadList = function () {
        searchCurrency(document.frmCurrencySearch);
    };
    searchCurrency = function (form) {
        fcom.ajax(fcom.makeUrl('CurrencyManagement', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    editCurrencyForm = function (currencyId) {
        currencyForm(currencyId);
    };
    currencyForm = function (currencyId) {
        fcom.ajax(fcom.makeUrl('CurrencyManagement', 'form', [currencyId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupCurrency = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('CurrencyManagement', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                editCurrencyLangForm(t.currencyId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    syncRates = function () {
        fcom.updateWithAjax(fcom.makeUrl('CurrencyManagement', 'syncRates'), '', function (t) {
            $("#last-sync").text(t.lastsync);
            $.w3mentorsmodal.close();
        });
    };
    editCurrencyLangForm = function (currencyId, langId) {
        fcom.ajax(fcom.makeUrl('CurrencyManagement', 'langForm', [currencyId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    getConfigurationForm = function () {
        fcom.ajax(fcom.makeUrl('CurrencyManagement', 'configurationForm'), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupConfig = function (form) {
        if (!$(form).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('CurrencyManagement', 'setupConfig'), fcom.frmData(form), function (t) {
            $.w3mentorsmodal.close();
            if(parseInt(form.status.value) == 1){
                $("#last-sync").text(t.lastsync);
                $(".sync-rates-js").removeClass('hide');
            }else{
                $(".sync-rates-js").addClass('hide');
            }
        });
    };
    setupLangCurrency = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('CurrencyManagement', 'langSetup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                editCurrencyLangForm(t.currencyId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var currencyId = parseInt(obj.id);
        var data = 'currencyId=' + currencyId + '&status=' + active;
        fcom.ajax(fcom.makeUrl('CurrencyManagement', 'changeStatus'), data, function (res) {
            $(obj).removeClass("inactive");
            $(obj).addClass("active");
            $(".status_" + currencyId).attr('onclick', 'inactiveStatus(this)');
            setTimeout(function () {
                reloadList();
            }, 1000);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var currencyId = parseInt(obj.id);
        var data = 'currencyId=' + currencyId + '&status=' + inActive;
        fcom.ajax(fcom.makeUrl('CurrencyManagement', 'changeStatus'), data, function (res) {
            $(obj).removeClass("active");
            $(obj).addClass("inactive");
            $(".status_" + currencyId).attr('onclick', 'activeStatus(this)');
            setTimeout(function () {
                reloadList();
            }, 1000);
        });
    };
})();	