
/* global fcom, langLbl, FLASHCARD_VIEW, FLASHCARD_TYPE, FLASHCARD_TYPE_ID, FLASHCARD_TLANG_ID */
$(function () {
    searchFlashcards = function (frm) {
        var data = {
            view: FLASHCARD_VIEW,
            keyword: frm.keyword.value,
            flashcard_type: FLASHCARD_TYPE,
            flashcard_type_id: FLASHCARD_TYPE_ID
        };
        fcom.ajax(fcom.makeUrl('Flashcards', 'search'), data, function (res) {
            $('#flashcard').html(res);
        });
    };

    clearSearch = function () {
        document.searchFlashcardFrm.reset();
        searchFlashcards(document.searchFlashcardFrm);
    };

    flashcardForm = function (id) {
        var frmData = {flashcardId: id, view: FLASHCARD_VIEW};
        fcom.ajax(fcom.makeUrl('Flashcards', 'form'), frmData, function (res) {
            $('#flashcard').html(res);
        });
    };
    
    flashcardSetup = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        var data = {
            flashcard_type: FLASHCARD_TYPE,
            flashcard_type_id: FLASHCARD_TYPE_ID,
            flashcard_tlang_id: FLASHCARD_TLANG_ID,
            flashcard_id: frm.flashcard_id.value,
            flashcard_title: frm.flashcard_title.value,
            flashcard_detail: frm.flashcard_detail.value
        };
        fcom.updateWithAjax(fcom.makeUrl('Flashcards', 'setup'), data, function (res) {
            searchFlashcards(document.searchFlashcardFrm);
            $.w3mentorsmodal.close();
        });
    };
    flashcardCancel = function () {
        searchFlashcards(document.searchFlashcardFrm);
    }
    flashcardRemove = function (id) {
        if (!confirm(langLbl.confirmRemove)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Flashcards', 'remove'), {cardId: id}, function (res) {
            searchFlashcards(document.searchFlashcardFrm);
        });
    };
    searchFlashcards(document.searchFlashcardFrm);
});
