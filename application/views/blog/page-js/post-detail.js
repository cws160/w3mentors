/* global langLbl, fcom, boolLoadComments */
$(document).ready(function () {
    $('input[name="keyword"]').on('keyup', function (event) {
        if ((event.keyCode == 13 && $(this).val() != '')) {
            $(".main-search__submit").hide();
            $(".main-search__reset").show();
        }
        if($(this).val() == '') {
            $(".main-search__submit").show();
            $(".main-search__reset").hide();
            searchBlogss(document.frmBlogSearchPaging);
        }
    });

    $('.toggle-nav--vertical-js').click(function () {
        $(this).toggleClass("active");
        if ($(window).width() < 990) {
            $('.nav--vertical-js').slideToggle();
        }
    });
    /* blog slider */
    $('.post__pic').slick({
        dots: false,
        arrows: true,
        autoplay: true,
        pauseOnHover: false,
        rtl: langLbl.layoutDirection == 'rtl',
    });
});
(function () {
    bannerAdds = function () {
        fcom.process();
        fcom.ajax(fcom.makeUrl('Banner', 'blogPage'), '', function (res) {
            $("#div--banners").html(res);
            if ($(window).width() < 990) {
                $('.grids').masonry({ itemSelector: '.grids__item', });
            }
        });
    };
    searchBlogss = function (frm) {
        fcom.process();
        if($('.blog-post-keyword').val() != '') {
            $(".main-search__submit").hide();
            $(".main-search__reset").show();
        }
        fcom.updateWithAjax(fcom.makeUrl('Blog', 'search'), fcom.frmData(frm), function (ans) {
            $("#listItem").html(ans.html);
        });
    };
    setupPostComment = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Blog', 'setupPostComment'), fcom.frmData(frm), function (res) {
            frm.reset();
        });
    };
    searchComments = function (blogId, page) {
        page = parseInt(page);
        fcom.ajax(fcom.makeUrl('Blog', 'searchComments'), { blogId: blogId, page: page }, function (res) {
            let divElement = $("#comments--listing");
            if (page > 1) {
                divElement.find("#loadMoreBtn").remove();
            }
            divElement.append(res);
        });
    };
    goToSearchPage = function (page) {
        var frm = document.frmBlogSearchPaging;
        $(frm.page).val(page);
        searchBlogss(frm);
    };

    updateStickyPosition();
    $(window).on("resize", updateStickyPosition);

    clearKeyword = function() {
        $('.blog-post-keyword').val('');
        $(".main-search__submit").show();
        $(".main-search__reset").hide();
        searchBlogss(document.frmBlogSearch);
    }
})();
