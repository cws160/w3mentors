/* global fcom, langLbl */
$(document).ready(function () {
    searchPages(document.frmPagesSearch);
});
(function () {
    pagesLayouts = function () {
        fcom.ajax(fcom.makeUrl('ContentPages', 'layouts'), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    goToSearchPage = function (page) {
        var frm = document.frmPagesSearchPaging;
        $(frm.page).val(page);
        searchPages(frm);
    };
    reloadList = function () {
        searchPages(document.frmPagesSearchPaging);
    };
    searchPages = function (form) {
        var dv = '#pageListing';
        fcom.ajax(fcom.makeUrl('ContentPages', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    addFormNew = function (id) {
        addForm(id);
    };
    addForm = function (id) {
        fcom.ajax(fcom.makeUrl('ContentPages', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setup = function (frm) {
        fcom.resetEditorInstance();
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ContentPages', 'setup'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.pageId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (pageId, langId) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('ContentPages', 'langForm', [pageId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
            fcom.setEditorLayout(langId);
            var frm = $('.modal form')[0];
            var validator = $(frm).validation({ errordisplay: 3 });
            $(frm).submit(function (e) {
                e.preventDefault();
                validator.validate();
                if (!validator.isValid()) {
                    return;
                }
                fcom.updateWithAjax(fcom.makeUrl('ContentPages', 'langSetup'), fcom.frmData(frm), function (t) {
                    fcom.resetEditorInstance();
                    reloadList();
                    let element = $('.tabs-nav a.active').parent().next('li');
                    if (element.length > 0) {
                        let langId = element.find('a').attr('data-id');
                        langForm(t.pageId, langId);
                        return;
                    }
                    $.w3mentorsmodal.close();
                });
            });
        });
    };
    setupLang = function (frm) {
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('ContentPages', 'langSetup'), data, function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(t.pageId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ContentPages', 'deleteRecord'), { id: id }, function (res) {
            reloadList();
        });
    };
    removeBgImage = function (cpageId, langId, cpageLayout) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ContentPages', 'removeBgImage', [cpageId, langId]), '', function (t) {
            $('.bg-image-js').remove();
            $('.image-div-js').addClass('hide');
        });
    };
    clearSearch = function () {
        document.frmPagesSearch.reset();
        searchPages(document.frmPagesSearch);
    };
})();
$(document).on('click', '.bgImageFile-Js', function () {
    var node = this;
    $('#form-upload').remove();
    var formName = $(node).attr('data-frm');
    var lang_id = document.frmBlockLang.lang_id.value;
    var cpage_id = document.frmBlockLang.cpage_id.value;
    var cpage_layout = document.frmBlockLang.cpage_layout.value;
    var fileType = $(node).attr('data-file_type');
    var frm = '<form enctype="multipart/form-data" id="form-upload" style="position:absolute; top:-100px;" >';
    frm = frm.concat('<input type="file" name="file" />');
    frm = frm.concat('<input type="hidden" name="file_type" value="' + fileType + '">');
    frm = frm.concat('<input type="hidden" name="cpage_id" value="' + cpage_id + '">');
    frm = frm.concat('<input type="hidden" name="lang_id" value="' + lang_id + '">');
    frm = frm.concat('<input type="hidden" name="cpage_layout" value="' + cpage_layout + '">');
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
            fcom.ajaxMultipart(fcom.makeUrl('ContentPages', 'setUpBgImage'), data, function (res) {
                $(node).val($val);
                $("body .uploaded--image").html('<img src="' + res.img + '" class="bg-image-js"> <a href="javascript:void(0);" onclick="removeBgImage(' + [res.cpage_id, res.lang_id, res.cpage_layout] + ')" class="remove--img"><i class="ion-close-round"></i></a>');
                $('.image-div-js').removeClass('hide');
            }, { fOutMode: 'json' });
        }
    }, 500);
});