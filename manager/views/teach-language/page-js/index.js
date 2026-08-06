/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm, parentId);
});
(function () {
    var active = 1; 
    var inActive = 0;
    var dv = '#listing';
    search = function (form, parentId = '') {
        fcom.ajax(fcom.makeUrl('TeachLanguage', 'search', [parentId]), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    form = function (id, parentId = 0, frmData = '') {
        fcom.ajax(fcom.makeUrl('TeachLanguage', 'form', [id, parentId]), frmData, function (response) {
            $.w3mentorsmodal(response);
            updateFeatured(document.frmLessonPackage.tlang_parent.value);
        });
    };
    langForm = function (tLangId, langId) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('TeachLanguage', 'langForm', [tLangId, langId]), '', function (response) {
            $.w3mentorsmodal(response);
            fcom.setEditorLayout(langId);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('TeachLanguage', 'setup'), fcom.frmData(frm), function (res) {
            search(document.srchForm, parentId);
            let element = $('.tab-inline a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.tLangId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langSetup = function (frm) {
        if(!$(document.frmTeachLang).validate()){
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('TeachLanguage', 'langSetup'), fcom.frmData(document.frmTeachLang), function (res) {
            search(document.srchForm, parentId);
            let element = $('.tab-inline a.active').parent().next('li');
            if (element.length > 0) {
                if (!element.find('a').hasClass('media-js')) {
                    let langId = element.find('a').attr('data-id');
                    langForm(res.tLangId, langId);
                } else {
                    mediaForm(res.tLangId);
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
        var data = 'tLangId=' + id;
        fcom.updateWithAjax(fcom.makeUrl('TeachLanguage', 'deleteRecord'), data, function (res) {
            search(document.srchForm, parentId);
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var tLangId = parseInt(obj.id);
        var data = 'tLangId=' + tLangId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('TeachLanguage', 'changeStatus'), data, function (res) {
            $(obj).removeClass("inactive");
            $(obj).addClass("active");
            $(".status_" + tLangId).attr('onclick', 'inactiveStatus(this)');
            search(document.srchForm, parentId);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var tLangId = parseInt(obj.id);
        var data = 'tLangId=' + tLangId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('TeachLanguage', 'changeStatus'), data, function (res) {
            $(obj).removeClass("active");
            $(obj).addClass("inactive");
            $(".status_" + tLangId).attr('onclick', 'activeStatus(this)');
            search(document.srchForm, parentId);
        });
    };

    mediaForm = function (tLangId) {
        fcom.ajax(fcom.makeUrl('TeachLanguage', 'mediaForm', [tLangId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    removeFile = function (tLangId, fileType) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('TeachLanguage', 'removeFile', [tLangId, fileType]), '', function (t) {
            mediaForm(tLangId);
        });
    };
    uploadImage = function (input, tlanguageId, type) {
        if (input.files[0]) {
            uploadFile(input.files[0], tlanguageId, type);
        }
    };
    uploadFile = function (file, tlanguageId, type) {
        let formData = new FormData();
        formData.append('file', file);
        formData.append('imageType', type);
        fcom.ajaxMultipart(fcom.makeUrl('TeachLanguage', 'uploadFile', [tlanguageId]), formData, function (res) {
            search(document.frmSpokenLanguageSearch, parentId);
            mediaForm(tlanguageId);
        }, {fOutMode: 'json'});
    }
    updateFeatured = function(value) {
        $('.fldFeaturedJs').find('.caption-wraper').hide();
        if (value > 0) {
            $('.fldFeaturedJs').parent().hide();
            $('.fldFeaturedJs').find('input[name="tlang_featured"]').attr('checked', false);
        } else {
            $('.fldFeaturedJs').parent().show();
        }
    };
})();
$(document).on('click', '.tlanguageFile-Js', function () {
    $('.tlang_image_file').trigger('click');
});
$(document).on('click', '.tlanguageFlagFile-Js', function () {
    $('.tlang_flag_file').trigger('click');
});
