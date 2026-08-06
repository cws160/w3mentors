(function ($) {
    var displayInPopup = false;
    $.w3mentorsmodal = function (data, popupView = false, dialogClassParm = "", modalClassParm = "", bodyClass = "") {
        modalClass = 'fixed-right ' + modalClassParm;
        var dialogClass = 'modal-dialog-vertical ' + dialogClassParm;
        var bodyClass = 'p-0' + bodyClass;

        /* !! is used to convert variable type in to bool. */
        displayInPopup = !!popupView;
        if (true == popupView) {
            modalClass = modalClassParm;
            dialogClass = 'modal-dialog-centered ' + dialogClassParm;
        }

        init(modalClass, dialogClass);
        if (data.ajax) {
            fillYKModalFromAjax(data.ajax);
        } else if (data.image) {
            fillYKModalFromImage(data.image);
        } else if (data.div) {
            fillYKModalFromHref(data.div);
        } else if ($.isFunction(data)) {
            data.call($);
        } else {
            $.w3mentorsmodal.reveal(data, bodyClass);
    }
    };

    $.extend($.w3mentorsmodal, {
        element: Date.now(),
        reveal: function (data, bodyClass) {
            if ($(data).hasClass("loaderJs") && 0 < $("." + $.w3mentorsmodal.element + " .loaderContainerJs").length) {
                $("." + $.w3mentorsmodal.element + " .loaderContainerJs").prepend(data);
                return;
            }

            if (0 == $(data).find(".modal-body").length && false === $(data).hasClass("modal-body")) {
                data = '<div class="modal-body">' + data + "</div>";
            }

            var contentBody = "." + $.w3mentorsmodal.element + " .contentBodyJs";
            $(contentBody).html(data);
            var headerHtm = '<div class="modal-header">';
            var closeBtnHtm = '<button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label="' + langLbl.close + '"></button>';
            
            if (1 > $(contentBody).find(".modal-header").length && 1 <= $(contentBody).find(".card-head-title").length) {
                headerHtm = headerHtm + '<h5>' + $(contentBody).find(".card-head-title").text() + '</h5>';
                $(contentBody).find(".card-head").remove();
                $(contentBody).prepend(headerHtm + closeBtnHtm + "</div>");
            } else if (1 > $(contentBody).find(".modal-header").length) {
                $(contentBody).prepend(headerHtm + closeBtnHtm + "</div>");
            } else if (0 < $(contentBody).find(".modal-header").length && 1 > $("body ." + $.w3mentorsmodal.element + " .contentBodyJs .modal-header").find(".close").length) {
                $("body ." + $.w3mentorsmodal.element + " .contentBodyJs .modal-header").append(closeBtnHtm);
            }

            if ("undefined" != typeof bodyClass && 0 == $(data).find(bodyClass).length) {
                $(contentBody + " .modal-body").addClass(bodyClass);
            }

            $.w3mentorsmodal.show();
        },
        setEditorLayout: function (lang_id) {
            var editors = oUtil.arrEditor;
            layout = langLbl['language' + lang_id];
            for (x in editors) {
                $('#idContent' + editors[x]).contents().find("body").css('direction', layout);
            }
            $('table').find(".istoolbar_container").attr('dir', layout);
        },
        close: function () {
            $("." + $.w3mentorsmodal.element).modal("hide");
            return;
        },
        show: function () {
            $("." + $.w3mentorsmodal.element).modal("show");
            return;
        },
        isAdded: function () {
            return (0 < $("." + $.w3mentorsmodal.element).length);
        },
        remove: function () {
            $("." + $.w3mentorsmodal.element + ', .modal-backdrop').remove();
        },
        isSideBarView: function () {
            return !!$(".fixed-right." + $.w3mentorsmodal.element).length;
        }
    });

    function init(modalClass, dialogClass) {
        if (1 > $("body").find("." + $.w3mentorsmodal.element).length) {
            var content = '<div class="modal-dialog ' + dialogClass + ' " role="document"><div class="modal-content contentBodyJs"></div></div>';
            var htm = '<div class="modal ' + modalClass + ' fade ' + $.w3mentorsmodal.element + '" tabindex="-1" role="dialog">' + content + "</div>";
            $("body").append(htm);
        } else if (true === displayInPopup && true === $("." + $.w3mentorsmodal.element).hasClass('fixed-right')) {
            $("." + $.w3mentorsmodal.element).removeClass('fixed-right');
        } else if (false === displayInPopup && false === $("." + $.w3mentorsmodal.element).hasClass('fixed-right')) {
            $("." + $.w3mentorsmodal.element).addClass('fixed-right');
        }

        if (dialogClass != '' && !$("body ." + $.w3mentorsmodal.element + " .modal-dialog").hasClass(dialogClass)) {
            $("body ." + $.w3mentorsmodal.element + " .modal-dialog").removeClass( "modal-dialog-vertical-sm  modal-dialog-vertical-md modal-dialog-vertical-lg" ).addClass(dialogClass);
        }
    }

    function fillYKModalFromHref(href) {
        if (href.match(/#/)) {
            var url = window.location.href.split("#")[0];
            var target = href.replace(url, "");
            if (target === "#") {
                return;
            }
            $.w3mentorsmodal.reveal($(target).html());
        } else if (href.match($.w3mentorsmodal.settings.imageTypesRegexp)) {
            fillYKModalFromImage(href);
        } else {
            fillYKModalFromAjax(href);
        }
    }

    function fillYKModalFromImage(href) {
        var image = new Image();
        image.onload = function () {
            $.w3mentorsmodal.reveal('<div class="image"><img src="' + image.src + '" /></div>');
        };
        image.src = href;
    }

    function fillYKModalFromAjax(href) {
        $.w3mentorsmodal.jqxhr = $.get(href, function (data) {
            $.w3mentorsmodal.reveal(data);
        });
    }

    $(document).bind("close.w3mentorsmodal", function () {
        $.w3mentorsmodal.close();
    });

    $(document).on("hidden.bs.modal", "." + $.w3mentorsmodal.element, function () {
        $.w3mentorsmodal.close();
    });
})(jQuery);