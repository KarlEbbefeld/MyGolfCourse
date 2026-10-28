/*
 * Gemeinsame Karte für den Admin: Satellitenbild (Esri World Imagery) mit Umschalter auf Straßenkarte (OpenStreetMap).
 * Nur im Admin verwendet. Vor einer kommerziellen Nutzung die Nutzungsbedingungen der Kachel-Anbieter prüfen.
 */
window.mgcMap = function (element, center) {
    const map = L.map(element, { maxZoom: 21, zoomControl: true });

    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxNativeZoom: 19,
        maxZoom: 21,
        attribution: 'Luftbild &copy; Esri, Maxar, Earthstar Geographics',
    });
    const streets = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxNativeZoom: 19,
        maxZoom: 21,
        attribution: '&copy; OpenStreetMap-Mitwirkende',
    });

    satellite.addTo(map);
    L.control.layers({ Satellit: satellite, Karte: streets }, {}, { position: 'topright' }).addTo(map);
    L.control.scale({ imperial: false }).addTo(map);
    map.setView([center.lat, center.lng], center.zoom);

    return map;
};

/** Haversine-Distanz in Metern (wie App\Core\Support\Geo). */
window.mgcDistance = function (a, b) {
    const rad = (d) => (d * Math.PI) / 180;
    const dLat = rad(b.lat - a.lat);
    const dLng = rad(b.lng - a.lng);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a.lat)) * Math.cos(rad(b.lat)) * Math.sin(dLng / 2) ** 2;
    return 2 * 6371000 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
};
