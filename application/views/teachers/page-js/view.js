/* global fcom, langLbl */
$("document").ready(function () {
    loadOneHalfSlick();
    $('.tab-a').click(function () {
        $(".tab").removeClass('tab-active');
        $(".tab[data-id='" + $(this).attr('data-id') + "']").addClass("tab-active");
        $(".tab-a").parent('li').removeClass('is-active');
        $(this).parent().addClass('is-active');
    });
    /* FUNCTION FOR LEFT COLLAPSEABLE LINKS */
    if ($(window).width() < 767) {
        $('.box__head-trigger-js').click(function () {
            if ($(this).hasClass('is-active')) {
                $(this).removeClass('is-active');
                $(this).siblings('.box__body-target-js').slideUp();
                return false;
            }
            $('.box__head-trigger-js').removeClass('is-active');
            $(this).addClass("is-active");
            $('.box__body-target-js').slideUp();
            $(this).siblings('.box__body-target-js').slideDown();
        });
    }
    $('select[name="orderBy"]').change(function () {
        var frm = document.frmReviewSearch;
        $(frm.page).val(1);
        var dv = '#itemRatings';
        $(dv).html('');
        reviews(document.frmReviewSearch);
    });
    loadReviews = function (teacherId, pageno) {
        var data = fcom.frmData(document.reviewFrm);
        data += '&sorting=' + $('select[name="sorting"]').val() + '&pageno=' + pageno + '&teacher_id=' + teacherId;
        fcom.ajax(fcom.makeUrl('Teachers', 'reviews'), data, function (response) {
            if (pageno > 1) {
                $(".show-more-container").remove();
                $('#listing-reviews').append(response);
            } else {
                $('#listing-reviews').html(response);
            }
        });
    };

    toggleCourseFavorite = function (courseId, el) {
        var status = $(el).data('status');
        var data = 'course_id= ' + courseId + '&status=' + status;
        fcom.updateWithAjax(fcom.makeUrl('Courses', 'toggleFavorite', [], confWebDashUrl), data, function (resp) {
            if (status == 0) {
                $(el).data("status", 1).addClass("is-active");
            } else {
                $(el).data("status", 0).removeClass("is-active");
            }
        });
    };

    if ($(window).width() < 767) {
        $('.panel__head-trigger-js').click(function() {
            if ($(this).hasClass('is-active')) {
                $(this).removeClass('is-active');
                $(this).siblings('.panel__body-target-js').slideUp();
                return false;
            }
            $('.panel__head-trigger-js').removeClass('is-active');
            $(this).addClass("is-active");
            $('.panel__body-target-js').slideUp();
            $(this).siblings('.panel__body-target-js').slideDown();
            let slider = $(this).siblings('.panel__body-target-js').find('.slick-slider');
            if(slider.length > 0) {
                slider.slick('refresh');
            }
        });
    }
    var _tab = $('.js--tabs');

        _tab.each(function() {
            var _this = $(this),
                _tabTrigger = _this.find('a'),
                _tabTarget = _tabTrigger.map(function() {
                    return $($(this).attr('href'))[0];
                });

            _tabTrigger.on('click', function(e) {
                e.preventDefault();
                var _clickedTab = $(this),
                    _target = $(_clickedTab.attr('href'));

                _tabTrigger.removeClass('current');
                $(_tabTarget).removeClass('visible');

                _clickedTab.addClass('current');
                _target.addClass('visible');
            });
        });
    });

function viewCalendar(teacherId) {
    fcom.ajax(fcom.makeUrl('Teachers', 'teacherAvailability', [teacherId]), {}, function (response) {
        $('#availbility').html(response);
        $('.modal-header').remove();
    });
}
function showPrice(_obj) {
    $(_obj).prev().find('span').hide();
    $(_obj).prev().find('.price-' + $(_obj).val()).show();
}
loadOneHalfSlick = function () {
    $('.slider-onehalf-js').slick({
        centerPadding: '0px',
        slidesToShow: 2,
        slidesToScroll: 1,
        dots: false,
        rtl: (langLbl.layoutDirection == 'rtl') ? true : false,
        responsive: [{
                breakpoint: 1200,
                settings: {
                    slidesToShow: 2,
                    arrows: false,
                    dots: true
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                    arrows: false,
                    dots: true
                }
            },
            {
                breakpoint: 580,
                settings: {
                    slidesToShow: 1,
                    arrows: false,
                    dots: true
                }
            }
        ]
    });
};

changeSlot = function () {
    let slot = $('select[name="selected_slot"]').val();
    let element = $('.slot-change-' + slot);
    $('.trigger-checkout').css('display', 'none');
    element.css('display', 'block');
};