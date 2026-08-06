/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.frmCategoryPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (form, process = true) {
        fcom.ajax(fcom.makeUrl('Categories', 'search'), fcom.frmData(form), function (response) {
            $('#listing').html(response);
        }, {process: process});
    };
    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
    categoryForm = function (categoryId) {
        type = document.srchForm.cate_type.value;
        fcom.ajax(fcom.makeUrl('Categories', 'form', [categoryId, type]), '', function (response) {
            $.w3mentorsmodal(response);
            if (categoryId < 1 && document.srchForm.parent_id.value > 0) {
                document.frmCategory.cate_parent.value = document.srchForm.parent_id.value;
                document.frmCategory.cate_type.value = type;
                updateFeatured(document.srchForm.parent_id.value);
            } else {
                updateFeatured(document.frmCategory.cate_parent.value);
            }
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Categories', 'setup'), fcom.frmData(frm), function (res) {
            search(document.srchForm, false);
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.cateId, langId, false);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (cateId, langId, process = true) {
        fcom.ajax(fcom.makeUrl('Categories', 'langForm', [cateId, langId]), '', function (response) {
            $.w3mentorsmodal(response);
        }, {process: process});
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Categories', 'langSetup'), fcom.frmData(frm), function (res) {
            search(document.srchForm, false);
            let element = $('.tabs-nav a.active').parent().next('li');
            if(element.hasClass('mediaTab')) {
                mediaForm(res.cateId);
                return;
            } else {
                if (element.length > 0) {
                    let langId = element.find('a').attr('data-id');
                    langForm(res.cateId, langId, false);
                    return;
                }
            }
            $.w3mentorsmodal.close();
        });
    };
    remove = function (cateId) {
        if (confirm(langLbl.confirmRemove)) {
            fcom.updateWithAjax(fcom.makeUrl('Categories', 'delete', [cateId]), '', function (response) {
                search(document.srchForm);
            });
        }
    };
    updateStatus = function (cateId, status) {
        if (confirm(langLbl.confirmUpdateStatus)) {
            fcom.updateWithAjax(fcom.makeUrl('Categories', 'updateStatus', [cateId, status]), '', function (res) {
                search(document.srchForm);
            });
        }
    };

    updateOrder = function (onDrag = 1) {
        var order = $("#categoriesList").tableDnDSerialize();
        var type = document.srchForm.cate_type.value;
        fcom.updateWithAjax(fcom.makeUrl('Categories', 'updateOrder', [onDrag, type]), order, function (res) {
            search(document.srchForm);
        });
    };

    mediaForm = function(categoryId) {
        fcom.ajax(fcom.makeUrl('Categories', 'mediaForm', [categoryId]), '', function (response) {
            $.w3mentorsmodal(response);
            if (categoryId < 1 && document.srchForm.parent_id.value > 0) {
                document.frmCategory.cate_parent.value = document.srchForm.parent_id.value;
            }
        });
    }

    $(document).on('click', '.categoryFile-Js', function () {
        var node = this;
        $('#form-upload').remove();
        var frmName = $(node).attr('data-frm');
        if ('frmCategoryMedia' == frmName) {
            var categoryId = document.frmCategoryMedia.category_id.value;
        }
        var fileType = $(node).attr('data-file_type');
        var frm = '<form enctype="multipart/form-data" id="form-upload" style="position:absolute; top:-100px;" >';
        frm = frm.concat('<input type="file" name="file" />');
        frm = frm.concat('<input type="hidden" name="category_id" value="' + categoryId + '"/>');
        frm = frm.concat('<input type="hidden" name="file_type" value="' + fileType + '"></form>');
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
                fcom.ajaxMultipart(fcom.makeUrl('Categories', 'setupMedia', [categoryId]), data, function (res) {
                    $(node).val($val);
                    $('#form-upload').remove();
                    mediaForm(categoryId);
                }, {fOutMode: 'json'});
            }
        }, 500);
    });

    removeMedia = function (type, categoryId) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        var data = "type=" + type+ "&categoryId=" + categoryId;
        fcom.updateWithAjax(fcom.makeUrl('Categories', 'removeMedia'), data, function (res) {
            mediaForm(categoryId);
        });
    };
    updateFeatured = function(value) {
        $('.fldFeaturedJs').find('.caption-wraper').hide();
        if (value > 0) {
            $('.fldFeaturedJs').parent().hide();
            $('.fldFeaturedJs').find('input[name="cate_featured"]').attr('checked', false);
        } else {
            $('.fldFeaturedJs').parent().show();
        }
    };
})();