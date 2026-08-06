/* global fcom, langLbl, range, LABELS */
$(document).ready(function () {

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
        $('.keyword-field-js').val('');
        document.frmSearch.keyword.value = '';
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
    
    $('form[name="frmSearch"] input[name="teachs[]"], form[name="frmSearch"] input[name="classtype[]"], form[name="frmSearch"] input[name="duration[]"]').change(function () {
        countFilters();
        searchDebounce();
    });

    searchOfflineClasses = function (offline, onLoad = 0) {
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
        document.frmSearch.grpcls_offline.value = offline ? 1 : 0;
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
            console.log('location not detected');
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

    clearLocation = function () {
        $("#btnCloseJs").hide();
        $("input[name='user_lat'], input[name='user_lng'], input[name='address'],  input[name='formatted_address']").val('');
        search(document.frmSearch);
    };

    search = function (frmSearch, scroll = 0) {
        fcom.process();
        var data = fcom.frmData(frmSearch);
        fcom.ajax(fcom.makeUrl('GroupClasses', 'search'), data, function (response) {
            $('#listing').html(response);
            if (scroll == 1) {
                $('html, body').animate({
                    scrollTop: ($(".record-count-header").offset().top) - 100
                }, 1000);
            }
        });
    };

    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm, 1);
    };

    openFilter = function() {
        $("body").addClass('is-filter-show');
        $("#filter-panel").addClass('is-filter-visible');
        $('.btn--filters').attr('onclick', 'closeFilter()');
        setTimeout(function() {
            $('.filters-layout__item-second .filter-item__target').show();
            $('.filters-layout__item-second .filter-item__trigger').addClass('is-active');
        }, 500);
    };

    closeFilter = function() {
        $("body").removeClass('is-filter-show');
        $("#filter-panel").removeClass('is-filter-visible');
        $('.btn--filters').attr('onclick', 'openFilter()');
    };

    clearAllFilters = function() {
        $("input[name='user_lat'], input[name='user_lng'], input[name='address'],  input[name='formatted_address'], input[name='grpcls_offline']").val('');
        $('input[name="teach_language"]').val('').trigger('keyup');
        $('input[name="teachs[]"]').prop('checked', false);
        $('input[name="classtype[]"]').prop('checked', false);
        $('input[name="duration[]"]').prop('checked', false);
        $('input[name="ch_offline"]').prop('checked', false);
        $("#btnCloseJs").hide();
        $('.geo-location--js').hide();
        countFilters();
        if($('form[name="frmSearchPaging"]').length > 0) {
            document.frmSearchPaging.pageno.value = 1;
        }
        search(document.frmSearch);
    };

    updateStickyPosition();
    $(window).on("resize", updateStickyPosition);
    if ($('input[name="ch_offline"]').length > 0) {
        searchOfflineClasses($('input[name="ch_offline"]').is(':checked'), 1);
        autoCompleteGoogle();
    }
    countFilters();
    search(document.frmSearch);
});

