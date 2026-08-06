/* global fcom, langLbl, forum */
$(function () {
    var comntsDv = $("#comments--listing");
    addComment = function (frm) {
        if (1 > forum.getloggedUserId('maindv__js')) {
            return;
        }
        fcom.ajax(fcom.makeUrl('Forum', 'addComment'), fcom.frmData(frm), function (response) {
            var data = JSON.parse(response);
            if (data.status == 1) {
                $(frm).find("[name=fcomm_comment]").val('');
                comments($(frm).find("[name=fcomm_fque_id]").val());
            }
        });
    };
    comments = function (quesId, page) {
        var page = parseInt(page) || 1;
        var quesId = parseInt(quesId) || 0;
        var postData = {que_id: quesId, page: page, order_by: $('input[name="sort_option"]:checked').val()};
        fcom.updateWithAjax(fcom.makeUrl('Forum', 'comments'), postData, function (res) {
            if (1 == page) {
                comntsDv.html(res.htm);
                $('#comments-count').text(res.totalCount);
                if (2 > res.totalCount) {
                    $('#sorting-js').remove();
                }
                setTimeout(resetIframes, 500);
                return;
            }
            comntsDv.find("#loadMoreBtn").remove();
            comntsDv.append(res.htm);
            setTimeout(resetIframes, 500);
        });
    };
    markAction = function (ths, recordId, queId) {
        if (forum.getQueUserId('maindv__js') != forum.getloggedUserId('maindv__js')) {
            fcom.error(errs.not_owner_of_question);
            return false;
        }
        fcom.ajax(fcom.makeUrl('Forum', 'markComment'), {record_id: recordId, fque_id: queId}, function (res) {
            var data = JSON.parse(res);
            if (1 == data.status) {
                $('.article-mark').removeClass('is-active');
                if (1 == data.marked) {
                    $(ths).addClass('is-active');
                }
            }
        });
    };
    showSigninForm = function ()
    {
        signinForm();
    };
    $('.goto-comments-js').click(function () {
        let target = $(this).attr('data-target');
        $('html, body').animate({ scrollTop: $(target).offset().top - 100 }, 100);
    });
});
$(document).ready(function () {
    $('.comment-target-js').hide();
    $('.comment-trigger-js').click(function () {
        if ($(this).hasClass('is-active')) {
            $(this).removeClass('is-active');
            $(this).siblings('.comment-target-js').hide();
            return false;
        }
        $('.comment-trigger-js').removeClass('is-active');
        $(this).addClass("is-active");
        $('.comment-target-js').hide();
        $(this).siblings('.comment-target-js').show();
        $("#fcomm_comment").focus();
    });
    $(".sorting-trigger-js").click(function () {
        $(".sorting-target-js").toggle();
    });
    $("#sort_radio_list_js input:radio").click(function () {
        var trgtElm = $(this).val() + '_js';
        $('.sorting__value').text($('#' + trgtElm).text());
        comments($('#' + trgtElm).data('que_id'));
    });
    $('#sort_radio_list_js input:radio:first').trigger("click");
    $('.view-comments-section-js').click(function () {
        var elem = $('#_comments');
        if (elem.length) {
            forum.scrollToElem('html, body', elem, $(elem).offset().top, 2000);
        }
    });
    var elem = $('#_' + window.location.hash.replace('#', ''));
    if (elem.length) {
        forum.scrollToElem('html, body', elem, $(elem).offset().top, 2000);
    }

    setTimeout(resetIframes, 500);

});
