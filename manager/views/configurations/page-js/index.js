/* global fcom, langLbl */
$(document).ready(function () {
    getForm(activeTab);
    $(document).on("click", "#testMail-js", function () {
        fcom.ajax(fcom.makeUrl('Configurations', 'testEmail'), '', function (t) { });
    });
    $('.info__icon').click(function () {
        $(this).toggleClass('is--active');
    });
    $(document).click(function (el) {
        if (!$(el.target).parents().hasClass("info__icon"))
            $(".info__icon").removeClass("is--active");
    });
    $('body').on('click', '.registration-js', function () {
        let registrationApproval = $("body #registrationApproval").is(':checked');
        let registrationVerification = $("body #registrationVerification").is(':checked');
        if (registrationApproval || registrationVerification) {
            $("body #autoRegistration").prop('checked', false);
            $("body #autoRegistration").prop('disabled', true);
            $("body #autoRegistration").parent('span').addClass('disabled');
            return;
        } else if (!registrationApproval && !registrationVerification) {
            $("body #autoRegistration").prop('disabled', false);
            $("body #autoRegistration").parent('span').removeClass('disabled');
            return;
        }
    });
});
(function () {
    var dv = '#frmBlock';
    getForm = function (frmType) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('Configurations', 'form', [frmType]), '', function (t) {
            $(dv).html(t);
        });
    };
    getLangForm = function (frmType, langId) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('Configurations', 'langForm', [frmType, langId]), '', function (t) {
            $(dv).html(t);
            fcom.setEditorLayout(langId);
            if (frmType == 11) {
                $('input[name=btn_submit]').hide();
            }
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        if (frm.elements["form_type"].value == pwaFormType) {
            var data = new FormData(frm);
            data.append('fIsAjax', 1);
            fcom.ajaxMultipart(fcom.makeUrl('Configurations', 'setup'), data, function (res) {
                res = JSON.parse(res);
                if (res.frmType > 0) {
                    getForm(res.frmType);
                    return;
                }
                $.w3mentorsmodal.close();
            });
            return;
        }

        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'setup'), fcom.frmData(frm), function (res) {
            if (res.langId > 0 && res.frmType > 0) {
                getLangForm(res.frmType, res.langId);
                return;
            }
            if (res.frmType > 0) {
                getForm(res.frmType);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    googleAuthorize = function () {
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'googleAuthorize'), '', function (response) {
            if (response.redirectUrl) {
                window.location = response.redirectUrl;
            }
        });
    };
    setupLang = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'setupLang'), fcom.frmData(frm), function (res) {
            if (res.langId > 0 && res.frmType > 0) {
                getLangForm(res.frmType, res.langId);
                return;
            }
            if (res.frmType > 0) {
                getForm(res.frmType);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    removeMedia = function (type, langId, elementObj) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        var data = "type=" + type + "&langId=" + langId;
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'removeMedia'), data, function (res) {
            getLangForm(document.frmConfiguration.form_type.value, langId);
        });
    };
    checkCourses = function (_obj) {
        var ch = $(_obj).is(':checked');
        if (ch == false) {
            $('#confEnableCoursesJs').prop('checked', true);
            fcom.ajax(fcom.makeUrl('Configurations', 'checkCourses'), {}, function (res) {
                $.w3mentorsmodal(res);
            });
        }
    };
    disableCourses = function () {
        $('#confEnableCoursesJs').prop('checked', false);
        setup(document.frmConfiguration);
        $.w3mentorsmodal.close();
    };
    contactTeam = function () {
        fcom.ajax(fcom.makeUrl('Configurations', 'contactTeam'), {}, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    setupContactReq = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'setupContactRequest'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
        });
    };
    getResolutions = function (encoding) {
        fcom.updateWithAjax(fcom.makeUrl('Configurations', 'getResolutions'), {encoding}, function (res) {
            $('select[name="CONF_MUX_RESOLUTION"]').html('');
            $.each(JSON.parse(res.resolutions), function(i, item) {
                var o = new Option("text", item);
                $('select[name="CONF_MUX_RESOLUTION"]').append($(o).html(item));
            });
        });
    }
})();
form = function (form_type) {
    if (typeof form_type == undefined || form_type == null) {
        form_type = 1;
    }
    jQuery.ajax({
        type: "POST",
        data: { form: form_type, fIsAjax: 1 },
        url: fcom.makeUrl("configurations", "form"),
        success: function (json) {
            json = $.parseJSON(json);
            if ("1" == json.status) {
                $("#tabs_0" + form_type).html(json.msg);
            } else {
                jsonErrorMessage(json.msg)
            }
        }
    });
}
submitForm = function (form, v) {
    $(form).ajaxSubmit({
        delegation: true,
        beforeSubmit: function () {
            v.validate();
            if (!v.isValid()) {
                return false;
            }
        },
        success: function (json) {
            json = $.parseJSON(json);
            if (json.status == "1") {
                jsonSuccessMessage(json.msg)
            } else {
                jsonErrorMessage(json.msg);
            }
        }
    });
    return false;
}
$(document).on('click', '.logoFiles-Js', function () {
    var node = this;
    console.log(node);
    $('#form-upload').remove();
    var fileType = $(node).attr('data-file_type');
    var lang_id = document.frmConfiguration.lang_id.value;
    var form_type = document.frmConfiguration.form_type.value;
    var frm = '<form enctype="multipart/form-data" id="form-upload" style="position:absolute; top:-100px;" >';
    frm = frm.concat('<input type="file" name="file" />');
    frm = frm.concat('<input type="hidden" name="file_type" value="' + fileType + '">');
    frm = frm.concat('<input type="hidden" name="lang_id" value="' + lang_id + '">');
    frm = frm.concat('</form>');
    $('body').prepend(frm);
    $('#form-upload input[name=\'file\']').trigger('click');
    if (typeof timer != 'undefined') {
        clearInterval(timer);
    }
    timer = setInterval(function () {
        if ($('#form-upload input[name=\'file\']').val() != '') {
            clearInterval(timer);
            $val = $(node).val();
            var data = new FormData($('#form-upload')[0]);
            fcom.ajaxMultipart(fcom.makeUrl('Configurations', 'uploadMedia'), data, function (res) {
                $(node).val($val);
                getLangForm(form_type, lang_id);
            }, { fOutMode: 'json' });
        }
    }, 500);
});

checkAffiliates = function (_obj) {
    var ch = $(_obj).is(':checked');
    if (ch == false) {
        $('#confEnableAffiliatesJs').prop('checked', true);
        fcom.ajax(fcom.makeUrl('Configurations', 'checkAffiliates'), {}, function (res) {
            $.w3mentorsmodal(res);
        });
    }
};
disableAffiliates = function () {
    $('#confEnableAffiliatesJs').prop('checked', false);
    setup(document.frmConfiguration);
    $.w3mentorsmodal.close();
};