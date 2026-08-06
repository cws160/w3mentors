/* global fcom, langLbl, range, LABELS */
$(document).ready(function () {
    var slider = document.getElementById('price-slider-noui');
    var searchDebounce = fatDebounce(function() {
        search(document.frmSearch);
    }, 1000);

    $('input[name="keyword_search"]').on('keyup', function (event) {
        $(".main-search__submit").show();
        $(".main-search__reset").hide();
        if ((event.keyCode == 13 && $(this).val() != '')) {
            document.frmSearch.keyword.value = $('.keyword-field-js').val();
            search(document.frmSearch);
            $(".main-search__submit").hide();
            $(".main-search__reset").show();
        }
        if($(this).val() == '') {
            document.frmSearch.keyword.value = '';
            search(document.frmSearch);
            $(".main-search__submit").show();
            $(".main-search__reset").hide();
        }

    });

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
        document.frmSearch.keyword.value = '';
        $('.keyword-field-js').val('');
        $(".main-search__submit").show();
        $(".main-search__reset").hide();
        search(document.frmSearch);
    };

    onkeyupLanguage = function() {
        var keyword = $('input[name="teach_language"]').val().toLowerCase();
        $('.list-items-js').hide();
        $('.list-items-js').each(function() {
            var labelText = $(this).find('.form-check-label').text().toLowerCase();
            if (labelText.indexOf(keyword) !== -1) {
                $(this).show();
            }
        });
    };
    /* overwrite contains function to match any letter case */
    jQuery.expr[':'].contains = function(a, i, m) {
        return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    }

    $('form[name="frmSearch"] input[type="checkbox"], form[name="frmSearch"] input[type="radio"]').change(function () {
        if ($(this).attr('name') == 'ch_offline') {
            return;
        }
        countFilters();
        searchDebounce();
    });

    searchPrice = function () {
        countFilters();
        searchDebounce();
    };

    onkeyupLocation = function () {
        $('.select-location-js').parent().parent().hide();
        var keyword = ($('input[name="location_search"]').val()).toLowerCase();
        $('.select-location-js:contains("' + $.trim(keyword) + '")').parent().parent().show();
    };
    clearFilters = function () {
        $('input[name="teachs[]"]').prop('checked', false);
        $('input[name="teach_language"]').val('').trigger('onkeyup');
        $('input[name="locations[]"]').prop('checked', false);
        $('input[name="location_search"]').val('').trigger('onkeyup');
        $('input[name="gender[]"]').prop('checked', false);
        $('input[name="speaks[]"]').prop('checked', false);
        $('input[name="accents[]"]').prop('checked', false);
        $('input[name="levels[]"]').prop('checked', false);
        $('input[name="subjects[]"]').prop('checked', false);
        $('input[name="lesson_type[]"]').prop('checked', false);
        $('input[name="tests[]"]').prop('checked', false);
        $('input[name="age_group[]"]').prop('checked', false);
        $('input[name="days[]"]').prop('checked', false);
        $('input[name="slots[]"]').prop('checked', false);
        $('input[name="price[]"], input[name="ch_offline"], input[name="user_lastseen"], input[name="user_featured"]').prop('checked', false);
        $('input[name="price_from"], input[name="price_till"], input[name="user_lat"], input[name="user_lng"], input[name="address"]').val('');
        $('input[name="user_offline_sessions"]').val(0);
        $("#btnCloseJs").hide();
        $('.geo-location--js').hide();
        resetSlider();
        countFilters();
        if($('form[name="frmSearchPaging"]').length > 0) {
            document.frmSearchPaging.pageno.value = 1;
        }
        search(document.frmSearch);
    };

    clearLocation = function () {
        $("#btnCloseJs").hide();
        $("input[name='user_lat'], input[name='user_lng'], input[name='address'],  input[name='formatted_address']").val('');
        search(document.frmSearch);
    };

    toggleSort = function () {
        $('body').toggleClass('sort-active');
        $('.sort-trigger-js').toggleClass('is-active');
        $('.sort-trigger-js').siblings('.sort-target-js').slideToggle();
    };

    sortsearch = function (_obj) {
        var id = $(_obj).val();
        document.frmSearch.sorting.value = id;
        $('.sorting-action__value').text($('.sortOptTitleJs' + id).text());
        search(document.frmSearch);
        $("body").removeClass('sort-active');
    };

    search = function (frmSearch, scroll = 0) {
        fcom.process();
        var data = fcom.frmData(frmSearch);
        fcom.ajax(fcom.makeUrl('Teachers', 'search'), data, function (response) {
            $('#listing').html(response);
            initShareThis();
            if (scroll == 1) {
                $('html, body').animate({
                    scrollTop: ($(".record-count-header").offset().top) - 100
                }, 1000);
            }
            if($('.sort-trigger-js').hasClass('is-active')) {
                toggleSort();
                $("body").removeClass('sort-active');
            }
        });
    };

    gotoPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm, 1);
    };



    openFilter = function () {
        $("body").addClass('is-filter-show');
        $("#filter-panel").addClass('is-filter-visible');
        $('.btn--filters').attr('onclick', 'closeFilter()');
        setTimeout(function () {
            $('.filters-layout__item-second .filter-item__target').show();
            $('.filters-layout__item-second .filter-item__trigger').addClass('is-active');
        }, 500);
    };

    closeFilter = function () {
        $("body").removeClass('is-filter-show');
        $("#filter-panel").removeClass('is-filter-visible');
        $('.btn--filters').attr('onclick', 'openFilter()');
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

    viewCalendar = function (teacherId) {
        fcom.ajax(fcom.makeUrl('Teachers', 'teacherAvailability', [teacherId]), {}, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg middle-popup calendar--modal' });
        });
    };


    searchOfflineSessions = function (offline, onLoad = 0) {
        $('.geo-location--js').show();
        if (!offline) {
            document.frmSearch.user_lat.value = 0;
            document.frmSearch.user_lng.value = 0;
            document.frmSearch.address.value = '';
            document.frmSearch.formatted_address.value = '';
            $('.geo-location--js').hide();
        } else {
            if (onLoad == 0) {
                document.frmSearch.user_lat.value = $('input[name="address"]').attr('data-lat');
                document.frmSearch.user_lng.value = $('input[name="address"]').attr('data-lng');
                document.frmSearch.address.value = $('input[name="address"]').attr('data-address');
                document.frmSearch.formatted_address.value = $('input[name="address"]').attr('data-address');
            }
            ($('input[name="address"]').val() != '') ? $("#btnCloseJs").show() : $("#btnCloseJs").hide();
        }
        document.frmSearch.user_offline_sessions.value = (offline) ? 1 : 0;
        search(document.frmSearch);
    };

    $('input[name="address"]').on('keyup', function (event) {
        if ($(this).val() != '') {
            $("#btnCloseJs").show();
        } else {
            $("#btnCloseJs").hide();
        }
    });

    autoCompleteGoogle = function () {
        const input = document.getElementById("google-autocomplete");
        const options = {
            fields: ["formatted_address", "geometry", "name", "place_id", "address_components"],
            strictBounds: false,
        };
        const autocomplete = new google.maps.places.Autocomplete(input, options);
        autocomplete.setTypes(['establishment']);
        autocomplete.addListener("place_changed", () => {
            const place = autocomplete.getPlace();
            setLatLng(place);
        });
    }

    setLatLng = function (place) {
        document.frmSearch.user_lat.value = place.geometry.location.lat();
        document.frmSearch.user_lng.value = place.geometry.location.lng();
        document.frmSearch.formatted_address.value = place.formatted_address;
        $("#btnCloseJs").show();
        search(document.frmSearch);
        document.getElementById("google-autocomplete").value = place.formatted_address;
    }

    getLocation = function () {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(geocodePosition);
        } else {
            alert('location not detected');
        }
    }

    geocodePosition = function (pos) {
        var latlng = new google.maps.LatLng(pos.coords.latitude, pos.coords.longitude);
        geocoder = new google.maps.Geocoder();
        geocoder.geocode({
            latLng: latlng
        }, function (responses) {
            if (responses && responses.length > 0) {
                setLatLng(responses[0]);
            } else {
                console.log('Cannot determine address at this location.');
            }
        });
    }

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
            searchPrice();
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

    $('.other-filter-js').change(function () {
        var inputName = $(this).attr('name');
        var inputValue = $(this).is(':checked') ? 1 : 0;
        $('input[name="' + inputName + '"]').val(inputValue);
    });

    updateStickyPosition();
    $(window).on("resize", updateStickyPosition);
    countFilters();
    searchPrice(true);
    initSlider();
    search(document.frmSearch);
    if ($('input[name="ch_offline"]').length > 0) {
        searchOfflineSessions($('input[name="ch_offline"]').is(':checked'), 1);
        autoCompleteGoogle();
    }
});

function showIntroVideo(id)
{
    $('#video-id-' + id).trigger('click');
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

function initShareThis() {
    if (window.__sharethis__) {
        window.__sharethis__.initialize();
    }
}