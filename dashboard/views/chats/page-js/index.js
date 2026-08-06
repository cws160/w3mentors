/* global fcom, langLbl */
var threadId;
$(document).ready(function () {
    var frm = document.frmThreadSearch;
    threadListing(frm);
    $(".window__search-field-js").click(function () {
        $(".window__search-form-js").toggle();
    });
    $(".msg-list__action-js").click(function () {
        $(this).parent().toggleClass("is-active");
        $(".message-details-js").show();
        $("html").addClass("show-message-details");
        return false;
    });
    $(".msg-close-js").click(function () {
        $(".message-details-js").hide();
        $("html").removeClass("show-message-details");
        return false;
    });
});
var messageThreadPage = 1;
var messageThreadAjax = false;
var div = '#threadListing';
function threadListing(frm) {
    var data = fcom.frmData(frm);
    data = data;
    fcom.ajax(fcom.makeUrl('Chats', 'threadSearch'), data, function (res) {
        $(div).html(res);
        if (threadId > 0) {
            getThread(threadId);
        }
    });
    $(".window__search-form-js").hide();
}
function searchThreads(frm) {
    threadListing(frm);
    closethread();
    return false;
}
clearSearch = function () {
    document.frmThreadSearch.reset();
    threadListing(document.frmThreadSearch);
};
/* FUNCTION FOR SCROLLBAR */
function closethread() {
    $("body .message-details-js").hide();
    $("html").removeClass("show-message-details");
    $('#threadListing').find('.is-active').removeClass('is-active');
}

function getMessageCount() {
    fcom.updateWithAjax(fcom.makeUrl('Chats', 'getUnreadCount'), '', function (response) {
        if (response.messCount > 0) {
            let messages = (response.messCount >= 100) ? '100+' : response.messCount;
            $('.message-badge').attr('data-count', messages);
            return;
        }
        $('.message-badge').removeAttr("data-count");
    }, { process: false });
}

function getThread(id, page) {
    page = (page) ? page : messageThreadPage;
    if (page == 1) {
        messageThreadAjax = false;
    }
    if (messageThreadAjax) {
        return false;
    }
    if (isAdminLoggedIn == '0') {
        $('.msg-list-' + id).addClass('is-read');
    }
    $('.msg-list').removeClass('is-active');
    $('.msg-list-' + id).addClass('is-active');
    messageThreadPage += 1;
    dv = ".message-details-js";
    var data = "thread_id=" + id + "&page=" + page;
    fcom.ajax(fcom.makeUrl('Chats', 'messageSearch'), data, function (res) {
        if (page == 1) {
            $(dv).html(res).show();
            $("html").addClass("show-message-details");
            $(".chat-room__body").scrollTop($(".chat-room__body")[0].scrollHeight);
        } else {
            $('.load-more-js').remove();
            $('.chat-list').prepend(res);
        }
        if (isAdminLoggedIn == '0') {
            $("#unread-thread-" + id).remove();
        }
        getMessageCount();
        $('#message').unbind();
    });
    $('html').addClass('show-message-details');

}

function sendThreadMessage(frm) {
    if (document.getElementById('upload').files.length < 1) {
        if (!$(frm).validate()) {
            return;
        }
    }
    threadId = frm.thread_id.value
    var formData = new FormData(frm);
    fcom.ajaxMultipart(fcom.makeUrl('Chats', 'messageSetup'), formData, function (data) {
        messageThreadPage = 1;
        fcom.close();
        document.frmThreadSearch.thread_id.value = threadId;
        threadListing(document.frmThreadSearch);
    }, { fOutMode: 'json' });
    return false;
}
$(document).on('submit', 'form[name="frmMessage"]', function () {
    return false;
});
function selectFile(obj) {
    if (!obj.files[0]) {
        return false;
    }
    $('#message').attr('data-fatreq', '{"required":false,"lengthrange":[0,1000]}');
    var html = '<div class="attachment__item">';
    html += obj.files[0].name;
    html += '<a href="javascript:void(0)" class="attachment__item_remove" onclick="removeSelectedFile()"><svg class="icon icon--close icon--small" xmlns = "http://www.w3.org/2000/svg" viewBox = "0 0 24 24" ><path d="M12 10.586l4.95-4.95 1.414 1.414-4.95 4.95 4.95 4.95-1.414 1.414-4.95-4.95-4.95 4.95-1.414-1.414 4.95-4.95-4.95-4.95L7.05 5.636z"></path></svg ></a>';
    html += "</div>";
    $('#selectedFilesList').html(html);
}

function removeSelectedFile() {
    $('input[name="upload"]').val('');
    $('#message').attr('data-fatreq', '{"required":true,"lengthrange":[0,1000]}');
    $('#selectedFilesList').html('');
}

function deleteAttachment(msgId) {
    if (!confirm(langLbl.confirmRemove)) {
        return;
    }
    formData = 'msg_id=' + msgId + '&thread_id=' + threadId;
    fcom.updateWithAjax(fcom.makeUrl('Chats', 'removeAttachment'), formData, function (res) {
        var msgDiv = $('#msgRow' + msgId);
        msgDiv.find('.chat-attachment').remove();
        if ((msgDiv.find('.chat-text').text()).length < 1) {
            msgDiv.remove();
        }
        document.frmThreadSearch.thread_id.value = threadId;
        threadListing(document.frmThreadSearch);
        return;
    });
}
$('#message').unbind();
