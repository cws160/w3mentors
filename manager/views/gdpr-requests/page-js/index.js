/* global fcom */
$(document).ready(function () {
    searchGdprRequests(document.srchForm);
});
(function () {
    searchGdprRequests = function (frm) {
        fcom.ajax(fcom.makeUrl('GdprRequests', 'search'), fcom.frmData(frm), function (t) {
            $('#listItems').html(t);
        });
    };
    reloadList = function () {
        searchGdprRequests(document.srchFormPaging);
    };
    view = function (requestId) {
        fcom.ajax(fcom.makeUrl('GdprRequests', 'view'), {id: requestId}, function (t) {
            $.w3mentorsmodal(t);
            showHideCommentBox();
        });
    };
    updateStatus = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('GdprRequests', 'updateStatus'), fcom.frmData(frm), function (t) {
            $.w3mentorsmodal.close();
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchGdprRequests(document.srchForm);
    };
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchGdprRequests(frm);
    };
    showHideCommentBox = function (val) {
        if (val == STATUS_DECLINED) {
            $('#remarkField').show();
        } else {
            $('textarea[name="gdpreq_comment"]').val('');
            $('#remarkField').hide();
        }
    };
})();