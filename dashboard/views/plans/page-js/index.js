/* global langLbl, fcom */
$(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmPlanSearchPaging;
        $(frm.pageno).val(pageno);
        searchPlans(frm);
    };
    searchPlans = function (frm) {
        fcom.ajax(fcom.makeUrl('Plans', 'search'), fcom.frmData(frm), function (res) {
            $(".plan-listing#listing").html(res);
        });
    };
    clearPlanSearch = function () {
        document.planSearchFrm.reset();
        searchPlans(document.planSearchFrm);
    };
    form = function (planId) {
        fcom.ajax(fcom.makeUrl('Plans', 'form'), {planId: planId}, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = new FormData(frm);
        fcom.ajaxMultipart(fcom.makeUrl('Plans', 'setup'), data, function (res) {
            searchPlans(document.frmPlanSearchPaging);
            $.w3mentorsmodal.close();
        }, {fOutMode: 'json'});
    };
    remove = function (planId) {
        var agree = confirm(langLbl.confirmRemove);
        if (!agree) {
            return false;
        }
        fcom.ajax(fcom.makeUrl('Plans', 'remove'), {planId: planId}, function (response) {
            searchPlans(document.frmPlanSearchPaging);
        });
    };
    removeFile = function (celement, id, planId) {
        $.confirm({
            title: langLbl.Confirm,
            content: langLbl.confirmRemove,
            buttons: {
                Proceed: {
                    text: langLbl.Proceed,
                    btnClass: 'btn btn--primary',
                    keys: ['enter', 'shift'],
                    action: function () {
                        fcom.ajax(fcom.makeUrl('Plans', 'removeFile'), {plan_id: planId, file_id: id}, function (t) {
                            $(celement).parent().remove();
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
    };
    searchPlans(document.planSearchFrm);
});
