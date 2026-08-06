$(function () {
    retake = function (id) {
        if (confirm(langLbl.confirmRetake)) {
            fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'retake'), { 'progress_id' : id }, function (res) {
                window.location = fcom.makeUrl('Tutorials', 'index', [id]);
            });
        }
    };
    feedbackForm = function (ordcrsId) {
        fcom.ajax(fcom.makeUrl('Tutorials', 'feedbackForm'), { 'ordcrs_id': ordcrsId }, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    feedbackSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'feedbackSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            $('.reviewFrmJs').removeAttr('onclick').addClass('btn--disabled');
        });
    };
});