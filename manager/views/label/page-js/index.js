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
        fcom.ajax(fcom.makeUrl('Label', 'search'), fcom.frmData(frm), function (res) {
            $(dv).html(res);
        });
    };
    labelsForm = function (labelId) {
        fcom.ajax(fcom.makeUrl('Label', 'form', [labelId]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    setupLabels = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Label', 'setup'), fcom.frmData(frm), function (t) {
            $.w3mentorsmodal.close();
            reloadList();
        });
    };
    clearSearch = function () {
        document.frmLabelsSearch.reset();
        searchLabels(document.frmLabelsSearch);
    };
    exportLabels = function () {
        document.frmLabelsSearch.action = fcom.makeUrl('Label', 'export');
        document.frmLabelsSearch.submit();
    };
    importLabels = function () {
        fcom.ajax(fcom.makeUrl('Label', 'importLabelsForm'), '', function (t) {
            $.w3mentorsmodal(t);
        });
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
    submitImportLaeblsUploadForm = function () {
        var data = new FormData();
        $inputs = $('#frmImportLabels input[type=text],#frmImportLabels select,#frmImportLabels input[type=hidden]');
        $inputs.each(function () {
            data.append(this.name, $(this).val());
        });
        $.each($('#import_file')[0].files, function (i, file) {
            data.append('import_file', file);
            fcom.ajaxMultipart(fcom.makeUrl('Label', 'uploadLabelsImportedFile'), data, function (res) {
                $('#fileupload_div').html();
                reloadList();
                $.w3mentorsmodal.close();
            }, { fOutMode: 'json' });
        });
    };
})();