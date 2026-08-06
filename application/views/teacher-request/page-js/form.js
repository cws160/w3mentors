/* global fcom, langLbl */

var isRuningTeacherQualificationFormAjax = false;
(function ($) {
  resubmit = function () {
    fcom.process();
    fcom.ajax(
      fcom.makeUrl("TeacherRequest", "formStep1", []),
      "resubmit=1",
      function (res) {
        $("#main-container").html(res);
      }
    );
  };
  getform = function (step) {
    fcom.ajax(
      fcom.makeUrl("TeacherRequest", "formStep" + step, []),
      "",
      function (res) {
        $("#main-container").html(res);
      }
    );
  };
  setupStep1 = function (frm, loadNext = false) {
    if (!$(frm).validate()) {
      return;
    }
    fcom.process();
    var data = new FormData(frm);
    fcom.ajaxMultipart(
      fcom.makeUrl("TeacherRequest", "setupStep1"),
      data,
      function (response) {
        loadNext ? getform(response.step) : getform(1);
      },
      { fOutMode: "json" }
    );
  };
  setupStep2 = function (frm, loadNext = false) {
    if (!$(frm).validate()) {
      return;
    }
    fcom.process();
    fcom.updateWithAjax(
      fcom.makeUrl("TeacherRequest", "setupStep2", []),
      fcom.frmData(frm),
      function (res) {
        if (loadNext) {
          getform(res.step);
        }
      }
    );
  };
  setupStep3 = function (frm, loadNext = false) {
    if (!$(frm).validate()) {
      return;
    }
    fcom.process();
    fcom.updateWithAjax(
      fcom.makeUrl("TeacherRequest", "setupStep3", []),
      fcom.frmData(frm),
      function (res) {
        if (loadNext) {
          getform(res.step);
        }
      }
    );
  };
  setupStep4 = function (frm, loadNext = false) {
    if (!$(frm).validate()) {
      return;
    }
    fcom.process();
    fcom.updateWithAjax(
      fcom.makeUrl("TeacherRequest", "setupStep4", []),
      fcom.frmData(frm),
      function (res) {
        if (loadNext) {
          getform(res.step);
        }
      }
    );
  };
  validateVideolink = function (field) {
    $(document.frmFormStep2).validate();
    var url = field.value;
    if (!url && url == "") {
      return false;
    }
    var regExp =
      /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
    var matches = url.match(regExp);
    if (matches && matches[2].length == 11) {
      valideUrl = "https://www.youtube.com/embed/";
      valideUrl += matches[2];
      $(field).val(valideUrl);
      $(document.frmFormStep2).validate();
      return matches[1];
    }
    $(field).val("");
    return false;
  };
  setPhoneNumberMask = function () {
    let placeholder = $("#utrequest_phone_number").attr("placeholder");
    if (placeholder) {
      placeholder = placeholder.replace(/[0-9.]/g, "9");
      $("#utrequest_phone_number").inputmask({ mask: placeholder });
    }
  };
  teacherQualificationForm = function (uqualification_id) {
    fcom.process();
    fcom.ajax(
      fcom.makeUrl("TeacherRequest", "teacherQualificationForm", []),
      "uqualification_id=" + uqualification_id,
      function (res) {
        $.w3mentorsmodal(res, { size: "modal-lg" });
      }
    );
  };
  setupTeacherQualification = function (frm) {
    if (!$(frm).validate()) {
      return;
    }
    $(frm).find(":submit").attr("disabled", "disabled");
    setTimeout(function () {
      $(frm).find(":submit").removeAttr("disabled");
    }, 5000);
    fcom.process();
    var data = new FormData(frm);
    fcom.ajaxMultipart(
      fcom.makeUrl("TeacherRequest", "setupTeacherQualification"),
      data,
      function (response) {
        searchTeacherQualification();
      },
      { fOutMode: "json" }
    );
  };
  searchTeacherQualification = function () {
    fcom.process();
    fcom.ajax(
      fcom.makeUrl("TeacherRequest", "searchTeacherQualification"),
      "",
      function (res) {
        $.w3mentorsmodal.close();
        $("#qualification-container").html(res);
      }
    );
  };
  deleteTeacherQualification = function (uqualification_id) {
    if (!confirm(langLbl.confirmRemove)) {
      return;
    }
    fcom.updateWithAjax(
      fcom.makeUrl("TeacherRequest", "deleteTeacherQualification"),
      "&uqualification_id=" + uqualification_id,
      function () {
        searchTeacherQualification();
        $.w3mentorsmodal.close();
      }
    );
  };
  changeProficiency = function (obj, langId) {
    langId = parseInt(langId);
    if (langId <= 0) {
      return;
    }
    let value = obj.value;
    slanguageSection = ".slanguage-" + langId;
    slanguageCheckbox = ".slanguage-checkbox-" + langId;
    if (value == "") {
      $(slanguageSection).find(".badge-js").remove();
      $(slanguageSection).removeClass("is-selected");
      $(slanguageCheckbox).prop("checked", false);
    } else {
      $(slanguageSection).addClass("is-selected");
      $(slanguageCheckbox).prop("checked", true);
      $(slanguageSection).find(".badge-js").remove();
      $(slanguageSection)
        .find(".selection__trigger-label")
        .append(
          '<span class="badge color-secondary badge-js  badge--round badge--small m-0">' +
            obj.selectedOptions[0].innerHTML +
            "</span>"
        );
    }
  };
  popupImage = function (input) {
    wid = $(window).width();
    wid = wid > 767 ? 500 : 280;
    if (input.files && input.files[0]) {
      // Allowing file type
      var allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif)$/i;

      if (!allowedExtensions.exec(input.value)) {
        input.value = "";
        fcom.error("invalid extension!");
        return false;
      }
      var reader = new FileReader();
      reader.onload = function (e) {
        $.w3mentorsmodal(
          '<div class="modal-header"><h5>' +
            langLbl.profileImageHeading +
            '</h5><button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button></div><div class="modal-body"><div class="img-container "><img alt="Picture" src="' +
            e.target.result +
            '" class="img_responsive" id="new-img" /></div><div class="img-description"><div class="rotator-info">' +
            lblCroperInfoText +
            '</div><div class="-align-center rotator-actions"><a href="javascript:void(0)" class="btn btn--primary btn--sm" title="' +
            $("#rotate_left").val() +
            '" data-option="-90" data-method="rotate">' +
            $("#rotate_left").val() +
            '</a>&nbsp;<a onclick="sumbmitProfileImage();" href="javascript:void(0)" class="btn btn--secondary btn--sm">' +
            $("#update_profile_img").val() +
            '</a>&nbsp;<a href="javascript:void(0)" class="btn btn--primary btn--sm rotate-right" title="' +
            $("#rotate_right").val() +
            '" data-option="90" data-method="rotate">' +
            $("#rotate_right").val() +
            "</a></div></div></div>",
          { size: "modal-lg" }
        );
        $("#new-img").width(wid);
        setTimeout(function () {
          cropImage($("#new-img"));
        }, 300);
      };
      reader.readAsDataURL(input.files[0]);
    }
    input.value = "";
  };
  sumbmitProfileImage = function () {
    if (cropObj) {
      /* Add blob and file name */
      $image.cropper("getCroppedCanvas").toBlob(
        function (blob) {
          var formData = new FormData();
          formData.append("user_profile_image", blob, "file.jpg");
          formData.append("fIsAjax", 1);
          fcom.process();
          fcom.ajaxMultipart(
            fcom.makeUrl("TeacherRequest", "setupProfileImage"),
            formData,
            function (response) {
              $image.cropper("destroy");
              var image =
                '<img id="user-profile-pic--js" src="' + response.file + '">';
              $("#avtar-js").html(image);
              $.w3mentorsmodal.close();
              getform(2);
            },
            { fOutMode: "json" }
          );
        },
        "image/jpeg",
        0.9
      );
    }
  };
  var $image = null;
  var cropObj = null;
  cropImage = function (obj) {
    if ($image) {
      $image.cropper("destroy");
    }
    $image = obj;
    cropObj = $image.cropper({
      aspectRatio: 3 / 4,
      guides: true,
      highlight: true,
      dragCrop: true,
      cropBoxMovable: true,
      cropBoxResizable: true,
      rotatable: true,
      responsive: true,
      built: function () {
        $(this).cropper("zoom", 0.5);
      },
    });
  };
  $(document).on("click", "[data-method]", function () {
    var data = $(this).data();
    if (data.method) {
      result = $image.cropper(data.method, data.option);
    }
  });
})(jQuery);
