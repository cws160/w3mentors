var chat_appid = '';
var chat_auth = '';
var chat_id = '';
var chat_name = '';
var chat_avatar = '';
var chat_role = '';
var chat_friends = '';
var chat_height = '100%';
var chat_width = '100%';
function loadChatBox(data, chatBoxId) {
    console.log(data);
    chat_appid = data.chat_appid;
    chat_auth = data.chat_auth;
    chat_id = data.chat_id;
    chat_name = data.chat_name;
    chat_avatar = data.chat_avatar;
    chat_role = data.chat_role;
    if (data.chat_friends && data.chat_friends != '') {
        chat_friends = data.chat_friends.toString();
    }
    $(chatBoxId).html('<div id="cometchat_embed_synergy_container" style="width:100%;height:100%;max-width:100%;border:1px solid #CCCCCC;border-radius:5px;overflow:hidden;"></div>');
    var chatJs = document.createElement('script');
    chatJs.type = 'text/javascript';
    chatJs.src = data.chat_js;
    chatJs.onload = function () {
        var chatIframe = {};
        chatIframe.module = "synergy";
        chatIframe.style = "min-height:400px;height:100%;min-width:100%;";
        chatIframe.width = chat_width.replace('px', '');
        chatIframe.height = chat_height.replace('px', '');
        chatIframe.src = data.chat_url;
        if (typeof (addEmbedIframe) == "function") {
            addEmbedIframe(chatIframe);
        }
    };
    var chat_script = document.getElementsByTagName('script')[0];
    chat_script.parentNode.insertBefore(chatJs, chat_script);
}