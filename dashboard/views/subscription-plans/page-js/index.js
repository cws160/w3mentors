/* global weekDayNames, monthNames, langLbl, layoutDirection, fcom */
$(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.frmSubsSearch.reset();
        search(document.frmSubsSearch);
    };
    cancelPlan = function (id) {
        if (!confirm(langLbl.cancelSubscription)) {
            return false;
        }
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'cancelSetup'), { id: id }, function () {
            search(document.frmSubsSearch);
        });
    };
    upgrade = function (id) {
        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'upgrade'), { id: id, }, function () {
            search(document.frmSubsSearch);
            window.location.href = fcom.makeUrl('SubscriptionPlans', 'index', [], frontendUrl);
        });
    };
    renew = function (id) {
            fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'renewPlan'), { id: id }, function () {
                $.w3mentorsmodal.close();
                search(document.frmSubsSearch);
            });
    };
    autoRenew = function (checked) {
        let autoRenew = checked ? 1 : 0;
        fcom.ajax(fcom.makeUrl("SubscriptionPlans", "autoRenew"), { autoRenew: autoRenew }, function (response) {
        });
    }
    renewForm = function (ordsubId) {
        $.confirm({
            title: langLbl.Confirm,
            content: langLbl.planRenew,
            buttons: {
                Renew: {
                    text: langLbl.renew,
                    btnClass: 'btn btn--primary',
                    keys: ['enter', 'shift'],
                    action: function () {
                        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'renewPlan'), { id: ordsubId }, function () {
                            $.w3mentorsmodal.close();
                            search(document.frmSubsSearch);
                        });
                    }
                },
                upgrade: {
                    text: langLbl.upgrade,
                    btnClass: 'btn btn--primary',
                    keys: ['enter', 'shift'],
                    action: function () {
                        fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'upgrade'), { id: ordsubId }, function () {
                            search(document.frmSubsSearch);
                            window.location.href = fcom.makeUrl('SubscriptionPlans', 'index', [], frontendUrl);
                        });
                    }
                },
                Quit: {
                    text: langLbl.Quit,
                    btnClass: 'btn btn--secondary',
                    keys: ['enter', 'shift'],
                    action: function () {
                    }
                }
            }
        });
        // fcom.ajax(fcom.makeUrl('SubscriptionPlans', 'renewForm'), { ordsubId: ordsubId }, function (response) {
        //     $.w3mentorsmodal(response, { 'size': 'modal-lg' });
        // });
    };
    search(document.frmSubsSearch);
});
