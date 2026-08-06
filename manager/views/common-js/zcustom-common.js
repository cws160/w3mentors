/* global oUtil, langLbl, x, fcom, weekDayNames, monthNames, layoutDirection, SITE_ROOT_URL, siteConstants */
function isJson(str) {
  try {
    JSON.parse(str);
  } catch (e) {
    return false;
  }
  return true;
}
(function ($) {
  var screenHeight = $(window).height() - 100;
  window.onresize = function (event) {
    var screenHeight = $(window).height() - 100;
  };
  var dayShortNames = weekDayNames.shortName.slice(0);
  var lastValue = dayShortNames[6];
  dayShortNames.pop();
  dayShortNames.unshift(lastValue);
  defaultsValue = {
    monthNames: monthNames.longName,
    monthNamesShort: monthNames.shortName,
    dayNamesMin: dayShortNames,
    dayNamesShort: dayShortNames,
    currentText: langLbl.today,
    closeText: langLbl.done,
    prevText: langLbl.prev,
    nextText: langLbl.next,
    isRTL: layoutDirection == "rtl",
  };
  $.datepicker.regional[""] = $.extend(true, {}, defaultsValue);
  $.datepicker.setDefaults($.datepicker.regional[""]);
  $.extend(fcom, {
    resetEditorInstance: function () {
      if (typeof oUtil != "undefined") {
        var editors = oUtil.arrEditor;
        for (x in editors) {
          eval("delete window." + editors[x]);
        }
        oUtil.arrEditor = [];
      }
    },
    setEditorLayout: function (lang_id) {
      setTimeout(function () {
        var editors = oUtil.arrEditor;
        layout = langLbl["language" + lang_id];
        for (x in editors) {
          $("#idContent" + editors[x])
            .contents()
            .find("body")
            .css("direction", layout);
        }
        $("table").find(".istoolbar_container").attr("dir", layout);
      }, 100);
    },
    getLoader: function () {
      return '<div class="circularLoader"><svg class="circular" height="30" width="30"><circle class="path" cx="25" cy="25.2" r="19.9" fill="none" stroke-width="6" stroke-miterlimit="10"></circle> </svg> </div>';
    },
    updatePopupContent: function (t) {
      $.w3mentorsmodal(t);
    },
  });
  $(document).bind("close.sysmsgcontent", function () {
    $(".alert").fadeOut();
  });
  if ($.datepicker) {
    var old_goToToday = $.datepicker._gotoToday;
    $.datepicker._gotoToday = function (id) {
      old_goToToday.call(this, id);
      this._selectDate(id);
      $(id).blur();
      return;
    };
  }
  refreshCaptcha = function (elem) {
    $(elem).attr(
      "src",
      siteConstants.webroot + "helper/captcha?sid=" + Math.random()
    );
  };
  clearCache = function () {
    fcom.ajax(fcom.makeUrl("Home", "clearCache"), "", function (t) {
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    });
  };
  SelectText = function (element) {
    var doc = document,
      text = doc.getElementById(element),
      range,
      selection;
    if (doc.body.createTextRange) {
      range = document.body.createTextRange();
      range.moveToElementText(text);
      range.select();
    } else if (window.getSelection) {
      selection = window.getSelection();
      range = document.createRange();
      range.selectNodeContents(text);
      selection.removeAllRanges();
      selection.addRange(range);
    }
  };
  getSlugUrl = function (obj, str, extra, pos) {
    if (pos == undefined) pos = "pre";
    var str = str
      .toString()
      .toLowerCase()
      .replace(/\s+/g, "-") // Replace spaces with -
      .replace(/[^\w\-\/]+/g, "") // Remove all non-word chars
      .replace(/\-\-+/g, "-") // Replace multiple - with single -
      .replace(/^-+/, "") // Trim - from start of text
      .replace(/-+$/, "");
    if (extra && pos == "pre") {
      str = extra + "/" + str;
    }
    if (extra && pos == "post") {
      str = str + "/" + extra;
    }
    $(obj)
      .next()
      .html(SITE_URL + str);
  };
  redirectfunc = function (url, id, nid) {
    if (nid > 0) {
      markRead(nid, url, id);
    } else {
      var form = '<input type="hidden" name="id" value="' + id + '">';
      $('<form action="' + url + '" method="POST">' + form + "</form>")
        .appendTo($(document.body))
        .submit();
    }
  };
  markRead = function (nid, url, id) {
    if (nid.length < 1) {
      return false;
    }
    var data = "record_ids=" + nid + "&status=" + 1 + "&markread=1";
    fcom.updateWithAjax(
      fcom.makeUrl("Notifications", "changeStatus"),
      data,
      function (res) {
        var form = '<input type="hidden" name="id" value="' + id + '">';
        $('<form action="' + url + '" method="POST">' + form + "</form>")
          .appendTo($(document.body))
          .submit();
      }
    );
  };
  generateSitemap = function () {
    fcom.updateWithAjax(
      fcom.makeUrl("Sitemap", "generate"),
      "",
      function (res) {}
    );
  };
  logout = function () {
    fcom.updateWithAjax(fcom.makeUrl("Profile", "logout"), "", function (res) {
      setTimeout(function () {
        window.location.href = fcom.makeUrl("AdminGuest", "loginForm");
      }, 1000);
    });
  };
  exportCSV = function () {
    if (typeof srchForm != "undefined") {
      if (!$(srchForm).validate()) {
        return;
      }
      var data = fcom.frmData(srchForm) + "&export=1";
    } else {
      var data = "export=1";
    }
    fcom.updateWithAjax(
      fcom.makeUrl(CONTROLLER, "export"),
      data,
      function (res) {
        window.location.href = fcom.makeUrl("Exports", "download", [
          res.exportId,
        ]);
      }
    );
  };
})(jQuery);
function getSlickSliderSettings(slidesToShow, slidesToScroll, layoutDirection) {
  slidesToShow =
    typeof slidesToShow != "undefined" ? parseInt(slidesToShow) : 4;
  slidesToScroll =
    typeof slidesToScroll != "undefined" ? parseInt(slidesToScroll) : 1;
  layoutDirection =
    typeof layoutDirection != "undefined" ? layoutDirection : "ltr";
  if (layoutDirection == "rtl") {
    return {
      slidesToShow: slidesToShow,
      slidesToScroll: slidesToScroll,
      infinite: false,
      arrows: true,
      rtl: true,
      prevArrow:
        '<a data-role="none" class="slick-prev" aria-label="previous"></a>',
      nextArrow:
        '<a data-role="none" class="slick-next" aria-label="next"></a>',
      responsive: [
        { breakpoint: 1050, settings: { slidesToShow: slidesToShow - 1 } },
        { breakpoint: 990, settings: { slidesToShow: 3 } },
        { breakpoint: 767, settings: { slidesToShow: 2 } },
        { breakpoint: 400, settings: { slidesToShow: 1 } },
      ],
    };
  } else {
    return {
      slidesToShow: slidesToShow,
      slidesToScroll: slidesToScroll,
      infinite: false,
      arrows: true,
      prevArrow:
        '<a data-role="none" class="slick-prev" aria-label="previous"></a>',
      nextArrow:
        '<a data-role="none" class="slick-next" aria-label="next"></a>',
      responsive: [
        { breakpoint: 1050, settings: { slidesToShow: slidesToShow - 1 } },
        { breakpoint: 990, settings: { slidesToShow: 3 } },
        { breakpoint: 767, settings: { slidesToShow: 2 } },
        { breakpoint: 400, settings: { slidesToShow: 1 } },
      ],
    };
  }
}
(function () {
  Slugify = function (str, str_val_id, is_slugify) {
    var str = str
      .toString()
      .toLowerCase()
      .replace(/\s+/g, "-") // Replace spaces with -
      .replace(/[^\w\-]+/g, "") // Remove all non-word chars
      .replace(/\-\-+/g, "-") // Replace multiple - with single -
      .replace(/^-+/, "") // Trim - from start of text
      .replace(/-+$/, "");
    if ($("#" + is_slugify).val() == 0) $("#" + str_val_id).val(str);
  };
  callChart = function (dv, $labels, $series, $position) {
    new Chartist.Bar(
      "#" + dv,
      {
        labels: $labels,
        series: [$series],
      },
      {
        stackBars: true,
        axisY: {
          position: $position,
          labelInterpolationFnc: function (value) {
            return value;
          },
        },
        plugins: [Chartist.plugins.tooltip()],
      }
    ).on("draw", function (data) {
      if (data.type === "bar") {
        data.element.attr({
          style: "stroke-width: 25px",
        });
      }
    });
  };
  escapeHtml = function (text) {
    var map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  };
  validateYoutubelink = function (field) {
    let frm = field.form;
    let url = field.value.trim();
    if (url == "") {
      return false;
    }
    let regExp =
      /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
    let matches = url.match(regExp);
    if (matches && matches[2].length == 11) {
      let validUrl = "https://www.youtube.com/embed/";
      validUrl += matches[2];
      $(field).val(validUrl);
    } else {
      $(field).val("");
    }
    $(frm).validate();
  };

  validateLink = function (field) {
    let url = field.value.trim();
    if (url == "") {
      return false;
    }
    let regExp =
      /^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:[/?#]\S*)?$/;
    let matches = url.match(regExp);
    if (matches) {
      $(field).val(url);
    } else {
      fcom.error(langLbl.enterValidUrl);
      $(field).val("");
    }
  };
  resetIframe = function (frame) {
    setTimeout(() => {
      var height = $(frame).contents().height();
      $(frame).css({ height: height + "px" });
      $(frame)
        .parent(".iframe-content")
        .css("height", height + "px");
    }, 100);
  };
  translateAndAutoFill = function (tableName, recordId, toLangId) {
    let data = {
      tableName: tableName,
      recordId: recordId,
      toLangId: toLangId,
    };
    fcom.updateWithAjax(
      fcom.makeUrl("AdminBase", "translateAndAutoFill"),
      data,
      function (res) {
        if (res.fields && Object.keys(res.fields).length > 0) {
          $.each(res.fields, function (langId, fieldData) {
            if (res.table && res.table == "tbl_certificate_templates") {
              $.each(fieldData, function (name, value) {
                $("." + name + "-js").text(value);
              });
            } else {
              $.each(fieldData, function (name, value) {
                let $this = $("[name=" + name + "]");
                $this.val(value);
                if (
                  $this.is("textarea") &&
                  typeof oUtil !== "undefined" &&
                  oUtil.arrEditor.length > 0
                ) {
                  let editors = oUtil.arrEditor;
                  let id = $this.attr("id");
                  for (x in editors) {
                    if (editors[x] == "oEdit_" + id) {
                      var obj = eval("window." + editors[x]);
                      obj.putHTML(value);
                      var layout = langLbl["language" + toLangId];
                      $("#idContent" + editors[x])
                        .contents()
                        .find("body")
                        .css("direction", layout);
                    }
                  }
                }
              });
            }
          });
        }
      }
    );
  };
})();

$(document).on("click", ".sidebarOpenerBtnJs", function () {
  if ($(this).hasClass("active")) {
    // $.cookie('adminSidebar', 0, { expires: 30, path: siteConstants.rooturl });
    $("body").attr("data-sidebar-minimize", "on");
    $(this).removeClass("active");
    $(this).attr("title", langLbl.clickToExpand);
  } else {
    // $.cookie('adminSidebar', 1, { expires: 30, path: siteConstants.rooturl });
    $("body").attr("data-sidebar-minimize", "off");
    $(this).addClass("active");
    $(this).attr("title", langLbl.clickToHide);
  }
  $("#sidebar").addClass("animating");
  setInterval(function () {
    $("#sidebar").removeClass("animating");
  }, 2000);
});

/* Active Sidebar Link. */
$(document).ready(function () {
    var excludeUrls = ['rating-reviews/index', 'preferences/index', 'reported-issues/escalated', 'categories/quiz'];
    var uri = window.location.pathname;
    var isExclude = false;
    excludeUrls.forEach((url) => {
        if (uri.includes(url)) {
            isExclude = true;
            uri = ($('.breadcrumbJs').length > 0) ? $('.breadcrumbJs').attr('data-menu') : uri;
            return;
        }
    });


  if (isExclude) {
    uri.replace(/^(\/(?:[^/]+\/){2}).*$/, '$1');
  }
  if (uri.charAt(0) == "/") uri = uri.substring(1);
  if (uri.charAt(uri.length - 1) == "/") uri = uri.substring(0, uri.length - 1);
  uri.replace(/^\/|\/$/g, "");
  if (!isExclude) {
    uri = uri.split("/");
    if (SITE_ROOT_FRONT_URL != "/") {
      uri = uri[0] + "/" + uri[1] + "/" + uri[2];
    } else {
      uri = uri[0] + "/" + uri[1];
    }
  }

  $(".sidebarMenuJs .navLinkJs").each(function () {
    var attr = $(this).attr("href");
    var href = "";
    if (typeof attr !== "undefined" && attr !== false) {
      href = attr.replace(/^\/|\/$/g, "");
    }
    if (uri == href) {
      markNavActive($(this));
    }
  });
  $(".navLinkJs.active:not(.noCollapseJs)")
    .closest("ul")
    .addClass("show")
    .siblings(".menuLinkJs")
    .addClass("active")
    .removeClass("collapsed");
  $(".tabs-nav-js li a").click(function () {
    $(".tabs-nav-js li a").removeClass("active");
    $(this).addClass("active");
  });
});

markNavActive = function (ele) {
  if (!ele.hasClass("active")) {
    ele.addClass("active");
  }
  var menuLink = ele.parents("li").find(".menuLinkJs");
  menuLink.addClass("active").removeClass("collapsed");
  var target = menuLink.data("bsTarget");
  $(target).addClass("show");
};

$(document).ready(function () {
  setTimeout(myGreeting, 5000);
  function myGreeting() {
    /* Bind bootstrap tooltip with ajax elements. */
    $('[data-bs-toggle="tooltip"]')
      .tooltip({
        trigger: "hover",
      })
      .on("click", function () {
        setTimeout(() => {
          $(this).tooltip("hide");
        }, 100);
      });
  }
});

$(document).ready(function () {
  function updateAlertPosition() {
    let headerHeight = $(".main-header").height();
    $(".page-alert").css("top", headerHeight);
  }

  // Initial call to set the position
  updateAlertPosition();

  // Update position when the window is resized
  $(window).on("resize", function () {
    updateAlertPosition();
  });

  // Optionally, update position if the header height changes dynamically
  new ResizeObserver(updateAlertPosition).observe(
    document.querySelector(".main-header")
  );
});
