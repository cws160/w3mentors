/* global fcom, langLbl, e, oUtil */
var blockType;
$(document).ready(function () {
    searchBlocks(type);
});
(function () {
    var active = 1;
    var inActive = 0;
    reloadList = function (type) {
        searchBlocks(type);
    };
    searchBlocks = function (type) {
        blockType = type;
        var dv = '#frmBlock';
        fcom.ajax(fcom.makeUrl('ContentBlock', 'search', [type]), '', function (res) {
            $(dv).html(res);
        });
    };
    addBlockFormNew = function (id) {
        addBlockForm(id);
    };
    addBlockForm = function (id) {
        fcom.ajax(fcom.makeUrl('ContentBlock', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupBlock = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('ContentBlock', 'setup'), data, function (res) {
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                langForm(res.epageId, langId);
                return;
            }
            reloadList($('.blocksTabJs li a.active').data('type'));
            $.w3mentorsmodal.close();
        });
    };
    langForm = function (epageId, langId) {
        fcom.resetEditorInstance();
        fcom.ajax(fcom.makeUrl('ContentBlock', 'langForm', [epageId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
            fcom.setEditorLayout(langId);
            var frm = $('.modal form')[0];
            var validator = $(frm).validation({errordisplay: 3});
            $(frm).submit(function (e) {
                e.preventDefault();
                validator.validate();
                if (!validator.isValid()) {
                    return;
                }
                var data = fcom.frmData(frm);
                fcom.updateWithAjax(fcom.makeUrl('ContentBlock', 'langSetup'), data, function (res) {
                    fcom.resetEditorInstance();
                    reloadList($('.blocksTabJs li a.active').data('type'));
                    let element = $('.tabs-nav a.active').parent().next('li');
                    if (element.length > 0) {
                        let langId = element.find('a').attr('data-id');
                        langForm(res.epageId, langId);
                        return;
                    }
                    $.w3mentorsmodal.close();
                });
            });
        });
    };
    setupBlockLang = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ContentBlock', 'langSetup'), fcom.frmData(frm), function (res) {
            reloadList($('.blocksTabJs li a.active').data('type'));
            if (t.langId > 0) {
                langForm(res.epageId, res.langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    resetToDefaultContent = function () {
        var agree = confirm(langLbl.confirmReplaceCurrentToDefault);
        if (!agree) {
            return false;
        }
        oUtil.obj.putHTML($("#editor_default_content").html());
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var epageId = parseInt(obj.id);
        data = 'epageId=' + epageId + '&status=' + active;
        fcom.ajax(fcom.makeUrl('ContentBlock', 'changeStatus'), data, function (res) {
            searchBlocks(blockType);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var epageId = parseInt(obj.id);
        var data = 'epageId=' + epageId + '&status=' + inActive;
        fcom.updateWithAjax(fcom.makeUrl('ContentBlock', 'changeStatus'), data, function (res) {
            searchBlocks(blockType);
        });
    };
    removeBgImage = function (epage_id, langId, file_type) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ContentBlock', 'removeBgImage', [epage_id, langId, file_type]), '', function (res) {
            langForm(epage_id, langId);
        });
    };
    updateOrder = function (onDrag = 1) {
        var order = $("#blockListingTbl").tableDnDSerialize();
        fcom.updateWithAjax(fcom.makeUrl('ContentBlock', 'updateOrder', [onDrag]), order, function (res) {
            searchBlocks(type);
        });
    }
})();
