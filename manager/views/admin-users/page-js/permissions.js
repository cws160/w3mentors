/* global fcom */

$(document).ready(function () {
    searchAdminUsersRoles(document.frmAdminSrchFrm);
});
(function () {
    var dv = '#listing';
    reloadList = function () {
        searchAdminUsersRoles(document.frmAdminSrchFrm);
    };
    searchAdminUsersRoles = function (form) {
        fcom.ajax(fcom.makeUrl('AdminUsers', 'roles'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    updatePermission = function (moduleId, permission) {
        if (!$(document.frmAllAccess).validate()) {
            return;
        }
        if (1 > moduleId) {
            if (!(permission = $('.permissionForAll').val())) {
                return false;
            }
        }
        var data = fcom.frmData(document.frmAdminSrchFrm);
        fcom.updateWithAjax(fcom.makeUrl('AdminUsers', 'updatePermission', [moduleId, permission]), data, function (response) {
            if (response.moduleId == 0) {
                searchAdminUsersRoles(document.frmAdminSrchFrm);
            }
        });
    };
})();
