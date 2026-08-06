/* global fcom, langLbl */
$(document).ready(function () {
    search();
});
(function () {
    search = function () {
        fcom.ajax(fcom.makeUrl('ForumReportIssueReasons', 'search'), '', function (res) {
            $('#listing').html(res);
        });
    };
    form = function (id) {
        fcom.ajax(fcom.makeUrl('ForumReportIssueReasons', 'form', [id]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ForumReportIssueReasons', 'setup'), fcom.frmData(frm), function (res) {
            search();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.id, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    }
    langForm = function (id, langId) {
        fcom.ajax(fcom.makeUrl('ForumReportIssueReasons', 'langForm', [id, langId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('ForumReportIssueReasons', 'langSetup'), data, function (res) {
            search();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.id, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'id=' + id;
        fcom.updateWithAjax(fcom.makeUrl('ForumReportIssueReasons', 'deleteRecord'), data, function (res) {
            search();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var id = parseInt(obj.id);
        var data = 'id=' + id + "&status=1";
        fcom.ajax(fcom.makeUrl('ForumReportIssueReasons', 'changeStatus'), data, function (res) {
            search();
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var id = parseInt(obj.id);
        var data = 'id=' + id + "&status=0";
        fcom.ajax(fcom.makeUrl('ForumReportIssueReasons', 'changeStatus'), data, function (res) {
            search();
        });
    };
})();