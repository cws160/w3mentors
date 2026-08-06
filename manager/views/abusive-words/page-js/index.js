/* global fcom, SITE_ROOT_URL */
$(document).ready(function () {
    search(document.getSrchForm);
});
(function () {
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('AbusiveWords', 'search'), fcom.frmData(frm), function (res) {
            $('#listing').html(res);
        });
    };
    clearSearch = function () {
        document.getSrchForm.reset();
        document.getSrchForm.abusive_keyword.value = '';
        search(document.getSrchForm);
    };
    abusiveForm = function(abusive_id){
        fcom.ajax(fcom.makeUrl('AbusiveWords', 'form'), {abusive_id}, function (res) {
            fcom.updatePopupContent(res);
        });
    };
    setup = function(frm){
        if(!$(frm).validate()) return false;
        fcom.updateWithAjax(fcom.makeUrl('AbusiveWords', 'setup'), fcom.frmData(frm), function (res) {
            search(document.srchFormPaging);
            $.w3mentorsmodal.close();
        })
    };
    remove = function(abusive_id){
        if(!confirm(langLbl.confirmDelete)) return false;
        fcom.updateWithAjax(fcom.makeUrl('AbusiveWords', 'remove'), {abusive_id}, function (res) {
            search(document.srchFormPaging);
        })
    };
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(page);
        search(frm);
    };
})();