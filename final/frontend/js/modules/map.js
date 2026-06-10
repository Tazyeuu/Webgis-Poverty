/**
 * map.js
 * Tanggung Jawab: Inisialisasi peta Leaflet dasar dan base layer.
 */

export const initMap = (containerId) => {
    // Pusat peta default (Universitas Tanjungpura, Pontianak)
    const map = L.map(containerId, {
        zoomControl: false
    }).setView([-0.0583, 109.3448], 15);

    // Pindahkan zoom control ke kanan bawah
    L.control.zoom({
        position: 'bottomright'
    }).addTo(map);

    // Tile Layer Premium (CartoDB Dark Matter untuk Glass UI)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    return map;
};
