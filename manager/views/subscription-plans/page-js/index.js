/* global fcom, langLbl, e */
$(document).ready(function () {
    searchSubscriptionPlans(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchSubscriptionPlans(frm);
    };
    reloadList = function () {
        searchSubscriptionPlans(document.srchFormPaging);
    };
    searchSubscriptionPlans = function (form) {
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'search'), fcom.frmData(form), function (res) {
            $('#listing').html(res);
        });
    };
    form = function (id) {
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'form', [id]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('SubscriptionPlans', 'setup'), data, function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.subPlanId, langId, false);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (subPlanId, langId) {
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'langForm'), { subplang_subplan_id: subPlanId, subplang_lang_id: langId }, function (t) {
            $.w3mentorsmodal(t);
        });
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('SubscriptionPlans', 'langSetup'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(frm.subplang_subplan_id.value, langId, false);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var SubscriptionPlansId = parseInt(obj.id);
        var data = 'subPlanId=' + SubscriptionPlansId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'changeStatus'), data, function (res) {
            searchSubscriptionPlans(document.srchFormPaging);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var SubscriptionPlansId = parseInt(obj.id);
        var data = 'subPlanId=' + SubscriptionPlansId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'changeStatus'), data, function (res) {
            searchSubscriptionPlans(document.srchFormPaging);
        });
    };
    view = function (subPlanId) {
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'view',[subPlanId]), {}, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchSubscriptionPlans();
    };
})();