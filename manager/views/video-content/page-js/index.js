/* global fcom */
$(document).ready(function () {
    searchPages(document.srchForm);
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchPages(frm);
    };
    reloadList = function () {
        searchPages(document.srchFormPaging);
    };
    searchPages = function (form) {
        var dv = '#pageListing';
        fcom.ajax(fcom.makeUrl('VideoContent', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    addForm = function (id) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('VideoContent', 'form', [id]), '', function (t) {
            $.w3mentorsmodal(t);
            var frm = $('.modal form')[0];
            var validator = $(frm).validation({errordisplay: 3});
            $(frm).submit(function (e) {
                e.preventDefault();
                setup(frm, validator);
            });
        });
    };
    setup = function (frm, validator) {
        validateYoutubelink(frm.biblecontent_url);
        validator.validate();
        if (!validator.isValid()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('VideoContent', 'setup'), data, function (res) {
            fcom.success(res.msg);
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                addLangForm(res.bibleId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
        return false;
    };
    addLangForm = function (pageId, langId) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('VideoContent', 'langForm', [pageId, langId]), '', function (t) {
            $.w3mentorsmodal(t);
            var frm = $('.modal form')[0];
            var validator = $(frm).validation({errordisplay: 3});
            $(frm).submit(function (e) {
                e.preventDefault();
                validator.validate();
                if (!validator.isValid()) {
                    return;
                }
                var data = fcom.frmData(frm);
                fcom.updateWithAjax(fcom.makeUrl('VideoContent', 'langSetup'), data, function (res) {
                    reloadList();
                    let element = $('.tabs-nav a.active').parent().next('li');
                    if (element.length > 0) {
                        let langId = element.find('a').attr('data-id');
                        addLangForm(res.biblecontent_id, langId);
                        return;
                    }
                    $.w3mentorsmodal.close();
                });
            });
        });
    };
    setupLang = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('VideoContent', 'langSetup'), fcom.frmData(frm), function (res) {
            reloadList();
            if (res.langId > 0) {
                addLangForm(res.biblecontent_id, res.langId);
                return;
            }
        });
    };
    deleteRecord = function (id) {
        if (!confirm("Do you really want to delete this record?")) {
            return;
        }
        fcom.ajax(fcom.makeUrl('VideoContent', 'deleteRecord'), {id: id}, function (res) {
            reloadList();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        searchPages(document.srchForm);
    };
    toggleStatus = function (obj) {
        if (!confirm("Do you really want to update status?")) {
            return;
        }
        var biblecontentId = parseInt(obj.id);
        if (biblecontentId < 1) {
            fcom.error('Invalid Request!');
            return false;
        }
        var statusStr = '';
        if ($(obj).hasClass('active')) {
            statusStr = 'biblecontent_active=0';
        } else {
            statusStr = 'biblecontent_active=1';
        }
        var data = 'biblecontent_id=' + biblecontentId + '&' + statusStr;
        fcom.ajax(fcom.makeUrl('VideoContent', 'changeStatus'), data, function (res) {
            $(obj).toggleClass("active");
            setTimeout(function () {
                reloadList();
            }, 1000);
        });
    };
})();
function showMarketingMediaType(val) {
    var selectedMediaType = parseInt(val);
    if (isNaN(selectedMediaType)) {
        selectedMediaType = 0;
    }
    $('.media-types').parents('.col-3').hide();
    switch (selectedMediaType) {
        case 1:
            $('#ImageId').parents('.col-3').show();
            break;
        case 2:
            $('#videoId').parents('.col-3').show();
            break;
    }
    return true;
}
