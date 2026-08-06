
/* global langLbl, fcom */

$(function () {
    search = function () {
        fcom.updateWithAjax(fcom.makeUrl('ForumTags', 'subscribedSearch'), {}, function (res) {
            $('#listing').html(res.html);
            totalRecords = res.count;
            if (1 > totalRecords) {
                hideUnsubscribeAllLink();
                return;
            }
            showUnsubscribeAllLink();
        });
    };

    listSystemTags = function (pageno) {
        var pageno = pageno || 1;
        fcom.updateWithAjax(fcom.makeUrl('ForumTags', 'systemTagsList'), {'pageno': pageno}, function (res) {
            $('#system-tags').html(res.html);
        });
    };

    unScubscribe = function (ths) {
        if (!confirm(langLbl.confirmUnsubscribeTag)) {
            return;
        }
        var recordId = $(ths).data('row_id');
        fcom.updateWithAjax(fcom.makeUrl('ForumTags', 'unSubscribe'), {'ftag_id': recordId}, function (res) {
            if (1 === res.status) {
                $('#subscribedtag_' + recordId).remove();
                totalRecords = totalRecords - 1;
                if (1 > totalRecords) {
                    search();
                }
            }
        });
    };

    unScubscribeAll = function () {
        if (!confirm(langLbl.confirmUnsubscribeAllTags)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ForumTags', 'unSubscribeAll'), {}, function (res) {
            if (1 === res.status) {
                totalRecords = 0;
                search();
            }
        });
    };

    scubscribe = function (recordId) {
        var recordId = recordId || 0;
        if (1 > recordId) {
            console.log('invalid id');
            $("input[name='keyword']").val('');
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('ForumTags', 'subscribe'), {'ftag_id': recordId}, function (res) {
            if ('1' == res.status) {
                $("input[name='keyword']").val('');
                totalRecords = totalRecords + 1;
                search();
            }
        });
    };

    hideUnsubscribeAllLink = function ()
    {
        $(".unsubscribe-all").hide();
    };

    showUnsubscribeAllLink = function ()
    {
        $(".unsubscribe-all").show();
    }

    goToSearchPage = function (pageno)
    {
        listSystemTags(pageno);
    }

});

$(document).ready(function () {
    search();
    listSystemTags();
    $('input[name="keyword"]').autocomplete({
        'delay': 500,
        'source': function (request, response) {
            fcom.updateWithAjax(fcom.makeUrl('ForumTags', 'autoSuggestList'), {
                keyword: request.term
            }, function (result) {
                response($.map(result.data, function (item) {
                    return {
                        label: item['ftag_name'],
                        value: item['ftag_id']
                    };
                }));
            });
        },
        'select': function (event, ui) {
            if (ui.item.value == -1) {
                event.preventDefault();
                return;
            }
            event.preventDefault();
            scubscribe(ui.item.value);
            $("input[name='keyword']").val(ui.item.label);
        },
        'focus': function (event, ui) {
            if (ui.item.value == -1) {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            $("input[name='keyword']").val(ui.item.label);
        }
    });

    $('#unsubscribe-all-js').on('click', function () {
        unScubscribeAll();
    });

});