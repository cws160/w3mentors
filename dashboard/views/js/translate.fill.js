(function () {
    translateAndAutoFill = function (tableName, recordId, toLangId) {
        let data = {
            tableName: tableName,
            recordId: recordId,
            toLangId: toLangId
        }
        fcom.updateWithAjax(fcom.makeUrl('Dashboard', 'translateAndAutoFill'), data, function (res) {
            if (res.fields && Object.keys(res.fields).length > 0) {
                $.each(res.fields, function (langId, fieldData) {
                    $.each(fieldData, function (name, value) {
                        let $this = $('[name="' + name + '"]');
                        $this.val(value);
                        if ($this.is("textarea") && typeof oUtil !== 'undefined' && oUtil.arrEditor.length > 0) {
                            let editors = oUtil.arrEditor;
                            let id = $this.attr('id');
                            for (x in editors) {
                                if (editors[x] == 'oEdit_' + id) {
                                    var obj = eval('window.' + editors[x]);
                                    obj.putHTML(value);
                                }
                            }
                        }
                    });
                });
            }
        });
    }
})();