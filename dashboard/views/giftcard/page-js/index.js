/* global langLbl, fcom */
$(document).ready(function () {
    search(document.frmGiftcardSrch);
});
(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmGiftcardSrch;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Giftcard', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.frmGiftcardSrch.reset();
        search(document.frmGiftcardSrch);
    };
    form = function () {
        fcom.ajax(fcom.makeUrl('Giftcard', 'form'), '', function (response) {
            $.w3mentorsmodal(response,{ 'size': 'modal-lg' });
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        fcom.process();
        frm.submit.disabled = true;
        fcom.updateWithAjax(fcom.makeUrl('Giftcard', 'setup'), fcom.frmData(frm), function (response) {
            if (response.redirectUrl) {
                setTimeout(function () {
                    window.location.href = response.redirectUrl
                }, 1000);
            }
            if (response.status != 1) {
                frm.submit.disabled = false;
            }
        }, {failed: true});
    };

    checkWalletBalance = function (amount, balance) {
        if (amount > balance) {
            $('.wallet-pay-js').hide();
            $('.add-and-pay-js').show();
            $('input[name="add_and_pay"]').prop('checked', true);
            $('input[name="order_pmethod_id"]:first').prop('checked', false);
        } else {
            $('.wallet-pay-js').show();
            $('.add-and-pay-js').hide();
            $('input[name="add_and_pay"]').prop('checked', false);
            $('input[name="order_pmethod_id"]:first').prop('checked', true);
        }
    };

})();