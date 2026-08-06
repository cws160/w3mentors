/* global fcom, langLbl */
$(document).ready(function () {
    searchBlogPosts(document.srchForm);
});
$(document).delegate('.language-js', 'change', function () {
    var lang_id = $(this).val();
    var post_id = $("input[name='post_id']").val();
    images(post_id, lang_id);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchBlogPosts(frm);
    };
    reloadList = function () {
        searchBlogPosts(document.srchFormPaging);
    };
    addBlogPostForm = function (id) {
        blogPostForm(id);
    };
    blogPostForm = function (id) {
        fcom.resetEditorInstance;
        fcom.ajax(fcom.makeUrl('BlogPosts', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogPosts', 'setup'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.postId, langId);
                return;
            }
        });
    };
    setupPostCategories = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogPosts', 'setupCategories'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.postId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (postId, langId) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('BlogPosts', 'langForm', [postId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
            fcom.setEditorLayout(langId);
        });
    };
    langSetup = function () {
        var frm = $('.modal form')[0];
        $(frm).validation({errordisplay: 3});
        if ($(frm).validate() == false) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogPosts', 'langSetup'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                if (langId == 'media') {
                    postImages(res.postId);
                } else {
                    langForm(res.postId, langId);
                }
                return;
            } 
            $.w3mentorsmodal.close();
        });
    };
    searchBlogPosts = function (form) {
        fcom.ajax(fcom.makeUrl('BlogPosts', 'search'), fcom.frmData(form), function (res) {
            $("#listing").html(res);
        });
    };
    linksForm = function (id) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('BlogPosts', 'linksForm', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogPosts', 'deleteRecord'), {id: id}, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchBlogPosts(document.srchForm);
    };
    postImages = function (post_id) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('BlogPosts', 'imagesForm', [post_id]), '', function (t) {
            images(post_id);
            $.w3mentorsmodal(t);
        });
    };
    images = function (post_id, lang_id) {
        fcom.ajax(fcom.makeUrl('BlogPosts', 'images', [post_id, lang_id]), '', function (t) {
            $('#image-listing').html(t);
        });
    };
    deleteImage = function (postId, fileId, lang_id) {
        var agree = confirm(langLbl.confirmDelete);
        if (!agree) {
            return false;
        }
        fcom.ajax(fcom.makeUrl('BlogPosts', 'deleteImage', [postId, fileId, lang_id]), '', function (t) {
            images(postId, lang_id);
        });
    };
})();
$(document).on('click', '.blogFile-Js', function () {
    var node = this;
    $('#form-upload').remove();
    var frmName = $(node).attr('data-frm');
    if ('frmBlogPostImage' == frmName) {
        var langId = document.frmBlogPostImage.lang_id.value;
        var postId = document.frmBlogPostImage.post_id.value;
    }
    var fileType = $(node).attr('data-file_type');
    var frm = '<form enctype="multipart/form-data" id="form-upload" style="position:absolute; top:-100px;" >';
    frm = frm.concat('<input type="file" name="file" />');
    frm = frm.concat('<input type="hidden" name="post_id" value="' + postId + '"/>');
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
            fcom.ajaxMultipart(fcom.makeUrl('BlogPosts', 'uploadBlogPostImages', [postId, langId]), data, function (res) {
                $(node).val($val);
                $('#form-upload').remove();
                images(postId, langId);
            }, {fOutMode: 'json'});
        }
    }, 500);
});
