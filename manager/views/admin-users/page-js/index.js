/* global fcom, langLbl */
$(document).ready(function () {
    searchAdminUsers();
    $(document).on('click', 'ul.linksvertical li a.redirect--js', function (event) {
        event.stopPropagation();
    });
});
(function () {
    var dv = '#listing';
    reloadList = function () {
        searchAdminUsers();
    };
    searchAdminUsers = function () {
        fcom.ajax(fcom.makeUrl('AdminUsers', 'search'), '', function (res) {
            $(dv).html(res);
        });
    };
    addForm = function (id) {
        fcom.ajax(fcom.makeUrl('AdminUsers', 'form', [id]), '', function (t) {  
            fcom.updatePopupContent(t);
        });
    };
    editForm = function (adminId) {
        fcom.ajax(fcom.makeUrl('AdminUsers', 'form', [adminId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupAdminUser = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('AdminUsers', 'setup'), fcom.frmData(frm), function (response) {
            reloadList();
            $.w3mentorsmodal.close();
        });
    };
    changePasswordForm = function (id) {
        fcom.ajax(fcom.makeUrl('AdminUsers', 'changePassword', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupChangePassword = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('AdminUsers', 'setupChangePassword'), fcom.frmData(frm), function (response) {
            reloadList();
            $.w3mentorsmodal.close();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var adminId = parseInt(obj.id);
        if (adminId < 1) {
            fcom.error(langLbl.invalidRequest);
            return false;
        }
        var data = 'adminId=' + adminId + '&status=1';
        fcom.ajax(fcom.makeUrl('AdminUsers', 'changeStatus'), data, function (res) {
            $(obj).removeClass("inactive");
            $(obj).addClass("active");
            $(".status_" + adminId).attr('onclick', 'inactiveStatus(this)');
            reloadList();
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            return;
        }
        var adminId = parseInt(obj.id);
        var data = 'adminId=' + adminId + '&status=0';
        fcom.ajax(fcom.makeUrl('AdminUsers', 'changeStatus'), data, function (res) {
            $(obj).removeClass("active");
            $(obj).addClass("inactive");
            $(".status_" + adminId).attr('onclick', 'activeStatus(this)');
            reloadList();
        });
    };
    clearSearch = function () {
        document.frmSearch.reset();
        searchAdminUsers(document.frmSearch);
    };
    checkPassword = function (str)
    {
        var re = /^(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z])(?=.*).{8,}$/;
        return re.test(str);
    };
})();
