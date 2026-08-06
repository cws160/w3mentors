signupAffiliateSetup = function (frm) {
    if (!$(frm).validate()) {
        return;
    }
    fcom.process();
    fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'affiliateSignupSetup'), fcom.frmData(frm), function (res) {
        frm.reset();
        setTimeout(function () {
            (res.redirectUrl) ?  window.location.href = res.redirectUrl :  window.location.reload();  
        }, ALERT_CLOSE_TIME * 1000);
    });
};