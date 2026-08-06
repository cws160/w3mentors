/* global confFrontEndUrl, fcom, forum */

$(function () {
  var tagsDv = "#question-tags";
  var allTags = [];

  processOldTags = function (oldTags) {
    $.each(oldTags, function (index, item) {
      allTags.push(parseInt(item.ftag_id));
      addTagInSelectedList(item.ftag_name, item.ftag_id);
    });
    updateSelectedTagIds();
  };

  addTagInSelectedList = function (itemName, itemValue) {
    var htm = getTagItemHtml(itemName, itemValue);
    $(tagsDv).append(htm);
    return;
  };

  getTagItemHtml = function (itemName, itemValue) {
    return (
      '<a class="tags__item badge badge--curve" data-tag_id="' +
      itemValue +
      '" id="tag_' +
      itemValue +
      '">' +
      itemName +
      '<svg onclick="removeTag(' +
      itemValue +
      ')" data-type="new" class="icon icon--cancel icon--small ms-2"><use xlink:href="' +
      confFrontEndUrl +
      'images/forum/sprite.svg#cancel"></use></svg></div>'
    );
  };

  updateSelectedTagIds = function () {
    $("#fque_sel_tags").val(allTags.toString());
  };

  selectTag = function (title, value) {
    $("input[name='fque_tags']").val("");
    if ($.inArray(value, allTags) >= 0) {
      return;
    }
    allTags.push(parseInt(value));
    addTagInSelectedList(title, value);
    updateSelectedTagIds();
  };

  removeTag = function (tagId) {
    allTags.splice($.inArray(tagId, allTags), 1);
    updateSelectedTagIds();
    $("#tag_" + tagId).remove();
  };

  setup = function (frm) {
    forum.validateTxtLimit(frm.fque_title, true);
    if (!$(frm).validate()) {
      return;
    }
    var fTagIds = $("#fque_tags").data("ids");
    $(frm.fque_tags).val("");
    var data = fcom.frmData(frm);
    console.log(data);
    fcom.updateWithAjax(fcom.makeUrl("Forum", "setup"), data, function (res) {
      if ("0" != res.status) {
        window.location = fcom.makeUrl("Forum");
      }
    });
  };

  getApprovalRequestForm = function () {
    fcom.ajax(
      fcom.makeUrl("ForumTagRequests", "form"),
      {},
      function (response) {
        $.w3mentorsmodal(response);
      }
    );
  };

  setupApprovalRequest = function (frm) {
    $(frm.ftagreq_id).val(0);
    fcom.updateWithAjax(
      fcom.makeUrl("ForumTagRequests", "setup"),
      fcom.frmData(frm),
      function (response) {
        if ("1" == response.status) {
          $.w3mentorsmodal.close();
        }
      }
    );
  };

  formatSlug = function (fld) {
    fcom.updateWithAjax(
      fcom.makeUrl("Home", "slug"),
      { slug: $(fld).val() },
      function (res) {
        $(fld).val(res.slug);
        if (res.slug != "") {
          checkUnique(
            $(fld),
            "tbl_forum_questions",
            "fque_slug",
            "fque_id",
            $("#fque_id"),
            []
          );
        }
      }
    );
  };
});
$(document).ready(function () {
  $("#addquestion").submit(function (e) {
    e.preventDefault();
    setup(this);
  });
  $("#fque_tags").bind("keypress keydown keyup", function (e) {
    if (e.keyCode == 13) {
      e.preventDefault();
      return false;
    }
  });
  $('input[name="fque_tags"]').autocomplete({
    source: function (request, response) {
      fcom.updateWithAjax(
        fcom.makeUrl("ForumTags", "autoSuggestList"),
        {
          keyword: request.term,
        },
        function (result) {
          response(
            $.map(result.data, function (item) {
              return {
                label: item["ftag_name"],
                value: item["ftag_id"],
              };
            })
          );
        }
      );
    },
    select: function (event, ui) {
      if (ui.item.value == -1) {
        event.preventDefault();
        return;
      }
      event.preventDefault();
      selectTag(ui.item.label, ui.item.value);
    },
    focus: function (event, ui) {
      if (ui.item.value == -1) {
        event.preventDefault();
        return;
      }
      event.preventDefault();
    },
  });
  $("#que_title").on("input", function () {
    forum.validateTxtLimit(this);
  });
});
