/* global fcom */

$(document).ready(function () {
    searchLabels(document.frmLabelsSearch);
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.frmLabelsSrchPaging;
        $(frm.page).val(page);
        searchLabels(frm);
    };
    reloadList = function () {
        searchLabels(document.frmLabelsSrchPaging);
    };
    searchLabels = function (frm) {
        fcom.ajax(fcom.makeUrl('AppLabels', 'search'), fcom.frmData(frm), function (res) {
            $(dv).html(res);
        });
    };
    labelsForm = function (labelId) {
        fcom.ajax(fcom.makeUrl('AppLabels', 'form', [labelId]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    setupLabels = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('AppLabels', 'setup'), fcom.frmData(frm), function (t) {
            $.w3mentorsmodal.close();
            reloadList();
        });
    };
    regenerate = function () {
        fcom.updateWithAjax(fcom.makeUrl('AppLabels', 'regenerate'), '', function (t) { });
    };
    exportLabels = function () {
        document.frmLabelsSearch.action = fcom.makeUrl('AppLabels', 'export');
        document.frmLabelsSearch.submit();
    };
    importLabels = function () {
        fcom.ajax(fcom.makeUrl('AppLabels', 'importForm'), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    setupImport = function () {
        var data = new FormData();
        $inputs = $('#frmImportLabels input[type=text],#frmImportLabels select,#frmImportLabels input[type=hidden]');
        $inputs.each(function () {
            data.append(this.name, $(this).val());
        });
        $.each($('#import_file')[0].files, function (i, file) {
            data.append('import_file', file);
            fcom.ajaxMultipart(fcom.makeUrl('AppLabels', 'setupImport'), data, function (res) {
                $('#fileupload_div').html();
                reloadList();
                $.w3mentorsmodal.close();
            }, { fOutMode: 'json' });
        });
    };
    clearSearch = function () {
        document.frmLabelsSearch.reset();
        searchLabels(document.frmLabelsSearch);
    };
    autoFillLabel = function (tabel, labelKey, form) {
        data = fcom.frmData(form);
        data += '&tableName=' + tabel + '&recordId=' + labelKey;
        fcom.updateWithAjax(fcom.makeUrl('AdminBase', 'translateAndAutoFill'), data, function (res) {
            if (res.fields && Object.keys(res.fields).length > 0) {
                $.each(res.fields, function (langId, fieldData) {
                    $.each(fieldData, function (name, value) {
                        let $this = $('[name=' + name + ']');
                        $this.val(value);
                    });
                });
            }
        });
    };
})();