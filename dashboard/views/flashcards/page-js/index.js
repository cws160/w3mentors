
/* global fcom, langLbl */
$(function () {
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Flashcards', 'search'), fcom.frmData(frm), function (res) {
            $('#listing').html(res);
        });
    };
    goToSearchPage = function (page) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(page);
        search(frm);
    };
    clearSearch = function () {
        document.searchFlashcardFrm.reset();
        search(document.searchFlashcardFrm);
    };
    form = function (id) {
        fcom.ajax(fcom.makeUrl('Flashcards', 'form'), { flashcardId: id }, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        fcom.updateWithAjax(fcom.makeUrl('Flashcards', 'setup'), fcom.frmData(frm), function (res) {
            search(document.searchFlashcardFrm);
            $.w3mentorsmodal.close();
        });
    };
    remove = function (id) {
        if (!confirm(langLbl.confirmRemove)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Flashcards', 'remove'), { cardId: id }, function (res) {
            search(document.searchFlashcardFrm);
        });
    };
    cancel = function () {
        $.w3mentorsmodal.close();
    };
    search(document.searchFlashcardFrm);
});
