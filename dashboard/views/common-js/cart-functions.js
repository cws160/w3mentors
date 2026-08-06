/* global fcom, moment, props, langLbl, parseFloat, minValue, LESSON_TYPE_SUBCRIP, LESSON_TYPE_REGULAR, maxValue, FTRAIL_TYPE */
var cart = {
    prop: {
        add_and_pay: 0,
        checkout_form_data: "",
    },
    addCourse: function (courseId) {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "addCourse", '', confFrontEndUrl), { course_id: courseId, is_free: 0 }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: 'fade' });
            callAnalyticsEvent('book_course', {});
        });
    },
    addFreeCourse: function (courseId) {
        fcom.process();
        fcom.ajax(fcom.makeUrl("Cart", "addCourse", '', confFrontEndUrl), { course_id: courseId, is_free: 1 }, function (response) {
            try {
                resp = JSON.parse(response);
                if (resp.status && resp.status == 2) {
                    cart.addCourse(courseId);
                    return;
                }
            } catch (e) {
                cart.confirmOrder(document.frmCheckout);
                callAnalyticsEvent('book_trial_course', {});
            }
        }, {failed : true});
    },
    selectWallet: function (checked) {
        document.checkoutForm.add_and_pay.value = checked ? 1 : 0;
        if (!$(document.checkoutForm).validate()) {
            return;
        }
        fcom.process();
        cart.resetModal();
        var orderType = document.checkoutForm.order_type.value;
        fcom.ajax(fcom.makeUrl("Cart", "paymentSummary", [orderType], confFrontEndUrl), fcom.frmData(document.checkoutForm), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    confirmOrder: function (form) {
        fcom.process();
        form.submit.disabled = true;
        fcom.updateWithAjax(fcom.makeUrl("Cart", "confirmOrder", '', confFrontEndUrl), fcom.frmData(form), function (response) {
            callAnalyticsEvent('confirm_order', {});
            setTimeout(function () {
                form.submit.disabled = false;
            }, 1000);
            if (response.redirectUrl) {
                window.location.href = response.redirectUrl;
            }
            if (response.status != 1) {
                form.submit.disabled = false;
            }
        }, { failed: true });
    },
    applyCoupon: function (code) {
        if (code) {
            document.checkoutForm.coupon_code.value = code;
        }
        if (!$(document.checkoutForm).validate()) {
            return;
        }
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "applyCoupon", '', confFrontEndUrl), fcom.frmData(document.checkoutForm), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    removeCoupon: function () {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "removeCoupon", '', confFrontEndUrl), fcom.frmData(document.checkoutForm), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    applyRewards: function (cb) {
        var status = cb.checked ? 1 : 0;
        document.checkoutForm.apply_reward.value = status;
        if (!$(document.checkoutForm).validate()) {
            return;
        }
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "applyRewards", [], confFrontEndUrl), fcom.frmData(document.checkoutForm), function (response) {
            if (isJson(response)) {
                var res = JSON.parse(response);
                if (res.status == 0) {
                    $('input[name="apply_reward"]').attr('checked', false);
                    return;
                }
            } else {
                $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
            }
        }, { 'failed': true });
    },
    reviewOrder: function (form) {
        cart.prop.order_type = form.order_type.value;
        cart.prop.checkout_form_data = form;
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "reviewOrder", [], confFrontEndUrl), fcom.frmData(form), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    paymentSummary: function () {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "paymentSummary", [cart.prop.order_type, true], confFrontEndUrl), fcom.frmData(cart.prop.checkout_form_data), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    resetModal: function ()
    {
        $("#w3mentorsModal").removeClass('fade');
        setTimeout(function () {
            $("#w3mentorsModal").addClass('fade');
            options.fade = 'fade';
        }, 700);
    }
};
$(document).on("hidden.bs.modal", "#w3mentorsModal", function () {
    cart.prop.add_and_pay = 0;
    cart.prop.checkout_form_data= "";
});