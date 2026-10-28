/*
 * Platzformular: Lage des Platzes per Klick auf die Karte oder per Ortssuche (OpenStreetMap Nominatim).
 */
(() => {
    const element = document.querySelector('[data-course-map]');
    if (!element) return;

    const latInput = document.querySelector('[data-lat-input]');
    const lngInput = document.querySelector('[data-lng-input]');
    const lat = parseFloat(element.dataset.lat);
    const lng = parseFloat(element.dataset.lng);
    const hasPosition = Number.isFinite(lat) && Number.isFinite(lng);

    const map = window.mgcMap(element, hasPosition ? { lat, lng, zoom: 15 } : { lat: 51.16, lng: 10.45, zoom: 6 });
    let marker = hasPosition ? L.marker([lat, lng], { draggable: true }).addTo(map) : null;

    function setPosition(latlng) {
        latInput.value = latlng.lat.toFixed(7);
        lngInput.value = latlng.lng.toFixed(7);
        if (!marker) {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            marker.on('dragend', () => setPosition(marker.getLatLng()));
        } else {
            marker.setLatLng(latlng);
        }
    }

    marker?.on('dragend', () => setPosition(marker.getLatLng()));
    map.on('click', (event) => setPosition(event.latlng));

    const searchInput = document.querySelector('[data-map-search]');
    const searchButton = document.querySelector('[data-map-search-button]');

    async function search() {
        const query = searchInput.value.trim();
        if (!query) return;
        searchButton.disabled = true;
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&accept-language=de&q=${encodeURIComponent(query)}`);
            const results = await response.json();
            if (results[0]) {
                map.setView([parseFloat(results[0].lat), parseFloat(results[0].lon)], 16);
            } else {
                alert('Nichts gefunden. Anderen Suchbegriff versuchen oder die Karte von Hand verschieben.');
            }
        } catch {
            alert('Suche gerade nicht erreichbar – bitte die Karte von Hand verschieben.');
        } finally {
            searchButton.disabled = false;
        }
    }

    searchButton.addEventListener('click', search);
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            search();
        }
    });
})();
