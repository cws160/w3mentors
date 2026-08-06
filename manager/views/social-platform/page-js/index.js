/* global fcom, e, langLbl */
$(document).ready(function () {
    searchSocialPlatforms();
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    reloadList = function () {
        searchSocialPlatforms();
    };
    searchSocialPlatforms = function (form) {
        fcom.ajax(fcom.makeUrl('SocialPlatform', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    addFormNew = function (id) {
        addForm(id);
    };
    addForm = function (id) {
        fcom.ajax(fcom.makeUrl('SocialPlatform', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('SocialPlatform', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            $.w3mentorsmodal.close();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var splatformId = parseInt(obj.id);
        var data = 'splatformId=' + splatformId + '&status=' + active;
        fcom.ajax(fcom.makeUrl('SocialPlatform', 'changeStatus'), data, function (res) {
            searchSocialPlatforms();
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var splatformId = parseInt(obj.id);
        var data = 'splatformId=' + splatformId + '&status=' + inActive;
        fcom.ajax(fcom.makeUrl('SocialPlatform', 'changeStatus'), data, function (res) {
            searchSocialPlatforms();
        });
    };
})();