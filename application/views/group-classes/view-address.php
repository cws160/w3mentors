<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_GROUP_CLASS/PACKAGE_ADDRESS'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <div id="map" style="aspect-ratio:2/1"></div>
</div>
<script>
    var lat = <?php echo $address['usradd_latitude'] ?? 0 ?>;
    var lng = <?php echo $address['usradd_longitude'] ?? 0 ?>;
    var address = '<?php echo htmlspecialchars(UserAddresses::format($address), ENT_QUOTES); ?>';
    window.initMap = initMap;
    $(document).ready(function() {
        window.initMap();
    });

    function initMap() {
        var latlng = new google.maps.LatLng(lat, lng);
        const map = new google.maps.Map(document.getElementById("map"), {
            center: {
                lat: lat,
                lng: lng
            },
            zoom: 20,
            mapTypeControl: true,
        });
        marker = new google.maps.Marker({
            map: map,
            position: latlng,
            anchorPoint: new google.maps.Point(0, -29)
        });
        infowindow = new google.maps.InfoWindow({
            content: address,
        });
        setMarker(latlng);
        setInfoWindow(address);
    }

    function setMarker(latlng) {
        marker.setPosition(latlng);
        marker.setVisible(true);
    }

    function setInfoWindow(address) {
        if (address == '') {
            return true;
        }
        infowindow.setContent(address);
        infowindow.close();
        infowindow.open(map, marker);
    }
</script>