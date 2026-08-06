$("document").ready(function () {
    viewAddress = function (id) {
        fcom.ajax(fcom.makeUrl('GroupClasses', 'viewAddress'), {id}, function (resp) {
            $.w3mentorsmodal(resp, { 'size': 'modal-lg' });
        });
    };

    /* New Design */
    $('.slider-oneforth-js').slick({
        centerPadding: '0px',
        slidesToShow: 4,
        slidesToScroll: 1,
        rtl: (langLbl.layoutDirection == 'rtl') ? true : false,
        dots: false,
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
    
    updateStickyPosition();
    $(window).on("resize", updateStickyPosition);
});

