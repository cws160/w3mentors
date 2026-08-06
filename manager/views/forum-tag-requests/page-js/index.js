/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (page)
    {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(page);
        search(frm);
    }
    search = function (form)
    {
        fcom.ajax(fcom.makeUrl('ForumTagRequests', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    clearSearch = function ()
    {
        document.srchForm.reset();
        search(document.srchForm);
    };

    getStatusChangeForm = function (id)
    {
        var id = id || 0;
        if (1 > id) {
            $.appalert(langLbl.invalidRequest, 'error');
            return;
        }

        fcom.ajax(fcom.makeUrl('ForumTagRequests', 'statusChangeForm', [id]), {}, function (response) {
            $.w3mentorsmodal(response);
        });
    }

    changeStatus = function (form)
    {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }

        if (!$(form).validate()) {
            return;
        }

        fcom.updateWithAjax(fcom.makeUrl('ForumTagRequests', 'changeStatus'), fcom.frmData(form), function (response) {
            if ('1' == response.status) {
                search(document.srchFormPaging);
            }
            $.w3mentorsmodal.close();
        });
    }
})();