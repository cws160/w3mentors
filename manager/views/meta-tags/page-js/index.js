/* global fcom, langLbl */
$(document).ready(function () {
    listMetaTags('-1');
});
$(document).delegate('.language-js', 'change', function () {
    var langId = $(this).val();
    var metaId = $("input[name='meta_id']").val();
    images(metaId, langId);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchMetaTag(frm);
    }
    reloadList = function () {
        searchMetaTag(document.srchFormPaging);
    };
    listMetaTags = function (metaType) {
        metaType = metaType || '';
        fcom.ajax(fcom.makeUrl('MetaTags', 'listMetaTags'), {metaType: metaType}, function (res) {
            $('#frmBlock').html(res);
            searchMetaTag(document.srchForm);
        });
    };
    editMetaTagFormNew = function (id, metaType, recordId) {
        editMetaTagForm(id, metaType, recordId);
    };
    deleteImage = function (metaId, langId) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('MetaTags', 'removeImage'), {metaId: metaId, langId: langId}, function (t) {
            images(metaId, langId);
        });
    };
    searchMetaTag = function (form) {
        fcom.ajax(fcom.makeUrl('MetaTags', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    addMetaTagForm = function (id, metaType, recordId) {
        metaTagForm(id, metaType, recordId);
    };
    metaTagForm = function (id, metaType, recordId) {
        fcom.ajax(fcom.makeUrl('MetaTags', 'form'), {metaId: id, metaType: metaType, recordId: recordId}, function (t) {
            fcom.updatePopupContent(t);
        });
    };
    editMetaTagFormNew = function (id, metaType, recordId) {
        editMetaTagForm(id, metaType, recordId);
    };
    editMetaTagForm = function (id, metaType, recordId) {
        fcom.ajax(fcom.makeUrl('MetaTags', 'form'), {metaId: id, metaType: metaType, recordId: recordId}, function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupMetaTag = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('MetaTags', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                editMetaTagLangForm(t.metaId, langId, t.metaType, false);
                return;
            }
            $.w3mentorsmodal.close();
        });
    }
    editMetaTagLangForm = function (metaId, langId, metaType, process = true) {
        fcom.ajax(fcom.makeUrl('MetaTags', 'langForm', [metaId, langId, metaType]), '', function (t) {
            fcom.updatePopupContent(t);
            images(metaId, langId);
        }, { process: process });
    };
    setupLangMetaTag = function (frm, metaType) {
        if (!$(frm).validate()){
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('MetaTags', 'langSetup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                editMetaTagLangForm(t.metaId, langId, metaType, false);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'metaId=' + id;
        fcom.updateWithAjax(fcom.makeUrl('MetaTags', 'deleteRecord'), data, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchMetaTag(document.srchForm);
    };
    images = function (metaId, langId) {
        fcom.ajax(fcom.makeUrl('MetaTags', 'images', [metaId, langId]), SITE_ROOT_URL, function (t) {
            $('#image-listing').html(t);
        });
    };
})();
$(document).on('click', '.meta-tag', function () {
    var node = this;
    $('#form-upload').remove();
    var metaId = document.frmMetaTagLang.meta_id.value;
    var langId = document.frmMetaTagLang.lang_id.value;
    var frm = '<form enctype="multipart/form-data" id="form-upload" style="position:absolute; top:-100px;" >';
    frm = frm.concat('<input type="file" name="file" />');
    frm = frm.concat('<input type="hidden" name="meta_id" value="' + metaId + '"/>');
    frm = frm.concat('<input type="hidden" name="lang_id" value="' + langId + '"/>');
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
            fcom.ajaxMultipart(fcom.makeUrl('MetaTags', 'setUpOgImage', [metaId]), data, function (ans) {
                $(node).val($val);
                $('#form-upload').remove();
                images(ans.metaId, langId);
            }, { fOutMode: 'json' });
        }
    }, 500);
});