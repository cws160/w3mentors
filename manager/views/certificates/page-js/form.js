(function () {
    var preview = 0;
    setupMedia = function () {
        var frm = document.frmMedia;
        if (!$(frm).validate()) {
            return;
        }
        var data = new FormData(frm);
        fcom.ajaxMultipart(fcom.makeUrl('Certificates', 'setupMedia'), data, function (response) {
            $(frm)[0].reset();
            $('.certificateMediaJs img').attr('src', response.imgUrl);
        }, { fOutMode: 'json' });
    };
    setup = function () {
        var frm = (document.frmCertificate);
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        data += "&heading=" + encodeURIComponent($.trim($('.heading-js').text()));
        data += "&content_part_1=" + encodeURIComponent($.trim($('.content_part_1-js').text()));
        data += "&learner=" + encodeURIComponent($.trim($('.learner-js').text()));
        data += "&content_part_2=" + encodeURIComponent($.trim($('.content_part_2-js').text()));
        data += "&trainer=" + encodeURIComponent($.trim($('.trainer-js').text()));
        data += "&certificate_number=" + encodeURIComponent($.trim($('.certificate_number-js').text()));
        fcom.updateWithAjax(fcom.makeUrl('Certificates', 'setup'), data, function (t) {
            if (preview == 1) {
                preview = 0;
                var time = (new Date()).getTime();
                window.open(fcom.makeUrl('Certificates', 'generate', [$('input[name="certpl_id"]').val()]) + '?time=' + time, '_blank');
            }
        });
        return false;
    };
    edit = function (certTplCode, langId) {
        fcom.updateWithAjax(fcom.makeUrl('Certificates', 'setupLangData'), { 'template_code': certTplCode, 'lang_id': langId }, function (response) {
            window.location = fcom.makeUrl('Certificates', 'form', [certTplCode, langId]);
        });
    };
    setupAndPreview = function () {
        preview = 1;
        setup();
    };
    resetToDefault = function () {
        var data = fcom.frmData(document.frmCertificate);
        fcom.ajax(fcom.makeUrl('Certificates', 'getDefaultContent'), data, function (response) {
            if (response.data) {
                $('.heading-js').text(response.data.heading);
                $('.content_part_1-js').text(response.data.content_part_1);
                $('.learner-js').text(response.data.learner);
                $('.content_part_2-js').text(response.data.content_part_2);
                $('.trainer-js').text(response.data.trainer);
                $('.certificate_number-js').text(response.data.certificate_number);
            }
        }, { fOutMode: 'json' });
    };
})();
