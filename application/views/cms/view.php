<?php defined('SYSTEM_INIT') or die('Invalid Usage'); ?>
<?php if ($cPage['cpage_layout'] == Contentpage::CONTENT_PAGE_LAYOUT1_TYPE) { ?>
    <section class="section section--hero" style="background-image: url(<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('image', 'show', [Afile::TYPE_CPAGE_BACKGROUND_IMAGE, $cPage['cpage_id'], Afile::SIZE_LARGE, $siteLangId]), CONF_DEF_CACHE_TIME); ?>);">
        <div class="container container--xxl">
            <div class="hero-panel">
                <div class="hero-panel__content" data-aos="fade-up" data-aos-duration="1000">
                    <div class="content">
                        <h4><?php echo $cPage['cpage_title']; ?></h4>
                        <?php if ($cPage['cpage_image_title']) { ?>
                            <h2><?php echo $cPage['cpage_image_title']; ?></h2>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($blockData) { ?>
        <?php if (isset($blockData[Contentpage::CONTENT_PAGE_LAYOUT1_BLOCK_1]) && !empty(trim($blockData[Contentpage::CONTENT_PAGE_LAYOUT1_BLOCK_1]['cpblocklang_text']))) { ?>
            <section class="section">
                <div class="editor-content">
                    <div class="container container--xxl">
                        <?php echo FatUtility::decodeHtmlEntities(trim($blockData[Contentpage::CONTENT_PAGE_LAYOUT1_BLOCK_1]['cpblocklang_text'])); ?>
                    </div>
                </div>
            </section>
        <?php } ?>
        <?php if (isset($blockData[Contentpage::CONTENT_PAGE_LAYOUT1_BLOCK_2]) && !empty(trim($blockData[Contentpage::CONTENT_PAGE_LAYOUT1_BLOCK_2]['cpblocklang_text']))) { ?>
            <div class="editor-content pb-5">
                <?php echo FatUtility::decodeHtmlEntities(trim($blockData[Contentpage::CONTENT_PAGE_LAYOUT1_BLOCK_2]['cpblocklang_text'])); ?>
            </div>
        <?php } ?>
    <?php } ?>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.slider-onefifth-js').on('init', function(event, slick) {
                $(slick.$dots).addClass('mb-2');
            });
            $('.slider-onefifth-js').slick({
                centerPadding: '0px',
                slidesToShow: 5,
                slidesToScroll: 1,
                dots: true,
                arrows: false,
                centerMode: true,
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

            $('.js--testimonials').slick({
                centerPadding: '0',
                centerMode: true,
                slidesToShow: 3,
                slidesToScroll: 1,
                dots: false,
                arrows: false,
                infinite: true,
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
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 2,
                            arrows: false,
                            dots: true
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 1,
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
            $('.slider-nav .prev-slide').on('click', function() {
                $('.js--testimonials').slick('slickPrev');
            });

            $('.slider-nav .next-slide').on('click', function() {
                $('.js--testimonials').slick('slickNext');
            });

            var _tab = $('.js-tab');
            _tab.each(function() {
                var _thisTab = $(this),
                    _tabTrigger = _thisTab.find('a'),
                    _tabTarget = [];
                _tabTrigger.each(function() {
                    var _this = $(this),
                        _target = $(_this.attr('href'));
                    _tabTarget.push(_target);
                    _this.on('click', function(e) {
                        var _index = _this.index(),
                            _move;
                        e.preventDefault();
                        for (var i = 0; i < _index; i++) {
                            _move += _tabTrigger[i].outerWidth();
                        }
                        _tabTrigger.removeClass('current');
                        $.each(_tabTarget, function(index, _thisTarget) {
                            _thisTarget.removeClass('visible');
                        });
                        _this.addClass('current');
                        _target.addClass('visible');
                        _thisTab.animate({
                            scrollLeft: Math.max(0, _move)
                        }, 800);
                    });
                });
            });

            const horizontalAccordions = $(".accordion.width");
            horizontalAccordions.each((index, element) => {
                const accordion = $(element);
                const collapse = accordion.find(".collapse");
                const bodies = collapse.find("> *");
                accordion.height(accordion.height());
                bodies.width(bodies.eq(0).width());
                collapse.not(".show").each((index, element) => {
                    $(element).parent().find("[data-toggle='collapse']").addClass("collapsed");
                });

                accordion.find("[data-toggle='collapse']").on("click", function() {
                    const card = $(this).closest(".card");
                    const isCollapsed = $(this).hasClass("collapsed");
                    accordion.find(".card").removeClass("has-open");
                    if (isCollapsed) {
                        card.addClass("has-open");
                    }
                });

                collapse.each((index, element) => {
                    if ($(element).hasClass("show")) {
                        $(element).closest(".card").addClass("has-open");
                    }
                });
            });
        });
    </script>
<?php } else { ?>
    <section class="section bg-gradiant section--page-header text-center">
        <div class="container container--xl">
            <h1><?php echo $cPage['cpage_title']; ?></h1>
        </div>
    </section>
    <section class="section">
        <div class="container container--narrow">
            <div class="editor-content">
                <?php echo FatUtility::decodeHtmlEntities($cPage['cpage_content']) ?></p>
            </div>
        </div>
    </section>
<?php } ?>