/* global fcom */

$(document).ready(function () {
    search(document.srchForm);
    $(document).on('click', function () {
        $('.autoSuggest').empty();
    });
    $('input[name=\'teacher\']').autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'AutoCompleteJson'),
                    {keyword: request}, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                        value: item['user_id'], name: item['full_name']
                    };
                }));
            }, {process: false});
        },
        'select': function (item) {
            $("input[name='teacher_id']").val(item.value);
            $("input[name='teacher']").val(item.name);
        }
    });
    $('input[name=\'teacher\']').keyup(function () {
        $('input[name=\'teacher_id\']').val('');
    });
    $('input[name=\'learner\']').autocomplete({
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('Users', 'AutoCompleteJson'),
                    {keyword: request}, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                        value: item['user_id'], name: item['full_name']
                    };
                }));
            }, {process: false});
        },
        'select': function (item) {
            $("input[name='learner_id']").val(item.value);
            $("input[name='learner']").val(item.name);
        }
    });
    $('input[name=\'learner\']').keyup(function () {
        $('input[name=\'learner_id\']').val('');
    });
    //redirect user to login page
    $(document).on('click', 'ul.linksvertical li a.redirect--js', function (event) {
        event.stopPropagation();
    });
});
(function () {
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        search(frm);
    };
    search = function (form, page) {
        fcom.ajax(fcom.makeUrl('ReportedIssues', 'search'), fcom.frmData(form), function (res) {
            $("#issueListing").html(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        if (document.srchForm.teacher_id) {
            document.srchForm.teacher_id.value = '';
        }
        if (document.srchForm.learner_id) {
            document.srchForm.learner_id.value = '';
        }
        search(document.srchForm);
    };
    view = function (issueId) {
        fcom.ajax(fcom.makeUrl('ReportedIssues', 'view', [issueId]), '', function (t) {
            $.w3mentorsmodal(t);
        });
    };
    actionForm = function (issrepId) {
        fcom.ajax(fcom.makeUrl('ReportedIssues', 'actionForm', [issrepId]), '', function (response) {
            $.w3mentorsmodal(response);
        });
    };
    setupAction = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ReportedIssues', 'setupAction'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            search(document.srchFormPaging);
        });
    };
})();