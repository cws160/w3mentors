/* global fcom */
$(document).ready(function () {
    search(document.srchForm);
    $("input[name='course_clang']").autocomplete({
        'source': function (request, response) {
            $.ajax({
                url: fcom.makeUrl('Courses', 'autoCompleteJson'),
                data: {keyword: request, fIsAjax: 1},
                dataType: 'json',
                type: 'post',
                success: function (result) {
                    response($.map(result.data, function (item) {
                        return {
                            label: escapeHtml(item['clang_name']),
                            value: item['clang_id'], name: item['clang_name']
                        };
                    }));
                },
            });
        },
        'select': function (item) {
            $("input[name='course_clang_id']").val(item.value);
            $("input[name='course_clang']").val(item.name);
        }
    });
    $("input[name='course_clang']").keyup(function () {
        $("input[name='course_clang_id']").val('');
    });
});
(function () {
    var dv = '#listing';
    goToSearchPage = function (pageno) {
        var frm = document.frmPaging;
        $(frm.page).val(pageno);
        search(frm);
    };
    search = function (form) {
        var data = data = fcom.frmData(form);
        fcom.ajax(fcom.makeUrl('Courses', 'search'), data, function (res) {
            $(dv).html(res);
        });
    };
    clearSearch = function () {
        document.srchForm.reset();
        $("input[name='course_clang_id'], select[name='course_cateid'], select[name='course_subcateid']").val('');
        getSubcategories(0);
        search(document.srchForm);
    };

    view = function (courseId) {
        fcom.ajax(fcom.makeUrl('Courses', 'view', [courseId]), '', function (res) {
            $.w3mentorsmodal(res);
        });
    };
    userLogin = function (userId, courseId, action = 'edit') {
        fcom.updateWithAjax(fcom.makeUrl('Users', 'login', [userId]), '', function (res) {
            if (action == 'edit') {
                window.open(fcom.makeUrl('Courses', 'form', [courseId], SITE_ROOT_DASHBOARD_URL), "_blank");
            } else if (action == 'preview') {
                window.open(fcom.makeUrl('CoursePreview', 'index', [courseId], SITE_ROOT_DASHBOARD_URL), "_blank");
            }
        });
    };
    getSubcategories = function (id, selectedId = 0) {
        fcom.ajax(fcom.makeUrl('Courses', 'getSubcategories', [id, selectedId]), '', function (res) {
            $("#subCategories").html(res);
        }, {async: false});
    };
    updateStatus = function (id, status) {
        if (confirm(langLbl.confirmUpdateStatus)) {
            fcom.updateWithAjax(fcom.makeUrl('Courses', 'updateStatus', [id, status]), '', function (res) {
                if ($('form[name="frmPaging"] input[name="page"]').length > 0) {
                    search(document.frmPaging);
                } else {
                    search(document.srchForm);
                }
            });
        }
    };
})();
