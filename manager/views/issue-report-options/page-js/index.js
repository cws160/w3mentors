/* global fcom, langLbl */
$(document).ready(function () {
    search(document.frmIssueReoprtOptions);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.frmIssueReportOptionsPaging;
        $(frm.page).val(page);
        search(frm);
    }
    search = function (form) {
        fcom.ajax(fcom.makeUrl('IssueReportOptions', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    clearSearch = function () {
        document.frmIssueReoprtOptions.reset();
        search(document.frmIssueReoprtOptions);
    };
    form = function (id) {
        fcom.ajax(fcom.makeUrl('IssueReportOptions', 'form', [id]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('IssueReportOptions', 'setup'), fcom.frmData(frm), function (res) {
            search(document.frmIssueReportOptionsPaging);
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.optId, langId);
                return;
            }
        });
    }
    langForm = function (optId, langId) {
        fcom.ajax(fcom.makeUrl('IssueReportOptions', 'langForm', [optId, langId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('IssueReportOptions', 'langSetup'), data, function (res) {
            search(document.frmIssueReportOptionsPaging);
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.optId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'optId=' + id;
        fcom.updateWithAjax(fcom.makeUrl('IssueReportOptions', 'deleteRecord'), data, function (res) {
            search(document.frmIssueReportOptionsPaging);
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var optId = parseInt(obj.id);
        var data = 'optId=' + optId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('IssueReportOptions', 'changeStatus'), data, function (res) {
            $(obj).removeClass("inactive");
            $(obj).addClass("active");
            $(".status_" + optId).attr('onclick', 'inactiveStatus(this)');
            search(document.frmIssueReportOptionsPaging);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var optId = parseInt(obj.id);
        var data = 'optId=' + optId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('IssueReportOptions', 'changeStatus'), data, function (res) {
            $(obj).removeClass("active");
            $(obj).addClass("inactive");
            $(".status_" + optId).attr('onclick', 'activeStatus(this)');
            search(document.frmIssueReportOptionsPaging);
        });
    };
})();