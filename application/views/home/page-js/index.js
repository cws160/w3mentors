/* global langLbl, LANGUAGES, fcom */
$("document").ready(function () {
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

    switchCourseCat = function(element) {
        let targetId = $(element).attr('href');
        $('.inline-content').removeClass('visible');
        $('.inline-tabs-link').removeClass('is-active');
        $(element).addClass('is-active');
        $(targetId).addClass('visible');
    };

    /* New Design */
    $('.slider-onefifth-js').slick({
        centerPadding: '0px',
        slidesToShow: 5,
        slidesToScroll: 1,
        infinite: false,
        dots: false,
        rtl: (langLbl.layoutDirection == 'rtl') ? true : false,
        responsive: [{
                breakpoint: 1200,
                settings: {
                    slidesToShow: 4,
                    arrows: false,
                    dots: true
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 3,
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
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    arrows: false,
                    dots: true
                }
            }
        ]
    });
    $('.slider-oneforth-js').slick({
        centerPadding: '0px',
        slidesToShow: 4,
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
                breakpoint: 480,
                settings: {
                    centerPadding: '0px',
                    slidesToShow: 1,
                    arrows: false,
                    dots: true
                }
            }
        ]
    });
    $('.slider-onethird-js').slick({
        centerPadding: '0px',
        slidesToShow: 3,
        slidesToScroll: 1,
        dots: false,
        rtl: (langLbl.layoutDirection == 'rtl') ? true : false,
        responsive: [{
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3,
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
                breakpoint: 480,
                settings: {
                    centerPadding: '0px',
                    slidesToShow: 1,
                    arrows: false,
                    dots: true
                }
            }
        ]
    });
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
    let testiLen = $('.js--testimonials-thumb').find('.testimonial').length;
    $('.js--testimonials-main').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: (testiLen > 1) ? true : false,
        arrows: true,
        fade: true,
        rtl: (langLbl.layoutDirection == 'rtl') ? true : false,
        asNavFor: '.js--testimonials-thumb',
        responsive: [{
                breakpoint: 1200,
                settings: {
                    arrows: false,
                    dots: true,
                }
            },
            {
                breakpoint: 580,
                settings: {
                    arrows: false,
                    dots: true,
                }
            }
        ]
    });
    $('.js--testimonials-thumb').slick({
        slidesToShow: testiLen - 1,
        asNavFor: '.js--testimonials-main',
        arrows: false,
        dots: false,
        infinite: (testiLen > 1) ? true : false,
        centerMode: true,
        variableWidth: true,
        focusOnSelect: true,
        draggable: false,
        rtl: (langLbl.layoutDirection == 'rtl') ? true : false,
        responsive: [{
                breakpoint:1200,
                settings: {
                    slidesToShow: 1,
                    variableWidth: false,
                }
            },
            {
                breakpoint: 580,
                settings: {
                    slidesToShow: 1,
                    variableWidth: false,
                }
            }
        ]
    });

    const counters = document.querySelectorAll('.stats__number span');
    const duration = 2000; 

    counters.forEach(counter => {
        const updateCounter = () => {
            const target = +counter.getAttribute('data-target');
            const start = +counter.innerText;
            const increment = target / (duration / 100); 

            if (start < target) {
                counter.innerText = Math.ceil(start + increment); 
                setTimeout(updateCounter, 100); 
            } else {
                counter.innerText = target; 
            }
        };

        updateCounter(); 
    });
});

