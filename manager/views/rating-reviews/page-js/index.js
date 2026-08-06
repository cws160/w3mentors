/* global fcom */
$(document).ready(function () {
    search(document.srchForm);
    $("input[name='ratrev_user']").autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'AutoCompleteJson'),
                    {keyword: request}, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                        value: item['user_id'], name: item['full_name']
                    };
                }));
            }, { process: false });
        },
        'select': function (item) {
            $("input[name='ratrev_user_id']").val(item.value);
            $("input[name='ratrev_user']").val(item.name);
        }
    });
    $("input[name='ratrev_user']").keyup(function () {
        $("input[name='ratrev_user_id']").val('');
    });
    $("input[name='ratrev_teacher']").keyup(function () {
        $("input[name='ratrev_teacher_id']").val('');
    });
    $(document).on('click', 'ul.linksvertical li a.redirect--js', function (event) {
        event.stopPropagation();
    });

    $("input[name='ratrev_teacher']").autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'AutoCompleteJson'),
                    {keyword: request}, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                        value: item['user_id'], name: item['full_name']
                    };
                }));
            }, { process: false });
        },
        'select': function (item) {
            $("input[name='ratrev_teacher_id']").val(item.value);
            $("input[name='ratrev_teacher']").val(item.name);
        }
    });


});
(function () {
    goToSearchPage = function (pageno) {
        var frm = document.srchFormPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (form) {
        fcom.ajax(fcom.makeUrl('RatingReviews', 'search'), fcom.frmData(form), function (res) {
            $('#listing').html(res);
        });
    };
    form = function (ratrevId) {
        fcom.ajax(fcom.makeUrl('RatingReviews', 'form'), 'ratrevId=' + ratrevId, function (res) {
            $.w3mentorsmodal(res);
        });
    };
    setup = function (form) {
        if (!$(form).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('RatingReviews', 'setup'), fcom.frmData(form), function (res) {
            search(document.srchFormPaging);
            $.w3mentorsmodal.close();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        if (document.srchForm.ratrev_user_id) {
            document.srchForm.ratrev_user_id.value = '';
        }
        if (document.srchForm.ratrev_teacher_id) {
            document.srchForm.ratrev_teacher_id.value = '';
        }
        search(document.srchForm);
    };
})();