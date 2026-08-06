/* global fcom, langLbl */
$(function () {
    var queId = 0;
    goToSearchPage = function (pageno) {
        var frm = document.srchQuestionForm;
        /* $(frm.pageno).val(pageno); */
        search(frm, pageno);
    };

    search = function (frm, pageno) {
        var pageno = pageno || 1;
        $(frm.pageno).val(pageno);
        fcom.ajax(fcom.makeUrl('Forum', 'search'), fcom.frmData(frm), function (response) {
            $('#listing').html(response);
        });
    };

    clearSearch = function ()
    {
        document.srchQuestionForm.reset();
        search(document.srchQuestionForm, 1);
    }

    viewComments = function (qId, page)
    {
        queId = qId;
        var page = page || 1;
        fcom.ajax(fcom.makeUrl('Forum', 'searchComments'), {que_id: queId, page: page}, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg' });
        });
    };

    goToCommentsSearchPage = function (page) {
        var frm = document.frmUserSearchPaging;
        $(frm.page).val(page);
        viewComments(queId, page);
    };

});

$(document).ready(function () {
    search(document.srchQuestionForm);
});
