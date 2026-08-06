$(document).ready(function() {
    var slider = document.getElementById('price-slider-noui');
    var searchDebounce = fatDebounce(function() {
        search(document.frmSearch);
    }, 1000);

   
    openFilter = function () {
        $("body").addClass('is-filter-show');
        $("#filter-panel").addClass('is-filter-visible');
        $('.btn--filters').attr('onclick', 'closeFilter()');
    };

    closeFilter = function () {
        $("body").removeClass('is-filter-show');
        $("#filter-panel").removeClass('is-filter-visible');
        $('.btn--filters').attr('onclick', 'openFilter()');
    };

    searchKeyword = function () {
        document.frmSearch.keyword.value = $('.keyword-field-js').val();
        var keyword = document.frmSearch.keyword.value;
        if (keyword.trim() != '') {
            $(".main-search__submit").hide();
            $(".main-search__reset").show();
        }
        search(document.frmSearch);
    };

    clearKeyword = function () {
        document.frmSearch.search_keyword.value = '';
        document.frmSearch.record_id.value = '';
        $(".main-search__submit").show();
        $(".main-search__reset").hide();
        $('select[name="keyword"]').val(null).trigger('change');
        search(document.frmSearch);
    };

    search = function(frmSearch, scroll = 0) {
        fcom.process();
        var data = fcom.frmData(frmSearch);
        fcom.ajax(fcom.makeUrl('Courses', 'search'), data, function(response) {
            $('#listing').html(response);
            if (scroll == 1) {
                $('html, body').animate({
                    scrollTop: ($(".record-count-header").offset().top) - 100
                }, 1000);
            }
        });
    };
    gotoPage = function(pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm, 1);
    };

    applyFilters = function(name, reset = false) {
        if ($('select[name="' + name + '"]').val().trim() != '') {
            $('.submit-' + name + '-js').hide();
            $('.reset-' + name + '-js').show();
            $('.select2-selection__arrow').hide();
        }
        if (reset === true) {
            return;
        }
        searchDebounce();
    };
    searchByFilters = function(close = 0) {
        if (close == 0) {
            searchDebounce();
        }
    };

    $('form[name="frmSearch"] input[type="checkbox"], form[name="frmSearch"] input[type="radio"]').change(function () {
        countFilters();
        searchDebounce();
    });

    onkeyupCategory = function() {
        var keyword = $('input[name="category"]').val().toLowerCase();
        $('.list-items-js').hide();
        $('.list-items-js').each(function() {
            var labelText = $(this).find('.form-check-label').text().toLowerCase();
            if (labelText.indexOf(keyword) !== -1) {
                $(this).show();
            }
        });
    };

    /* Price [ */
    searchByPrice = function(reset = false) {
        var price = [];
        if (!isNaN(parseInt($('input[name="price_from"]').val()))) {
            price.push($('input[name="price_from"]').val());
        }
        if (!isNaN(parseInt($('input[name="price_till"]').val()))) {
            price.push($('input[name="price_till"]').val());
        }
        $('input[name="price[]"]:checked').each(function() {
            price.push($(this).parent().find('.select-option__item').text());
        });
        if (reset === true) {
            return;
        }
        countFilters();
        searchDebounce();
    };
    /* ] */

    /* Sorting */
    toggleSort = function() {
        $('body').toggleClass('sort-active');
        $('.sort-trigger-js').toggleClass('is-active');
        $('.sort-trigger-js').siblings('.sort-target-js').slideToggle();
    };
    priceSortSearch = function(_obj) {
        var id = $(_obj).val();
        document.frmSearch.price_sorting.value = id;
        $('.sorting-action__value').text($('.sortOptTitleJs' + id).text());
        toggleSort();
        $("body").removeClass('sort-active');
        search(document.frmSearch);
    };
    /* ] */

    /* More Filters [ */
    clearAllFilters = function() {
        $('input[name="course_cate_id[]"]').prop('checked', false);
        $('input[name="category"]').val('');
        onkeyupCategory();
        $('input[name="price[]"]').prop('checked', false);
        $('input[name="price_from"], input[name="price_till"]').val('');
        $('input[name="course_ratings"]').prop('checked', false);
        $('input[name="course_level[]"]').prop('checked', false);
        $('input[name="course_levels"]').val('');
        $('input[name="course_clang_id[]"]').prop('checked', false);
        $('input[name="course_languages"]').val('');
        resetSlider();
        countFilters();
        if($('form[name="frmSearchPaging"]').length > 0) {
            document.frmSearchPaging.pageno.value = 1;
        }
        search(document.frmSearch);
    };
    /* ] */

    showPreviewVideo = function (courseId) {
        fcom.process();
        fcom.ajax(fcom.makeUrl('Courses', 'previewVideo', [courseId]), '', function (resp) {
            $.w3mentorsmodal(resp,{ 'size': 'modal-lg' });
            fcom.close();
        });
    };
    let options = [];
    $('select[name="keyword"]').select2({
        placeholder: langLbl.courseSrchPlaceholder,
        language: {
            searching: function() {
                return langLbl.searching;
            }
        },
        ajax: {
            url: fcom.makeUrl('Courses', 'autoComplete'),
            type: 'post',
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                var query = {
                  term: params.term,
                  fIsAjax:1
                }
                return query;
            },
            processResults: function (data) {
                $('select[name="keyword"]').find('option').remove();
                if (data.length > 0) {
                    $.each(data, function(key, value) {
                        $.each(value.children, function(key1, value1) {
                            options[value1.id] = {
                                type: value.type,
                                text: value1.text
                            };
                        });
                    });
                }
                return {
                    results: (data.length > 0) ? data : []
                }
            }
        }
    }).on("select2:select", function(e) {
        if (options.length > 0) {
            var group = options[e.params.data.id].type;
            var text = options[e.params.data.id].text;
            var reset = false;
            if (e.params.data.reset) {
                reset = e.params.data.reset;
            }
            $('input[name="record_id"]').val(e.params.data.id);
            $('input[name="type"]').val(group);
            $('input[name="search_keyword"]').val(text);
        }
        $('.main-search__submit').hide();
        $('.main-search__reset').show();
        applyFilters('keyword', reset);
    });

    toggleCourseFavorite = function(courseId, el) {
        var status = $(el).data('status');
        var data = 'course_id= ' + courseId + '&status=' + status;
        fcom.updateWithAjax(fcom.makeUrl('Courses', 'toggleFavorite', [], confWebDashUrl), data, function(resp) {
            if (status == 0) {
                $(el).data("status", 1).addClass("is-active");
            } else {
                $(el).data("status", 0).removeClass("is-active");
            }
        });
    };
    searchByKeyword = function() {
        var keywordData = {
            id: $('input[name="record_id"]').val(),
            text: $('input[name="search_keyword"]').val(),
        };
        if (keywordData.id != '') {
            var newOption = new Option(keywordData.text, keywordData.id, false, false);
            $('select[name="keyword"]').append(newOption).trigger('change');
            $('select[name="keyword"]').trigger({
                type: 'select2:select',
                params: {
                    data: {
                        id: keywordData.id,
                        text: keywordData.text,
                        selected: true,
                        reset: true,
                    }
                }
            });
        }
    };

    function initSlider() {
        let priceFrom = parseFloat($('input[name="minPrice"]').val());
        let priceTill = parseFloat($('input[name="maxPrice"]').val());
        if($('input[name="price_from"]').val() != '') {
            priceFrom = parseFloat($('input[name="price_from"]').val());
        }
        if($('input[name="price_till"]').val() != '') {
            priceTill = parseFloat($('input[name="price_till"]').val());
        }
        noUiSlider.create(slider, {
            start: [priceFrom, priceTill],
            step: 1,
            connect: true,
            range: {
                'min': parseFloat($('input[name="minPrice"]').val()),
                'max': parseFloat($('input[name="maxPrice"]').val())
            },
            direction: langLbl.layoutDirection,
            behaviour: 'tap',
            tooltips: [{to: function(value) {return formatMoney(value);}}, {to: function(value) {return formatMoney(value);}}],
        });
        slider.noUiSlider.on('update', function (values, handle) {
            $('#price-slider-noui').removeClass('noUi-txt-dir-rtl');
        });

        /* Manage the Input fields on the slider change */
        slider.noUiSlider.on('change', function (values, handle) {
            vals = slider.noUiSlider.get(true);
            var value = values[handle];
            if (handle) {
                $('input[name="price_from"]').val(Math.round(vals[0]));
                $('input[name="price_till"]').val(Math.round(value));
            } else {
                $('input[name="price_from"]').val(Math.round(value));
                $('input[name="price_till"]').val(Math.round(vals[1]));
            }
            searchByPrice();
        });
    };

    /* Change slider handles on input field change */
    $(".priceSliderValue").keyup(function() {
        if($('input[name="price_from"]').val() != '') {
            slider.noUiSlider.setHandle(0, Math.round($('input[name="price_from"]').val()));
        } else {
            slider.noUiSlider.setHandle(0, Math.round($('input[name="minPrice"]').val()));
        }
        if($('input[name="price_till"]').val() != '') {
            slider.noUiSlider.setHandle(1, Math.round($('input[name="price_till"]').val()));
        } else {
            slider.noUiSlider.setHandle(1, Math.round($('input[name="maxPrice"]').val()));
        }
    });

    function resetSlider() {
        if (slider.noUiSlider) {
            slider.noUiSlider.destroy();
        }
        initSlider();
    };
    
    updateStickyPosition();
    $(window).on("resize", updateStickyPosition);
    countFilters();
    searchByPrice(true);
    searchByKeyword();
    searchByFilters();
    initSlider();
});