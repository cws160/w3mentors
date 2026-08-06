/* global fcom */

$(document).ready(function () {
    searchGiftcards(document.srchForm);
});
(function () {
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        searchGiftcards(frm);
    }
    searchGiftcards = function (form) {
        var dv = $('#ordersListing');
        fcom.ajax(fcom.makeUrl('Giftcards', 'search'), fcom.frmData(form), function (res) {
            dv.html(res);
        });
    };
    viewGiftCard = function (ordgiftId) {
        fcom.ajax(fcom.makeUrl('Giftcards', 'view'), {ordgiftId: ordgiftId}, function (response) {
            $.w3mentorsmodal(response);
        });
    };
    reloadOrderList = function () {
        searchGiftcards(document.srchFormPaging);
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchGiftcards(document.srchForm);
    };
})();
