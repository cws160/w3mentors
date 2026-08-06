$(document).ready(function () {
    search = function (frmSearch) {
        fcom.process();
        var data = fcom.frmData(frmSearch);
        fcom.ajax(fcom.makeUrl('Teachers', 'search', [true]), data, function (response) {
            $('#listing').html(response);
            $(".gototop").trigger('click');
        });
    };

    onkeyupLanguage = function() {
        var keyword = $('input[name="teach_language"]').val().toLowerCase();
        if(keyword != '') {
            $('.main-search__submit').hide();
            $('.main-search__reset').show();
        } else {
            $('.main-search__submit').show();
            $('.main-search__reset').hide();
        }
        $('.list-items-js, .no-record-js').hide();
        var i = 0;
        $('.list-items-js').each(function() {
            var labelText = $(this).find('.form-check-label').text().toLowerCase();
            if (labelText.indexOf(keyword) !== -1) {
                $(this).show();
                i += 1;
            }
        });
        if (i == 0) {
            $('.no-record-js').show();
        }
    };
    /* overwrite contains function to match any letter case */
    jQuery.expr[':'].contains = function(a, i, m) {
        return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };

    $('.main-search__trigger-js').click(function (event) {
        if ($('.main-search__target-js').is(':visible')) {
            return;
        }
        $('.main-search__trigger-js').removeClass('is-active');
        $('.main-search__target-js').hide();
        $(this).addClass("is-active").siblings('.main-search__target-js').slideDown();
    });

    $('body').click(function (e) {
        if ($(e.target).parents('.main-search').length == 0) {
            $('.main-search__trigger-js').siblings('.main-search__target-js').slideUp();
            $('.main-search__trigger-js').removeClass('is-active');
        }
    });

    gotoPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };

    selectLanguage = function(slug) {
        window.location = fcom.makeUrl('Teachers', 'languages', [slug]);
    };

    $(document).on('click', '.video-js', function () {
        var target = $(this).attr('href');
        var iframe = $(target).find('iframe');
        if (iframe.attr('src') == '') {
            iframe.attr('src', $(target).find('.video-src').attr('data-src'));
        } else {
            iframe.attr('src', '');
        }
    });

    search(document.frmSearch);
    document.frmSearch.pageno.value = 1;
});

function showIntroVideo(id)
{
    $('#video-id-' + id).trigger('click');
}
$(window).scroll(function() {
    var filterPanelOffset = $('.section--page-header').offset().top;
    var scrollPosition = $(window).scrollTop();

    if (scrollPosition >= filterPanelOffset) {
        $('.section--listing').addClass('is-filter-fixed');
    } else {
        $('.section--listing').removeClass('is-filter-fixed');
    }
});  

viewCalendar = function (teacherId) {
    fcom.ajax(fcom.makeUrl('Teachers', 'teacherAvailability', [teacherId]), {}, function (response) {
        $.w3mentorsmodal(response, { 'size': 'modal-lg middle-popup calendar--modal' });
    });
};

clearKeyword = function() {
    $('input[name="teach_language"]').val('');
    $('.main-search__submit').show();
    $('.main-search__reset').hide();
    $('.list-items-js').show();
    $('.no-record-js').hide();
};

function fetchTeacherMeta(teacherId) {
    let target = '.share-teacher-js-' + teacherId;
    if($(target).attr('data-title') != '') {
        return;
    }
    fcom.ajax(fcom.makeUrl('Teachers', 'fetchTeacherMeta', [teacherId]), {}, function (response) {
        if(isJson(response)) {
            response = JSON.parse(response);
            if(response.status == 1) {
                $(target).attr('data-title', response.meta_title);
            } else {
                return;
            }
        }
    });
};