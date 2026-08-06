<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'experienceFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupAddress(this); return(false);');
$usrAddresses = $frm->getField('usradd_address');
$usrAddPhone = $frm->getField('usradd_phone');
$usrAddCity = $frm->getField('usradd_city');
$usrAddState = $frm->getField('usradd_state_id');
$usrAddZipcode = $frm->getField('usradd_zipcode');
$usrAddPlaceId = $frm->getField('usradd_place_id');
$usrAddPlaceName = $frm->getField('usradd_place_name');
$usrAddLatitude = $frm->getField('usradd_latitude');
$usrAddLongitude = $frm->getField('usradd_longitude');
$usrAddDefault = $frm->getField('usradd_default');
$usrAddType = $frm->getField('usradd_type');
$resetBtn = $frm->getField('btn_reset');
$submitBtn = $frm->getField('btn_submit');
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_Address'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 order-md-2">
            <div class="field-set">
                <div class="caption-wraper">
                    <label class="field_label">
                        <?php echo Label::getLabel('LBL_ENTER_A_LOCATION'); ?><span class="spn_must_field">*</span>
                    </label>
                </div>
                <div class="field-wraper">
                    <div class="field_cover">
                        <input id="google-autocomplete" type="text" value="" placeholder="<?php echo Label::getLabel('LBL_ENTER_A_LOCATION'); ?>" autocomplete="off">
                    </div>
                </div>
            </div>
            <div id="map" class="mb-4" style="aspect-ratio:1/1"></div>
        </div>
        <div class="col-md-6">
            <?php echo $frm->getFormTag(); ?>
            <?php echo $frm->getFieldHtml('usradd_id'); ?>
            <?php echo $frm->getFieldHtml('usradd_place_name'); ?>
            <?php echo $frm->getFieldHtml('usradd_place_id'); ?>
            <?php echo $frm->getFieldHtml('usradd_country_id'); ?>
            <?php echo $frm->getFieldHtml('usradd_latitude'); ?>
            <?php echo $frm->getFieldHtml('usradd_longitude'); ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $usrAddresses->getCaption(); ?>
                                <?php if ($usrAddresses->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $usrAddresses->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $usrAddPhone->getCaption(); ?>
                                <?php if ($usrAddPhone->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $usrAddPhone->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $usrAddCity->getCaption(); ?>
                                <?php if ($usrAddCity->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $usrAddCity->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $usrAddState->getCaption(); ?>
                                <?php if ($usrAddState->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $usrAddState->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $usrAddType->getCaption(); ?>
                                <?php if ($usrAddType->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $usrAddType->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $usrAddZipcode->getCaption(); ?>
                                <?php if ($usrAddZipcode->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $usrAddZipcode->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php $usrAddDefault->developerTags['cbHtmlBeforeCheckbox'] = '<span class="checkbox">';
                                $usrAddDefault->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i></span>';
                                echo $usrAddDefault->getCaption(); ?>
                                <?php if ($usrAddDefault->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $usrAddDefault->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="field-set">
                        <div class="field-wraper form-buttons-group">
                            <div>
                                <?php echo $submitBtn->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </form>
            <?php echo $frm->getExternalJS(); ?>
        </div>

    </div>
</div>
<script>
    var lat = <?php echo empty($usrAddLatitude->value) ? "40.7259931" : $usrAddLatitude->value; ?>;
    var lng = <?php echo empty($usrAddLongitude->value) ? "-74.0019471" : $usrAddLongitude->value; ?>;
    var name = '<?php echo $usrAddPlaceName->value; ?>';
    var address = '<?php echo htmlspecialchars($usrAddresses->value, ENT_QUOTES); ?>';
    var marker = null;
    var infowindow = null;

    function initMap() {
        var latlng = new google.maps.LatLng(lat, lng);
        const input = document.getElementById("google-autocomplete");
        const options = {
            fields: ["formatted_address", "geometry", "name", "place_id", "address_components"],
            strictBounds: false,
        };

        $('#map').html('');
        var mapOpt = (address == '' || address == null) ? {
            scrollwheel: false,
            navigationControl: false,
            mapTypeControl: false,
            scaleControl: false,
            draggable: false,
        } : {
            center: {
                lat: lat,
                lng: lng
            },
            zoom: 16,
            mapTypeControl: true,
        };
        const map = new google.maps.Map(document.getElementById("map"), mapOpt);
        marker = new google.maps.Marker({
            map: map,
            draggable: true,
            position: latlng,
            anchorPoint: new google.maps.Point(0, -29)
        });

        infowindow = new google.maps.InfoWindow({
            content: address,
            ariaLabel: name,
        });

        setMarker(latlng);
        const autocomplete = new google.maps.places.Autocomplete(input, options);
        autocomplete.setTypes(['establishment']);
        autocomplete.addListener("place_changed", () => {
            const place = autocomplete.getPlace();
            setMarker(place.geometry.location);
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }
            setFormValues(place);
        });

    }

    function setFormValues(place) {
        var pin = city = street = state = country = '';
        for (var component of place.address_components) {
            var componentType = component.types[0];
            switch (componentType) {
                case "premise":
                case "sublocality_level_1":
                case "sublocality":
                case "political":
                case "street_number":
                case "route":
                    street += component.long_name + ', ';
                    break;
                case "postal_code": {
                    pin = component.long_name;
                    break;
                }
                case "locality": {
                    city = component.long_name;
                    break;
                }
                case "administrative_area_level_1": {
                    state = component.short_name;
                    break;
                }
                case "country": {
                    country = component.long_name;
                    break;
                }
            }
        }
        street = street.replace(/,\s*$/, "");
        document.frmAddressInfo.usradd_place_id.value = place.place_id;
        document.frmAddressInfo.usradd_place_name.value = (place.name == undefined) ? '' : place.name;
        document.frmAddressInfo.usradd_latitude.value = place.geometry.location.lat();
        document.frmAddressInfo.usradd_longitude.value = place.geometry.location.lng();
        document.frmAddressInfo.usradd_zipcode.value = pin;
        document.frmAddressInfo.usradd_address.value = street;
        document.frmAddressInfo.usradd_city.value = city;
        $('#google-autocomplete').val(place.formatted_address);
        setInfoWindow(place.formatted_address, place.name);
    }

    function setMarker(latlng) {
        marker.setPosition(latlng);
        marker.setVisible(true);
        marker.addListener('dragend', function() {
            geocodePosition(marker.getPosition());
        });

    }

    function setInfoWindow(address, name) {
        if (address == '') {
            return true;
        }
        infowindow.setContent(address);
        infowindow.setOptions(name);
        infowindow.close();
        infowindow.open(map, marker);
    }

    function geocodePosition(pos) {
        geocoder = new google.maps.Geocoder();
        geocoder.geocode({
            latLng: pos
        }, function(responses) {
            if (responses && responses.length > 0) {
                setFormValues(responses[0]);
            } else {
                alert('Cannot determine address at this location.');
            }
        });
    }

    window.initMap = initMap;
    $(document).ready(function() {
        window.initMap();
    });
</script>