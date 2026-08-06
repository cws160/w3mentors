/* global fcom, langLbl */
$(function () {
    search = function () {
        fcom.ajax(fcom.makeUrl('ForumTagRequests', 'search'), {}, function (response) {
            $('#listing').html(response);
        });
    };

    edit = function (id)
    {
        fcom.ajax(fcom.makeUrl('ForumTagRequests', 'form', [id]), {}, function (response) {
            $.w3mentorsmodal(response);
        });
    };

    setupApprovalRequest = function (frm)
    {
        fcom.updateWithAjax(fcom.makeUrl('ForumTagRequests', 'setup'), fcom.frmData(frm), function (response) {
            if ('1' == response.status) {
                $.w3mentorsmodal.close();
                search();
            }
        });
    };
});

$(document).ready(function () {
    search();
});
