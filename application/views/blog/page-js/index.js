/* global fcom, bpCategoryId */
$(document).ready(function () {
    $('input[name="keyword"]').on('keyup', function (event) {
        if ((event.keyCode == 13 && $(this).val() != '')) {
            $(".main-search__submit").hide();
            $(".main-search__reset").show();
        }
        if($(this).val() == '') {
            $(".main-search__submit").show();
            $(".main-search__reset").hide();
            searchBlogs(document.frmBlogSearch);
        }

    });

    searchBlogs(document.frmBlogSearch);
    $('.toggle-nav--vertical-js').click(function () {
        $(this).toggleClass("active");
        if ($(window).width() < 990) {
            $('.nav--vertical-js').slideToggle();
        }
    });
});
(function () {
    bannerAdds = function () {
        fcom.process();
        fcom.ajax(fcom.makeUrl('Banner', 'blogPage'), '', function (res) {
            $("#div--banners").html(res);
        });
    };
    var dv = '#listing';
    reloadListing = function () {
        searchBlogs(document.frmBlogSearch);
    };
    searchBlogs = function (frm, append, scroll = 0) {
        fcom.process();
        if($('.blog-keyword').val() != '') {
            $(".main-search__submit").hide();
            $(".main-search__reset").show();
        }
        if (typeof append == undefined || append == null) {
            append = 0;
        }
        var data = fcom.frmData(frm);
        if (bpCategoryId) {
            data += '&categoryId=' + bpCategoryId;
        }
        fcom.updateWithAjax(fcom.makeUrl('Blog', 'search'), data, function (ans) {
            if (append == 1) {
                $(dv).append(ans.html);
            } else {
                $(dv).html(ans.html);
            }
            if (scroll == 1) {
                $('html, body').animate({
                    scrollTop: ($("body").offset().top)
                }, 1000);
            }
            if ($("#loadMoreBtnDiv").length) {
                $("#loadMoreBtnDiv").html(ans.loadMoreBtnHtml);
            }
        });
    };
    goToSearchPage = function (page) {
        var frm = document.frmBlogSearchPaging;
        $(frm.page).val(page);
        searchBlogs(frm, null, 1);
    };

    clearKeyword = function () {
        $('input[name="keyword"]').val('');
        $(".main-search__submit").show();
        $(".main-search__reset").hide();
        searchBlogs(document.frmBlogSearch);
    };
})();