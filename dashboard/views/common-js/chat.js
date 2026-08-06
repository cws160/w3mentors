
threadForm = function (id, type) {
    var data = "thread_type=" + type + "&receiver=" + id;
    fcom.ajax(fcom.makeUrl('Chats', 'threadForm', [], confWebRootUrl), data, function (res) {
        if (isJson(res)) {
            var response = JSON.parse(res);
            loadThread(response);
        } else {
            $.w3mentorsmodal(res, { 'size': 'modal-md' });
        }
    });
};

loadThread = function (response) {
    var form = document.createElement("form");
    form.setAttribute("method", 'POST');
    form.setAttribute("action", fcom.makeUrl('Chats', '', [response.threadId], confWebRootUrl));
    var hiddenField = document.createElement("input");
    hiddenField.setAttribute("name", 'thread_id');
    hiddenField.setAttribute("value", response.threadId);
    form.appendChild(hiddenField);
    document.body.appendChild(form);
    form.submit();
};

setupThreadForm = function (frm) {
    if (!$(frm).validate()) {
        return;
    }
    var formData = new FormData(frm);
    fcom.ajaxMultipart(fcom.makeUrl('Chats', 'threadSetup', [], confWebRootUrl), formData, function (res) {
        loadThread(res);
    }, { fOutMode: 'json' });
    return false;
};