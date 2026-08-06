/* global fcom, langLbl */
$(function () {
    var dv = '#listItems';
    searchGroupClasses = function (frm) {
        fcom.ajax(fcom.makeUrl('GroupClasses', 'search'), fcom.frmData(frm), function (t) {
            $(dv).html(t);
        });
    };
    
    form = function (id) {
        fcom.ajax(fcom.makeUrl('GroupClasses', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
            jQuery('#grpcls_start_datetime,#grpcls_end_datetime').each(function () {
                $(this).datetimepicker({format: 'Y-m-d H:i'});
            });
        });
    };
    removeClass = function (id) {
        if (!confirm(langLbl.confirmRemove)) {
            return;
        }
        fcom.ajax(fcom.makeUrl('GroupClasses', 'removeClass', [id]), '', function (t) {
            searchGroupClasses(document.srchFormPaging);
        });
    };
    cancelClass = function (id) {
        if (confirm(langLbl.confirmCancel)) {
            fcom.ajax(fcom.makeUrl('GroupClasses', 'cancelClass', [id]), '', function (t) {
                searchGroupClasses(document.srchFormPaging);
            });
        }
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return false;
        }
        fcom.updateWithAjax(fcom.makeUrl('GroupClasses', 'setup'), fcom.frmData(frm), function (t) {
            searchGroupClasses(document.srchFormPaging);
            $.w3mentorsmodal.close();
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        document.srchForm.teacher_id.value = '';
        searchGroupClasses(document.srchForm);
    };
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchGroupClasses(frm);
    };
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
    searchGroupClasses(document.srchForm);
});