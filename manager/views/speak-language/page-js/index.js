/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    search = function (form) {
        fcom.ajax(fcom.makeUrl('SpeakLanguage', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    form = function (id) {
        fcom.ajax(fcom.makeUrl('SpeakLanguage', 'form', [id]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('SpeakLanguage', 'setup'), fcom.frmData(frm), function (res) {
            search(document.srchForm);
            let element = $('.tab-inline a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.sLangId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    }
    langForm = function (sLangId, langId) {
        fcom.ajax(fcom.makeUrl('SpeakLanguage', 'langForm', [sLangId, langId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('SpeakLanguage', 'langSetup'), fcom.frmData(frm), function (res) {
            search(document.srchForm);
            let element = $('.tab-inline a.active').parent().next('li');
            if (element.length > 0) {
                if (!element.find('a').hasClass('media-js')) {
                    let langId = element.find('a').attr('data-id');
                    langForm(res.sLangId, langId);
                } else {
                    mediaForm(res.sLangId);
                }
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'sLangId=' + id;
        fcom.updateWithAjax(fcom.makeUrl('SpeakLanguage', 'deleteRecord'), data, function (res) {
            search(document.srchForm);
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var sLangId = parseInt(obj.id);
        var data = 'sLangId=' + sLangId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('SpeakLanguage', 'changeStatus'), data, function (res) {
            $(obj).removeClass("inactive");
            $(obj).addClass("active");
            $(".status_" + sLangId).attr('onclick', 'inactiveStatus(this)');
            search(document.srchForm);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var sLangId = parseInt(obj.id);
        var data = 'sLangId=' + sLangId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('SpeakLanguage', 'changeStatus'), data, function (res) {
            $(obj).removeClass("active");
            $(obj).addClass("inactive");
            $(".status_" + sLangId).attr('onclick', 'activeStatus(this)');
            search(document.srchForm);
        });
    };

    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
    mediaForm = function (sLangId) {
        fcom.ajax(fcom.makeUrl('SpeakLanguage', 'mediaForm', [sLangId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    removeFile = function (sLangId, fileType) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('SpeakLanguage', 'removeFile', [sLangId, fileType]), '', function (t) {
            mediaForm(sLangId);
        });
    };
    uploadImage = function (input, slanguageId, type) {
        if (input.files[0]) {
            uploadFile(input.files[0], slanguageId, type);
        }
    };
    uploadFile = function (file, slanguageId, type) {
        let formData = new FormData();
        formData.append('file', file);
        formData.append('imageType', type);
        $.loader.show();
        fcom.ajaxMultipart(fcom.makeUrl('SpeakLanguage', 'uploadFile', [slanguageId]), formData, function (res) {
            $.loader.hide();
            search(document.srchForm);
            mediaForm(slanguageId);
        }, { fOutMode: 'json' });
    }
})();
$(document).on('click', '.slanguageFile-Js', function () {
    $('.slanguage_image_file').trigger('click');
});
$(document).on('click', '.slanguageFlagFile-Js', function () {
    $('.slanguage_flag_file').trigger('click');
});