var lectureId;
var videoSizeLbl;
var videoSize;
var errorMsg = "";
$(function () {
  generalForm = function () {
    fcom.ajax(
      fcom.makeUrl("Courses", "generalForm", [courseId]),
      "",
      function (res) {
        $("#pageContentJs").html(res);
        var id = $('textarea[name="course_details"]').attr("id");
        window["oEdit_" + id].disableFocusOnLoad = true;
        $('#pageContentJs input[name="course_title"]:first').focus();
        getCourseEligibility();
        fcom.setEditorLayout(siteLangId);
      }
    );
  };

  mediaForm = function (process = true) {
    fcom.ajax(
      fcom.makeUrl("Courses", "mediaForm", [courseId]),
      "",
      function (res) {
        $("#pageContentJs").html(res);
        getCourseEligibility();
      },
      { process: process }
    );
  };
  submitMedia = function () {
    if ($(".mediaCloseJs:visible").length < 2) {
      fcom.error(errorMsg);
      return false;
    }

    intendedLearnersForm();
  };
  intendedLearnersForm = function () {
    fcom.ajax(
      fcom.makeUrl("Courses", "intendedLearnersForm", [courseId]),
      "",
      function (res) {
        $("#pageContentJs").html(res);
        getCourseEligibility();
      }
    );
  };
  addFld = function (type) {
    $(".typesAreaJs" + type + " .typesListJs").append(
      $(".typesAreaJs" + type + " .typeFieldsJs:last")
        .clone()
        .find("input:text, input:hidden")
        .val("")
        .end()
    );
    var obj = $(".typesAreaJs" + type + " .typeFieldsJs:last");
    $(obj).find("a.sortHandlerJs").removeClass("sortHandlerJs");
    $(obj)
      .find("a.removeRespJs")
      .attr("onclick", "removeIntendedLearner(this, 0);")
      .show();
    $(obj)
      .find(".field-count")
      .attr("field-count", $(obj).find(".field-count").data("length"));
  };
  setupIntendedLearners = function (frm) {
    if (!$(frm).validate()) {
      return;
    }
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(
      fcom.makeUrl("Courses", "setupIntendedLearners"),
      data,
      function (res) {
        priceForm();
        getCourseEligibility();
      }
    );
  };
  updateIntendedOrder = function () {
    var order = [];
    $(".sortable_ids").each(function () {
      order.push($(this).val());
    });
    fcom.ajax(
      fcom.makeUrl("Courses", "updateIntendedOrder"),
      {
        order: order,
        course_id: courseId,
      },
      function (res) {
        intendedLearnersForm();
      }
    );
  };
  removeIntendedLearner = function (obj, id) {
    if (id > 0) {
      if (confirm(langLbl.confirmRemove)) {
        fcom.updateWithAjax(
          fcom.makeUrl("Courses", "deleteIntendedLearner", [id]),
          "",
          function (res) {
            $(obj).parents(".typeFieldsJs").remove();
          }
        );
      }
      getCourseEligibility();
    } else {
      $(obj).parents(".typeFieldsJs").remove();
    }
  };
  priceForm = function () {
    fcom.ajax(
      fcom.makeUrl("Courses", "priceForm", [courseId]),
      "",
      function (res) {
        $("#pageContentJs").html(res);
        updatePriceForm($('input[name="course_type"]:checked').val());
        getCourseEligibility();
      }
    );
  };
  updatePriceForm = function (type) {
    if (type == TYPE_FREE) {
      $('select[name="course_currency_id"], input[name="course_price"]')
        .attr("data-fatreq", '{"required":false}')
        .val("");
      $(".reqFldsJs").hide();
    } else {
      $('select[name="course_currency_id"], input[name="course_price"]').attr(
        "data-fatreq",
        '{"required":true}'
      );
      $(".reqFldsJs").show();
    }
  };
  setupPrice = function (frm) {
    if (!$(frm).validate()) {
      return;
    }
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(
      fcom.makeUrl("Courses", "setupPrice"),
      data,
      function (res) {
        curriculumForm();
        getCourseEligibility();
      }
    );
  };
  curriculumForm = function () {
    fcom.ajax(
      fcom.makeUrl("Courses", "curriculumForm", [courseId]),
      "",
      function (res) {
        $("#pageContentJs").html(res);
        searchSections();
        getCourseEligibility();
      }
    );
  };
  settingsForm = function () {
    fcom.ajax(
      fcom.makeUrl("Courses", "settingsForm", [courseId]),
      "",
      function (res) {
        $("#pageContentJs").html(res);
        getCourseEligibility();
      }
    );
  };
  setupSettings = function (frm) {
    if (!$(frm).validate()) {
      return;
    }
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(
      fcom.makeUrl("Courses", "setupSettings"),
      data,
      function (res) {
        settingsForm();
      }
    );
  };
  setup = function () {
    var frm = $("#frmCourses");
    if (!$(frm).validate()) {
      return;
    }

    var data = fcom.frmData(frm);
    fcom.updateWithAjax(fcom.makeUrl("Courses", "setup"), data, function (res) {
      $("#mainHeadingJs").text(res.title);
      var id = $('input[name="course_id"]').val();
      id = id == "" ? "/" + res.courseId : "";
      window.history.pushState("page", document.title, window.location + id);
      courseId = res.courseId;
      mediaForm();
      getCourseEligibility();
    });
  };
  triggerMediaClick = function () {
    $('input[name="course_image"]').trigger("click");
  };
  setupMedia = function () {
    var frm = $("#frmCourses")[0];
    var data = new FormData(frm);
    frm.reset();
    fcom.ajaxMultipart(
      fcom.makeUrl("Courses", "setupMedia"),
      data,
      function (res) {
        if (res.status == 1) {
          mediaForm(false);
          getCourseEligibility();
        }
      },
      { fOutMode: "json" }
    );
  };
  uploadVideo = function (_obj) {
    if (_obj.files[0] && _obj.files[0].size > videoSize) {
      fcom.error(videoSizeLbl);
      return;
    }
    var data = new FormData($("#frmCourses")[0]);
    $(".progress-div").addClass("course-progress-bar-js");
    fcom.ajaxMultipart(
      fcom.makeUrl("Courses", "setupVideo"),
      data,
      function (res) {
        if (res.status == 1) {
          $(".progress-div").removeClass("course-progress-bar-js");
          mediaForm();
          getCourseEligibility();
        }
      },
      { fOutMode: "json" }
    );
  };
  removeMedia = function (type = "") {
    if (confirm(langLbl.confirmRemove)) {
      fcom.updateWithAjax(
        fcom.makeUrl("Courses", "removeMedia", [courseId]),
        { type },
        function (res) {
          mediaForm();
          getCourseEligibility();
        }
      );
    }
  };
  generalForm();
  getCourseEligibility = function () {
    fcom.updateWithAjax(
      fcom.makeUrl("Courses", "getEligibilityStatus", [courseId]),
      "",
      function (res) {
        setCompletedStatus(res.criteria);
      },
      { process: false }
    );
  };
  setCompletedStatus = function (criteria) {
    $(
      ".general-info-js, .intended-learner-js, .course-price-js, .curriculum-js, .course-setting-js"
    )
      .removeClass("is-completed")
      .addClass("is-progress");
    $(".btnApprovalJs").addClass("d-none");
    if (
      criteria.course_lang == 1 &&
      criteria.course_image == 1 &&
      criteria.course_preview_video == 1 &&
      criteria.course_cate == 1 &&
      criteria.course_subcate == 1 &&
      criteria.course_clang == 1
    ) {
      $(".general-info-js").removeClass("is-progress").addClass("is-completed");
    }
    if (criteria.courses_intended_learners == 1) {
      $(".intended-learner-js")
        .removeClass("is-progress")
        .addClass("is-completed");
    }
    if (criteria.course_price == 1 && criteria.course_currency_id == 1) {
      $(".course-price-js").removeClass("is-progress").addClass("is-completed");
    }
    if (criteria.course_sections == 1 && criteria.course_lectures == 1) {
      $(".curriculum-js").removeClass("is-progress").addClass("is-completed");
    }
    if (criteria.course_tags == 1 && criteria.course_quiz == 1) {
      $(".course-setting-js")
        .removeClass("is-progress")
        .addClass("is-completed");
    }
    if (criteria.course_is_eligible == true) {
      $(".btnApprovalJs").removeClass("d-none");
    }
  };
  getCourseEligibility();

  /* Sections [ */
  sectionForm = function (id) {
    var section_order = $("#courseSectionOrderJs").val();
    fcom.ajax(
      fcom.makeUrl("Sections", "form", [courseId]),
      { section_order, id },
      function (res) {
        $(".message-display").remove();
        if (id > 0) {
          $("#sectionId" + id + " .sectionCardJs").hide();
          $("#sectionId" + id + " .sectionEditCardJs").html(res);
        } else {
          $("#courseSectionOrderJs").val(parseInt(section_order) + 1);
          $("#sectionFormAreaJs").append(res);
          $("body, html").animate(
            { scrollTop: $("#sectionForm" + section_order + "1").offset().top },
            1000
          );
        }
      }
    );
  };
  setupSection = function (frm) {
    if (!$(frm).validate()) {
      return;
    }
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(
      fcom.makeUrl("Sections", "setup"),
      data,
      function (res) {
        $(frm).parents(".card-panel").remove();
        searchSections();
        getCourseEligibility();
      }
    );
  };
  updateSectionOrder = function () {
    var order = [""];
    $("#sectionAreaJs .card-panel").each(function () {
      order.push($(this).data("id"));
    });
    fcom.ajax(
      fcom.makeUrl("Sections", "updateOrder", [courseId]),
      {
        order: order,
        course_id: courseId,
      },
      function (res) {
        searchSections();
      }
    );
  };
  removeSection = function (id) {
    if (confirm(langLbl.confirmRemove)) {
      fcom.ajax(fcom.makeUrl("Sections", "delete", [id]), "", function (res) {
        searchSections();
        getCourseEligibility();
      });
    }
  };
  cancelSection = function (id) {
    $("#sectionForm" + id).remove();
    if ($("#sectionAreaJs .card-panel").length < 1) {
      searchSections();
    }
  };
  searchSections = function (sectionId) {
    fcom.ajax(
      fcom.makeUrl("Sections", "search", [courseId]),
      { section_id: sectionId },
      function (res) {
        if (sectionId > 0) {
          $("#sectionId" + sectionId).html(res);
          return;
        } else {
          $("#sectionAreaJs").html(res);
        }
      }
    );
  };
  /* ] */

  /* Lectures [ */
  lectureForm = function (sectionId, lectureId = 0) {
    var lectureOrder = $("#lectureOrderJs").val();
    fcom.ajax(
      fcom.makeUrl("Lectures", "form", [sectionId]),
      {
        lecture_id: lectureId,
        lecture_order: lectureOrder,
        course_id: courseId,
      },
      function (res) {
        /* for edit, append form to the current lecture area */
        if ($("#sectionLectures" + lectureId).length > 0) {
          $("#sectionLectures" + lectureId)
            .replaceWith(res)
            .show();
        } else {
          /* if new form added, append it to the last */
          $("#sectionId" + sectionId + " .lecturesListJs")
            .append(res)
            .show();
          $("#lectureOrderJs").val(parseInt(lectureOrder) + 1);
        }
        var id = $(res).find('textarea[name="lecture_details"]').attr("id");
        window["oEdit_" + id].disableFocusOnLoad = true;
        fcom.setEditorLayout(siteLangId);
      }
    );
    getCourseEligibility();
  };
  $(document).on("submit", "form[name=frmLecture]", function (event) {
    var frm = $(this);
    if (!$(frm).validate()) {
      return;
    }
    fcom.updateWithAjax(
      fcom.makeUrl("Lectures", "setup"),
      fcom.frmData(frm),
      function (res) {
        $(frm)
          .parents(".card-group-js")
          .attr("id", "sectionLectures" + res.lectureId);
        lectureMediaForm(res.lectureId);
        getCourseEligibility();
      }
    );
    return false;
  });
  updateLectureOrder = function () {
    var order = [""];
    $("#sectionAreaJs .lecturePanelJs").each(function () {
      order.push($(this).data("id"));
    });
    fcom.ajax(
      fcom.makeUrl("Lectures", "updateOrder"),
      {
        order: order,
        course_id: courseId,
      },
      function (res) {
        searchSections();
      }
    );
  };
  removeLecture = function (sectionId, id) {
    if (confirm(langLbl.confirmRemove)) {
      fcom.ajax(fcom.makeUrl("Lectures", "delete", [id]), "", function (res) {
        searchSections(sectionId);
        getCourseEligibility();
      });
    }
  };
  cancelLecture = function (lectureId) {
    fcom.ajax(
      fcom.makeUrl("Lectures", "search", [lectureId]),
      "",
      function (res) {
        var ele = $("#sectionLectures" + lectureId);
        $("#sectionLectures" + lectureId).before(res);
        $(ele).remove();
      }
    );
  };
  removeLectureForm = function (sectionId, id) {
    $(id).remove();
    if (
      $("#sectionId" + sectionId + " .lecturesListJs .card-group-js").length ==
      0
    ) {
      $("#sectionId" + sectionId + " .lecturesListJs").hide();
    }
  };
  lectureMediaForm = function (lectureId) {
    fcom.ajax(
      fcom.makeUrl("Lectures", "mediaForm", [lectureId]),
      "",
      function (res) {
        $("#sectionLectures" + lectureId).html(res);
      }
    );
    getCourseEligibility();
  };
  setupLectureMedia = function (frm) {
    if (!$("#" + frm).validate()) {
      return;
    }
    var _obj = $("#" + frm).find('input[name="lecsrc_link"]')[0];
    if (_obj.files[0] && _obj.files[0].size > videoSize) {
      fcom.error(videoSizeLbl);
      return;
    }
    var data = new FormData($("#" + frm)[0]);
    $("#" + frm)
      .find(".progress-div")
      .addClass("course-progress-bar-js");
    fcom.ajaxMultipart(
      fcom.makeUrl("Lectures", "setupMedia"),
      data,
      function (res) {
        lectureResourceForm(res.lectureId);
        $("#" + frm)
          .find(".progress-div")
          .removeClass("course-progress-bar-js");
      },
      { fOutMode: "json" }
    );
  };
  removeLectureVideo = function (id) {
    if (confirm(langLbl.confirmRemove)) {
      var lecture_id = $('input[name="lecsrc_lecture_id"]').val();
      fcom.updateWithAjax(
        fcom.makeUrl("Lectures", "removeMedia"),
        { id, lecture_id, courseId },
        function (res) {
          lectureMediaForm(lecture_id);
        }
      );
    }
  };
  lectureResourceForm = function (lectureId, process = true) {
    fcom.ajax(
      fcom.makeUrl("LectureResources", "index", [lectureId]),
      "",
      function (res) {
        $("#sectionLectures" + lectureId).html(res);
      },
      { process: process }
    );
    getCourseEligibility();
  };
  setupLectureResrc = function (frm) {
    if (!$(frm).validate()) {
      return;
    }
    var data = new FormData(frm);
    fcom.ajaxMultipart(
      fcom.makeUrl("LectureResources", "setup"),
      data,
      function (res) {
        lectureResourceForm(res.lectureId, false);
        $.w3mentorsmodal.close();
      },
      { fOutMode: "json" }
    );
  };
  uploadResource = function (id) {
    var frm = $("#" + id)[0];
    setupLectureResrc(frm);
  };
  removeLectureResrc = function (id, lectureId) {
    if (confirm(langLbl.confirmRemove)) {
      fcom.ajax(
        fcom.makeUrl("LectureResources", "delete", [id]),
        "",
        function (res) {
          lectureResourceForm(lectureId);
        }
      );
    }
  };
  getResources = function (lecId) {
    fcom.ajax(
      fcom.makeUrl("LectureResources", "resources", [lecId]),
      "",
      function (res) {
        $.w3mentorsmodal(res, { size: "modal-lg", addClass: "p-0" });
        lectureId = lecId;
        searchResources(document.frmResourceSearch);
      }
    );
  };
  searchResources = function (frm, page = 1) {
    document.frmResourceSearch.page.value = page;
    fcom.updateWithAjax(
      fcom.makeUrl("LectureResources", "search", [lectureId]),
      fcom.frmData(frm),
      function (res) {
        if (page > 1) {
          $("#listingJs").append(res.html);
        } else {
          $("#listingJs").html(res.html);
        }
        if (res.loadMore == 1) {
          $(".rvwLoadMoreJs a").data("page", res.nextPage);
          $(".rvwLoadMoreJs").show();
        } else {
          $(".rvwLoadMoreJs").hide();
        }
      }
    );
  };
  resourcePaging = function (_obj) {
    searchResources(document.frmResourceSearch, $(_obj).data("page"));
  };
  /* ] */
  submitForReview = function () {
    if (confirm(langLbl.confirmCourseSubmission)) {
      fcom.updateWithAjax(
        fcom.makeUrl("Courses", "submitForApproval", [courseId]),
        "",
        function (res) {
          window.location = fcom.makeUrl("Courses");
        }
      );
    }
  };
  submitUpdates = function () {
    if (confirm(langLbl.confirmCourseEditSubmission)) {
      fcom.updateWithAjax(
        fcom.makeUrl("Courses", "submitUpdates", [courseId]),
        "",
        function (res) {
          window.location = fcom.makeUrl("Courses");
        }
      );
    }
  };
  /*updateProgress = function(perc) {
        $('.progress-bar-info').text(perc + '%');
        $('.progress-bar').val(perc);
    }; */
  getCertificates = function () {
    if ($("input[name='course_certificate']:checked").val() == 1) {
      $(".certTypeJs").show();
      $(
        "select[name='course_certificate_type'], input[name='course_quilin_id']"
      ).attr("data-fatreq", '{ "required": true }');
    } else {
      $(".certTypeJs, .quizSectionJs").hide();
      $(
        "select[name='course_certificate_type'], input[name='course_quilin_id']"
      )
        .attr("data-fatreq", '{ "required": false }')
        .val("");
    }
  };
  showQuizSection = function (val) {
    if (val == 3) {
      $(".quizSectionJs").show();
    } else {
      $(".quizSectionJs").hide();
    }
  };
  removeAttachedQuiz = function (quizLinkId) {
    if (!confirm(langLbl.confirmRemove)) {
      return;
    }
    if ($(".quizSectionJs").hasClass("hasQuiz")) {
      fcom.updateWithAjax(
        fcom.makeUrl("Courses", "removeQuiz"),
        { courseId, quizLinkId },
        function (res) {
          $(".attachedQuizJs").hide();
          $('input[name="course_quilin_id"]').val("");
          $(".quizSectionJs").removeClass("hasQuiz");
          getCourseEligibility();
        }
      );
    } else {
      $(".attachedQuizJs").hide();
      $('input[name="course_quilin_id"]').val("");
    }
  };
});
$(document).ready(function () {
  $("body").on("input", 'input[type="text"], textarea', function () {
    var ele = $(this).parent();
    if ($(ele).hasClass("field-count")) {
      var max = parseInt($(ele).data("length"));
      var strLen = parseInt($(this).val().length);
      var limit = max - strLen;
      if (limit < 0) {
        $(this).val($(this).val().substring(0, max));
        $(ele).attr("field-count", 0);
        return;
      }
      $(ele).attr("field-count", limit);
    }
  });
});
getSubCategories = function (id, selectedId = 0) {
  fcom.ajax(
    fcom.makeUrl("Courses", "getSubcategories", [id, selectedId]),
    "",
    function (res) {
      $("#subCategories").html(res);
    }
  );
};
