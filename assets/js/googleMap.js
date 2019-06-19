function initMap() {
    let centerLocation = {lat: 0.0, lng: 0.0};
    let map = new google.maps.Map(document.getElementById('map'), {
        zoom: 10,
        center: centerLocation
    });
    let marker = new google.maps.Marker({
        position: centerLocation,
        map: map
    });
}