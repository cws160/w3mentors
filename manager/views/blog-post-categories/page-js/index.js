
/* global fcom, langLbl */
$(document).ready(function () {
    searchBlogPostCategories(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchBlogPostCategories(frm);
    }
    reloadList = function () {
        searchBlogPostCategories(document.srchFormPaging);
    }
    addCategoryForm = function (id) {
        categoryForm(id);
    };
    categoryForm = function (id) {
        var frm = document.srchFormPaging;
        var parent = $(frm.bpcategory_parent).val();
        if (typeof parent == undefined || parent == null) {
            parent = 0;
        }
        fcom.ajax(fcom.makeUrl('BlogPostCategories', 'form', [id, parent]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupCategory = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogPostCategories', 'setup'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                categoryLangForm(res.catId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    categoryLangForm = function (catId, langId) {
        fcom.ajax(fcom.makeUrl('BlogPostCategories', 'langForm', [catId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupCategoryLang = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogPostCategories', 'langSetup'), fcom.frmData(frm), function (res) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                categoryLangForm(res.catId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    searchBlogPostCategories = function (form) {
        fcom.ajax(fcom.makeUrl('BlogPostCategories', 'search'), fcom.frmData(form), function (res) {
            $("#listing").html(res);
        });
    };
    subcat_list = function (parent) {
        var frm = document.srchFormPaging;
        $(frm.bpcategory_parent).val(parent);
        reloadList();
    };
    categoryMediaForm = function (prodCatId) {
        fcom.ajax(fcom.makeUrl('BlogPostCategories', 'mediaForm', [prodCatId]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('BlogPostCategories', 'deleteRecord'), {id: id}, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchBlogPostCategories(document.srchForm);
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var bpcategoryId = parseInt(obj.id);
        var data = 'bpcategoryId=' + bpcategoryId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('BlogPostCategories', 'changeStatus'), data, function (res) {
            searchBlogPostCategories(document.srchForm);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var bpcategoryId = parseInt(obj.id);
        var data = 'bpcategoryId=' + bpcategoryId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('BlogPostCategories', 'changeStatus'), data, function (res) {
            searchBlogPostCategories(document.srchForm);
        });
    };
})();