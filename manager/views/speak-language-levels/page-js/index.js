/* global fcom, langLbl */
$(document).ready(function () {
    search(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    search = function (form) {
        fcom.ajax(fcom.makeUrl('SpeakLanguageLevels', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    form = function (id) {
        fcom.ajax(fcom.makeUrl('SpeakLanguageLevels', 'form', [id]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('SpeakLanguageLevels', 'setup'), fcom.frmData(frm), function (res) {
            search(document.srchForm);
            let element = $('.tab-inline a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.sLangLevelId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    }
    langForm = function (sLangLevelId, langId) {
        fcom.ajax(fcom.makeUrl('SpeakLanguageLevels', 'langForm', [sLangLevelId, langId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    langSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('SpeakLanguageLevels', 'langSetup'), fcom.frmData(frm), function (res) {
            search(document.srchForm);
            let element = $('.tab-inline a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.sLangLevelId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'sLangLevelId=' + id;
        fcom.updateWithAjax(fcom.makeUrl('SpeakLanguageLevels', 'deleteRecord'), data, function (res) {
            search(document.srchForm);
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var sLangLevelId = parseInt(obj.id);
        var data = 'sLangLevelId=' + sLangLevelId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('SpeakLanguageLevels', 'changeStatus'), data, function (res) {
            $(obj).removeClass("inactive");
            $(obj).addClass("active");
            $(".status_" + sLangLevelId).attr('onclick', 'inactiveStatus(this)');
            search(document.srchForm);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var sLangLevelId = parseInt(obj.id);
        var data = 'sLangLevelId=' + sLangLevelId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('SpeakLanguageLevels', 'changeStatus'), data, function (res) {
            $(obj).removeClass("active");
            $(obj).addClass("inactive");
            $(".status_" + sLangLevelId).attr('onclick', 'activeStatus(this)');
            search(document.srchForm);
        });
    };

    clearSearch = function () {
        document.srchForm.reset();
        search(document.srchForm);
    };
})();