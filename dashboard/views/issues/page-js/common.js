/* global fcom, langLbl */
$(function () {
    viewIssue = function (issueId) {
        fcom.ajax(fcom.makeUrl('Issues', 'view'), {issueId: issueId}, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg' });
        });
    };
    issueForm = function (recordId, recordType) {
        fcom.ajax(fcom.makeUrl('Issues', 'form'), {recordId: recordId, recordType: recordType, }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg' });
        });
    };
    issueSetup = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        fcom.ajax(fcom.makeUrl('Issues', 'setup'), fcom.frmData(frm), function (response) {
            $.w3mentorsmodal.close();
            reloadPage(3000);
        });
    };
    resolveForm = function (issueId) {
        fcom.ajax(fcom.makeUrl('Issues', 'resolve'), {issueId: issueId}, function (response) {
            $.w3mentorsmodal(response,  { 'size': 'modal-lg', 'addClass' : 'issueDetailPopup' });
        });
    };
    resolveSetup = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        var action = fcom.makeUrl('Issues', 'resolveSetup');
        fcom.updateWithAjax(action, fcom.frmData(frm), function (response) {
            $.w3mentorsmodal.close();
            reloadPage(3000);
        });
    };
    escalate = function (issueId) {
        fcom.ajax(fcom.makeUrl('Issues', 'escalate'), {issueId: issueId}, function (response) {
            $.w3mentorsmodal(response,{'size':'modal-md'});
        });
    };
    escalateSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var action = fcom.makeUrl('Issues', 'escalateSetup');
        fcom.updateWithAjax(action, fcom.frmData(frm), function (response) {
            $.w3mentorsmodal.close();
            reloadPage(3000);
        });
    };
});
