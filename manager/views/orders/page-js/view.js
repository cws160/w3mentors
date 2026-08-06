/* global fcom */
(function () {
    updatePayment = function (form) {
        if (!$(form).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl("Orders", "updatePayment"), fcom.frmData(form), function (t) {
            location.reload();
        });
    };
    updateStatus = function (payId, status) {
        if (!confirm(langLbl.confirmProceed)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl("Orders", "updateStatus"), {payId: payId, status: status}, function (t) {
            location.reload();
        });
    };
    showText = function(event, show) {
        if(show) {
            $(event).siblings('.show-more-div').removeClass('hide');
            $(event).siblings('.show-less-div').addClass('hide');
            $(event).addClass('hide');
            $(event).siblings('.show-less').removeClass('hide');
        } else {
             $(event).siblings('.show-less-div').removeClass('hide');
            $(event).siblings('.show-more-div').addClass('hide');
            $(event).addClass('hide');
            $(event).siblings('.show-more').removeClass('hide');
        }
    }
})();
