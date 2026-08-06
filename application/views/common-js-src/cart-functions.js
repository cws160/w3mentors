/* global fcom, moment, props, langLbl, parseFloat, minValue, LESSON_TYPE_SUBCRIP, LESSON_TYPE_REGULAR, maxValue, FTRAIL_TYPE */
var TYPE_SUBCRIP;
var TYPE_FTRAIL;
var cart = {
    prop: {
        ordles_teacher_id: 0,
        ordles_tlang_id: 0,
        ordles_duration: 0,
        ordles_quantity: 1,
        ordles_type: 0,
        ordles_starttime: "",
        ordles_endtime: "",
        ordles_offline: 0,
        add_and_pay: 0,
        ordles_address_id: 0,
        slots: [],
        checkout_form_data: "",
        order_type: 0
    },
    updateQuantity: function (operation) {
        var oldQty = parseInt($("input[name=ordles_quantity]").val());
        if (operation == "+" && oldQty < maxValue) {
            cart.prop.ordles_quantity += 1;
        } else if (operation == "-" && oldQty > minValue) {
            cart.prop.ordles_quantity -= 1;
        }
        $("input[name=ordles_quantity]").val(cart.prop.ordles_quantity);
        if (cart.prop.ordles_quantity != oldQty) {
            cart.updatePrice();
        }
    },
    langSlots: function (teacherId) {
        var fade = (cart.prop.ordles_teacher_id == 0) ? 'fade' : '';
        cart.prop.ordles_teacher_id = teacherId;
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "langSlots"), cart.prop, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: fade });
            cart.prop.slots = [];
            callAnalyticsEvent('book_lesson', {});
        });
    },
    bookLangSlot: function (teacherId, tlangId, duration) {
        cart.prop.ordles_tlang_id = tlangId;
        cart.prop.ordles_duration = duration;
        cart.langSlots(teacherId);
    },
    viewCalendar: function () { 
        cart.prop.checkout_form_data = '';
        fcom.process();
        freeTrial = 0;
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "viewCalendar"), cart.prop, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
            cart.updatePrice();
        });
    },
    trailCalendar(teacherId) {
        fcom.process();
        cart.prop.ordles_type = FTRAIL_TYPE;
        freeTrial = FTRAIL_TYPE;
        fcom.ajax(fcom.makeUrl("Cart", "trailCalendar"), { ordles_type: cart.prop.ordles_type, teacherId: teacherId }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal' });
            callAnalyticsEvent('book_trial_lesson', {});
        });
    },
    addLesson: function () {
        fcom.process();
        cart.prop.startTime = [];
        cart.prop.endTime = [];
        for (let elements in cart.prop.slots) {
            cart.prop.startTime.push(cart.prop.slots[elements].ordles_starttime);
            cart.prop.endTime.push(cart.prop.slots[elements].ordles_endtime);
        }
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "addLesson"), cart.prop, function (response) {
            if (isJson(response)) {
                var res = JSON.parse(response);
                if (res.status == 1 && cart.prop.ordles_type == FTRAIL_TYPE) {
                    cart.confirmOrder(document.checkoutForm);
                }
            } else {
                $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
            }
        });
    },
    addSubscriptionLesson: function () {
        fcom.process();
        cart.prop.startTime = [];
        cart.prop.endTime = [];
        for (let elements in cart.prop.slots) {
            cart.prop.startTime.push(cart.prop.slots[elements].ordles_starttime);
            cart.prop.endTime.push(cart.prop.slots[elements].ordles_endtime);
        }
        fcom.ajax(fcom.makeUrl("Cart", "addLesson"), cart.prop, function (response) {
            if (isJson(response)) {
                var res = JSON.parse(response);
                if (res.status == 1) {
                    cart.confirmOrder(document.checkoutForm);
                }
            } else {
                $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal' });
            }
        });
    },
    addSubscription: function () {
        if (Object.keys(cart.prop.slots).length != cart.prop.ordles_quantity) {
            return false;
        }
        fcom.process();
        cart.prop.startTime = [];
        cart.prop.endTime = [];
        for (let elements in cart.prop.slots) {
            cart.prop.startTime.push(cart.prop.slots[elements].ordles_starttime);
            cart.prop.endTime.push(cart.prop.slots[elements].ordles_endtime);
        }
        fcom.ajax(fcom.makeUrl("Cart", "addSubscription"), cart.prop, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal' });
        });
    },
    addClass: function (classId) {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "addClass"), { grpcls_id: classId }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: 'fade' });
            callAnalyticsEvent('book_class', {});
        });
    },
    addPackage: function (packageId) {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "addPackage"), { packageId: packageId }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: 'fade' });
            callAnalyticsEvent('book_class', {});
        });
    },
    addCourse: function (courseId) {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "addCourse"), { course_id: courseId, is_free: 0 }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: 'fade' });
            callAnalyticsEvent('book_course', {});
        });
    },
    addFreeCourse: function (courseId) {
        fcom.process();
        fcom.ajax(fcom.makeUrl("Cart", "addCourse"), { course_id: courseId, is_free: 1 }, function (response) {
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
        fcom.ajax(fcom.makeUrl("Cart", "paymentSummary", [orderType]), fcom.frmData(document.checkoutForm), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    confirmOrder: function (form) {
        if (typeof freeTrial !== 'undefined') {
            form.ordles_type.value = freeTrial;
        }
        fcom.process();
        form.submit.disabled = true;
        fcom.updateWithAjax(fcom.makeUrl("Cart", "confirmOrder"), fcom.frmData(form), function (response) {
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
        fcom.ajax(fcom.makeUrl("Cart", "applyCoupon"), fcom.frmData(document.checkoutForm), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    removeCoupon: function () {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "removeCoupon"), fcom.frmData(document.checkoutForm), function (response) {
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
        fcom.ajax(fcom.makeUrl("Cart", "applyRewards"), fcom.frmData(document.checkoutForm), function (response) {
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
    toggleLanguage: function () {
        $('.select-slot-target-js').slideUp();
        $('.select-slot-trigger-js').removeClass('is-active');
        var trigger = $('.select-tlang-trigger-js');
        if (trigger.hasClass('is-active')) {
            trigger.removeClass('is-active');
            trigger.siblings('.select-tlang-target-js').slideUp();
            return false;
        }
        $('.select-tlang-trigger-js').removeClass('is-active');
        trigger.addClass("is-active");
        $('.select-tlang-target-js').slideUp();
        if ($('input[name="ordles_tlang_id"]').length > 1) {
            trigger.siblings('.select-tlang-target-js').slideDown();
        }
    },
    selectLanguage: function (ordles_tlang_id) {
        cart.prop.ordles_tlang_id = ordles_tlang_id;
        cart.selectDuration(cart.prop.ordles_duration);
    },
    toggleDuration: function () {
        $('.select-tlang-target-js').slideUp();
        $('.select-tlang-trigger-js').removeClass('is-active');
        var trigger = $('.select-slot-trigger-js');
        if (trigger.hasClass('is-active')) {
            trigger.removeClass('is-active');
            trigger.siblings('.select-slot-target-js').slideUp();
            return false;
        }
        $('.select-slot-trigger-js').removeClass('is-active');
        trigger.addClass("is-active");
        $('.select-slot-target-js').slideUp();
        if ($('input[name="ordles_duration[' + cart.prop.ordles_tlang_id + ']"]').length > 1) {
            trigger.siblings('.select-slot-target-js').slideDown();
        }
    },
    selectDuration: function (ordles_duration) {
        cart.prop.ordles_duration = ordles_duration;
        cart.updatePrice();
    },
    selectSubscription: function (type = '') {
        if ($('input[name="ordles_type"]:checked').length > 0) {
            cart.prop.ordles_type = LESSON_TYPE_SUBCRIP;
        } else {
            cart.prop.ordles_type = LESSON_TYPE_REGULAR;
        }
    },
    selectOfflineSession: function (addressId = 0) {
        if ($('input[name="ordles_offline"]:checked').length > 0) {
            cart.prop.ordles_offline = 1;
            cart.prop.ordles_address_id = addressId;
        } else {
            cart.prop.ordles_offline = 0;
            cart.prop.ordles_address_id = 0;
        }
    },
    disableEnter: function (event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            if (document.checkoutForm.coupon_code.value != '') {
                cart.applyCoupon(document.checkoutForm.coupon_code.value);
                return;
            }
        }
    },
    addSubscriptionPlan: function (planId) {
        fcom.process();
        fcom.ajax(fcom.makeUrl("Cart", "addSubscriptionPlan"), { planId: planId }, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: 'fade' });
            callAnalyticsEvent('book_subscription_plan', {});
        });
    },
    updatePrice: function () {
        fcom.ajax(fcom.makeUrl("Cart", "priceSlabs"), cart.prop, function (response) {
            if (isJson(response)) {
                var res = JSON.parse(response);
                if (res.status == 1) {
                    $('#price-js').text(res.price);
                    return;
                }
            }
            $('#price-js').text(formatMoney(0));
            fcom.error(langLbl.lessonNotAvailable);
        });
    },
    updateSlot: function (slot) {
        let val = slot.value;
        if($(slot).hasClass('is-checked')) {
            delete cart.prop.slots[val];
            $(slot).removeClass('is-checked');
            if (Object.keys(cart.prop.slots).length < cart.prop.ordles_quantity) {
                if (cart.prop.ordles_type == TYPE_SUBCRIP) {
                    $('#subcrip-checkout-btn-js').addClass('btn--disabled').removeAttr('onclick');
                }
                if(cart.prop.ordles_type == FTRAIL_TYPE) {
                    $('.book-freetrial-js').addClass('btn--disabled').removeAttr('onclick');
                }
            }
        } else {
            if (Object.keys(cart.prop.slots).length >= cart.prop.ordles_quantity) {
                $(slot).prop('checked', false);
                delete cart.prop.slots[val];
                if(cart.prop.ordles_type == FTRAIL_TYPE) {
                    fcom.error(langLbl.maxSlotsFreeTiral);
                } else {
                    fcom.error(langLbl.maxSlotsSelected);
                }
                return false;
            } else {
                let startTime = moment(val).format('YYYY-MM-DD HH:mm:ss');
                let endTime = moment(val).add(cart.prop.ordles_duration, 'minutes').format('YYYY-MM-DD HH:mm:ss');
                cart.prop.slots[val] = {
                    ordles_starttime: startTime,
                    ordles_endtime: endTime
                };
                if (cart.prop.ordles_type == TYPE_SUBCRIP && Object.keys(cart.prop.slots).length == cart.prop.ordles_quantity) {
                    $('#subcrip-checkout-btn-js').removeClass('btn--disabled').attr('onclick', ' cart.addSubscription();');
                }
                if(cart.prop.ordles_type == FTRAIL_TYPE && Object.keys(cart.prop.slots).length == cart.prop.ordles_quantity) {
                    cart.prop.ordles_starttime = startTime;
                    cart.prop.ordles_endtime = endTime;
                    $('.book-freetrial-js').removeClass('btn--disabled').attr('onclick', 'cart.addLesson();');
                }
                $(slot).addClass('is-checked');
            }
        }
        updateLessonList();
    },
    reviewOrder: function (form) {
        cart.prop.order_type = form.order_type.value;
        cart.prop.checkout_form_data = form;
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "reviewOrder"), fcom.frmData(form), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    paymentSummary: function () {
        fcom.process();
        cart.resetModal();
        fcom.ajax(fcom.makeUrl("Cart", "paymentSummary", [cart.prop.order_type, true]), fcom.frmData(cart.prop.checkout_form_data), function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg checkout--modal', fade: '' });
        });
    },
    resetCartData: function () {
        cart.prop.ordles_teacher_id = 0;
        cart.prop.ordles_tlang_id = 0;
        cart.prop.ordles_duration = 0;
        cart.prop.ordles_quantity = 1;
        cart.prop.ordles_type = 0;
        cart.prop.ordles_starttime = "";
        cart.prop.ordles_endtime = "";
        cart.prop.add_and_pay = 0;
        cart.prop.ordles_offline = 0;
        cart.prop.ordles_address_id = 0;
        cart.prop.checkout_form_data= "";
        cart.prop.order_type = 0;
        cart.prop.slots = [];
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
