/* global fcom, langLbl */

$(document).ready(function () {
    searchNotification(document.frmNotificationSrch);
});
(function () {
    searchNotification = function (frm) {
        fcom.ajax(fcom.makeUrl('notifications', 'search'), fcom.frmData(frm), function (res) {
            $('#ordersListing').html(res);
        });
    };
    deleteRecords = function () {
        var recordIdArr = [];
        $('.check-record').each(function (i, obj) {
            if ($(this).prop('checked') == true) {
                recordIdArr.push($(this).attr('rel'));
            }
        });
        if (recordIdArr.length < 1) {
            return false;
        }
        if (!confirm(langLbl.confirmRemove)) {
            return;
        }
        var data = 'record_ids=' + recordIdArr;
        fcom.updateWithAjax(fcom.makeUrl('Notifications', 'deleteRecords'), data, function (t) {
            reloadList();
        });
    };
    changeStatus = function (status) {
        var recordIdArr = [];
        $('.check-record').each(function (i, obj) {
            if ($(this).prop('checked') === true) {
                recordIdArr.push($(this).attr('rel'));
            }
        });
        if (recordIdArr.length < 1) {
            return false;
        }
        var data = 'record_ids=' + recordIdArr + '&status=' + status;
        fcom.updateWithAjax(fcom.makeUrl('Notifications', 'changeStatus'), data, function (t) {
            reloadList();
        });
    };
    goToSearchPage = function (pageno) {
        var frm = document.frmNotificationSrchPaging;
        frm.pageno.value = pageno;
        searchNotification(frm);
    };
    reloadList = function () {
        searchNotification(document.frmNotificationSrch);
        getMessageCount();
    };
    getMessageCount = function () {
        fcom.updateWithAjax(fcom.makeUrl('Notifications', 'getUnreadCount'), '', function (response) {
            if (response.notiCount > 0) {
                let notiCount = (response.notiCount > 99) ? '99+' : response.notiCount;
                $('.notification-badge').attr('data-count', notiCount);
                return;
            }
            $('.notification-badge').removeAttr("data-count");
        });
    };
})();