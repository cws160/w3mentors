/* global fcom */
$(document).ready(function () {
    searchMeetingTool(document.frmMeetingToolSearch);
});
(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmMeetingToolSearchPaging;
        $(frm.pageno).val(pageno);
        searchMeetingTool(frm);
    }
    reloadList = function () {
        searchMeetingTool(document.frmMeetingToolSearchPaging);
    };
    searchMeetingTool = function (form) {
        fcom.ajax(fcom.makeUrl('MeetingTools', 'search'), fcom.frmData(form), function (res) {
            $('#listing').html(res);
        });
    };
    meetingToolForm = function (id) {
        fcom.ajax(fcom.makeUrl('MeetingTools', 'form'), {id: id}, function (t) {
            fcom.updatePopupContent(t);
        });
    };
    meetingToolSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('MeetingTools', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            $.w3mentorsmodal.close();
        });
    };
    changeStatus = function (id, status) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var data = {id: id, status: status};
        fcom.updateWithAjax(fcom.makeUrl('MeetingTools', 'changeStatus'), data, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.frmMeetingToolSearch.reset();
        searchMeetingTool(document.frmMeetingToolSearch);
    };
})();	