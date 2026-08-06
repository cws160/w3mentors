$(function () {
    goToSearchPage = function (page) {
        var frm = document.frmPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Courses', 'search'), fcom.frmData(frm), function (res) {
            $("#listing").html(res);
        });
    };
    clearSearch = function () {
        document.frmSearch.reset();
        getSubCategories(0);
        search(document.frmSearch);
    };
    form = function () {
        fcom.ajax(fcom.makeUrl('Courses', 'form'), '', function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-md' });
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = new FormData(frm);
        fcom.ajaxMultipart(fcom.makeUrl('Courses', 'setup'), data, function (res) {
            $.w3mentorsmodal.close();
            search(document.frmSearch);
        }, { fOutMode: 'json' });
    };
    remove = function (courseId) {
        if (confirm(langLbl.confirmRemove)) {
            fcom.ajax(fcom.makeUrl('Courses', 'remove', [courseId]), '', function (res) {
                search(document.frmSearch);
            });
        }
    };
    cancelForm = function (ordcrsId) {
        fcom.ajax(fcom.makeUrl('Courses', 'cancelForm'), { 'ordcrs_id': ordcrsId }, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-md' });
        });
    };
    cancelSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Courses', 'cancelSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            search(document.frmSearch);
        });
    };
    search(document.frmSearch);
    feedbackForm = function (ordcrsId) {
        fcom.ajax(fcom.makeUrl('Tutorials', 'feedbackForm'), { 'ordcrs_id': ordcrsId }, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-md' });
        });
    };
    feedbackSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'feedbackSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            search(document.frmSearch);
        });
    };
    retake = function (id) {
        if (confirm(langLbl.confirmRetake)) {
            fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'retake'), { 'progress_id': id }, function (res) {
                window.location = fcom.makeUrl('Tutorials', 'index', [id]);
            });
        }
    };
    getSubCategories = function (id) {
        id = (id == '') ? 0 : id;
        fcom.ajax(fcom.makeUrl('Courses', 'getSubcategories', [id]), '', function (res) {
            $("#subCategories").html(res);
        }, { process: false });
    };
    editRequestForm = function (id) {
        fcom.ajax(fcom.makeUrl('Courses', 'editRequestForm'), { 'course_id': id }, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        }, { process: true });
    }
    editRequestSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Courses', 'editRequestSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            search(document.frmSearch);
        });
    }
});
