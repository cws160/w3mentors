var dv;
var ele = 'w3mentorsModal';
var options = {size: 'modal-md', backdrop : 'static' , addClass : '', fade : 'fade'};
(function ($) {
    $.w3mentorsmodal = function (data, opts = {}) {
        options = $.extend(options, opts);
        init();
        $.w3mentorsmodal.reveal(data);
    };
    function init() {
        $.w3mentorsmodal.close();
        
        dv = document.createElement('div');
        $(dv).addClass('modal show ' + options.fade + ' ' + options.size +' '+ options.addClass).attr({ 'id': ele, 'tabindex': "-1", 'role': "dialog", 'data-bs-backdrop': options.backdrop, 'aria-modal':"true" });
        $('body').append(dv);
    }
    $.extend($.w3mentorsmodal, {
        reveal: function (content) {
            $(dv).html('<div class="modal-dialog modal-dialog-centered modal-dialog-vertical " role="document"><div class="modal-content contentBodyJs">' + content + '</div></div>');
            $.w3mentorsmodal.show();
        },
        close: function () {
            $("#" + ele).modal("hide");
            $.w3mentorsmodal.clear();
            return;
        },
        show: function () {
            $("#" + ele).modal("show");
            return;
        },
        clear: function () {
            if ($('.modal').length > 0) {
                $('.modal, .modal-backdrop').remove();
            }
        },
    });
    $(document).on("hide.bs.modal", "#" + ele, function () {
        $.w3mentorsmodal.clear();
    });
})(jQuery);