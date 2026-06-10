/* =========================================================
   user-map.js — Read-only WebGIS Map (user mode)
   ========================================================= */

const API = APP_BASE + '/api';
const STAT_API = APP_BASE + '/api/statistik.php';

const state = {
    map: null,
    layers: {
        spbu: null, jalan: null, kavling: null,
        kawasan: null, rumah: null, warga: null,
        choropleth: null, blankSpot: null, spbuRoute: null
    }
};

const COLORS = {
    spbu:    '#F59E0B',
    spbu24:  '#10B981',
    jalan:   '#3B82F6',
    kavling: '#8B5CF6',
    kawasan: '#EF4444',
    rumah:   '#A855F7',
    warga:   '#EF4444',
    blankSpot: '#F97316'
};

function makeIcon(emoji, bgColor, size = 34) {
    return L.divIcon({
        className: 'div-icon-wrap',
        html: `<div class="marker-pin" style="background:${bgColor};width:${size}px;height:${size}px;"><span>${emoji}</span></div>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size],
        popupAnchor: [0, -size]
    });
}

const ICONS = {
    spbu24: makeIcon('⛽', COLORS.spbu24),
    spbu:   makeIcon('⛽', COLORS.spbu),
    rumah:  makeIcon('🕌', COLORS.rumah),
    warga:  makeIcon('👤', COLORS.warga),
    blankSpot: makeIcon('⚠️', COLORS.blankSpot, 36)
};

function toast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}" style="margin-right:8px;"></i>${msg}`;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
}

function initMap() {
    state.map = L.map('user-map', { zoomControl: false })
        .setView([-0.0583, 109.3448], 13);

    L.control.zoom({ position: 'bottomright' }).addTo(state.map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap, © CARTO',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(state.map);
}

async function fetchLayer(path) {
    const r = await fetch(`${API}/${path}`);
    const j = await r.json();
    return j.status === 'success' ? j.data : { type: 'FeatureCollection', features: [] };
}

function formatKm(value) {
    return Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

function bindReadOnlyPopup(layer, type, props) {
    let html = `<div class="popup-title">${props.nama || props.nama_pemilik || props.nama_kk || props.nama_kawasan || 'Tanpa Nama'}</div>`;
    if (type === 'spbu') {
        html += `<div class="popup-row">24 Jam: <strong style="color:${props.buka_24_jam ? '#10B981' : '#EF4444'}">${props.buka_24_jam ? 'Ya' : 'Tidak'}</strong></div>`;
        if (props.deskripsi) html += `<div class="popup-row">${props.deskripsi}</div>`;
        html += `<div class="popup-row">Rating: <strong>${props.avg_rating > 0 ? '⭐ ' + props.avg_rating + ' (' + props.total_ulasan + ' ulasan)' : 'Belum ada ulasan'}</strong></div>`;
        html += `<button class="btn btn-primary btn-sm" style="margin-top:10px;width:100%;" onclick="bukaReview('spbu', ${props.id}, '${props.nama}')">Beri Ulasan</button>`;
    } else if (type === 'jalan') {
        html += `<div class="popup-row">Jenis: <strong>${props.jenis_jalan || '-'}</strong></div>`;
    } else if (type === 'kavling') {
        html += `<div class="popup-row">Status: <strong>${props.status_kepemilikan || '-'}</strong></div>`;
        html += `<div class="popup-row">Luas: <strong>${props.luas ? props.luas + ' m²' : '-'}</strong></div>`;
    } else if (type === 'rumah') {
        html += `<div class="popup-row">Agama: <strong>${props.agama || '-'}</strong></div>`;
        html += `<div class="popup-row">Radius Bantuan: <strong>${formatKm(props.radius_bantuan_km)} km</strong></div>`;
        html += `<div class="popup-row">Rating: <strong>${props.avg_rating > 0 ? '⭐ ' + props.avg_rating + ' (' + props.total_ulasan + ' ulasan)' : 'Belum ada ulasan'}</strong></div>`;
        html += `<button class="btn btn-primary btn-sm" style="margin-top:10px;width:100%;" onclick="bukaReview('rumah_ibadah', ${props.id}, '${props.nama}')">Beri Ulasan</button>`;
    } else if (type === 'warga') {
        html += `<div class="popup-row">Penghasilan: <strong>Rp ${Number(props.penghasilan || 0).toLocaleString('id-ID')}</strong></div>`;
        html += `<div class="popup-row">Tanggungan: <strong>${props.jumlah_tanggungan || 0} org</strong></div>`;
    }
    layer.bindPopup(html);
}

// Ulasan Modal Logic
window.bukaReview = function(tipe, id, nama) {
    let m = document.getElementById('modalReview');
    if(!m) {
        m = document.createElement('div');
        m.id = 'modalReview';
        m.className = 'modal-overlay';
        m.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">⭐ Beri Ulasan</span>
                <button class="modal-close" onclick="document.getElementById('modalReview').style.display='none'">×</button>
            </div>
            <div style="padding:15px;">
                <h4 id="revNama" style="margin-top:0; color:var(--text-primary);"></h4>
                <input type="hidden" id="revTipe"><input type="hidden" id="revId">
                <div class="form-group">
                    <label class="form-label">Rating (1-5)</label>
                    <select id="revRating" class="form-control">
                        <option value="5">⭐⭐⭐⭐⭐ Sangat Baik</option>
                        <option value="4">⭐⭐⭐⭐ Baik</option>
                        <option value="3">⭐⭐⭐ Cukup</option>
                        <option value="2">⭐⭐ Buruk</option>
                        <option value="1">⭐ Sangat Buruk</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Komentar</label>
                    <textarea id="revKomen" class="form-control" rows="3" placeholder="Tuliskan pengalaman Anda..."></textarea>
                </div>
                <button class="btn btn-primary" style="width:100%" onclick="submitReview()">Kirim Ulasan</button>
            </div>
        </div>`;
        document.body.appendChild(m);
    }
    
    document.getElementById('revTipe').value = tipe;
    document.getElementById('revId').value = id;
    document.getElementById('revNama').innerText = "Mengulas: " + nama;
    document.getElementById('revRating').value = "5";
    document.getElementById('revKomen').value = "";
    m.style.display = 'flex';
};

window.submitReview = async function() {
    const tipe = document.getElementById('revTipe').value;
    const id = document.getElementById('revId').value;
    const rating = document.getElementById('revRating').value;
    const komen = document.getElementById('revKomen').value;
    
    const res = await fetch(APP_BASE + '/api/ulasan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fasilitas_tipe: tipe, fasilitas_id: id, rating: rating, komentar: komen })
    });
    const j = await res.json();
    if(j.status === 'success') {
        toast('Ulasan berhasil dikirim!');
        document.getElementById('modalReview').style.display = 'none';
        loadAll(); // reload data
    } else {
        toast(j.message, 'error');
    }
};

function renderSpbu(data) {
    if (state.layers.spbu) state.map.removeLayer(state.layers.spbu);
    state.layers.spbu = L.geoJSON(data, {
        pointToLayer: (f, ll) => L.marker(ll, { icon: f.properties.buka_24_jam ? ICONS.spbu24 : ICONS.spbu }),
        onEachFeature: (f, l) => bindReadOnlyPopup(l, 'spbu', f.properties)
    });
    if (document.getElementById('ly-spbu')?.checked) state.layers.spbu.addTo(state.map);
}

function renderJalan(data) {
    if (state.layers.jalan) state.map.removeLayer(state.layers.jalan);
    state.layers.jalan = L.geoJSON(data, {
        style: { color: COLORS.jalan, weight: 5, opacity: 0.85 },
        onEachFeature: (f, l) => bindReadOnlyPopup(l, 'jalan', f.properties)
    });
    if (document.getElementById('ly-jalan')?.checked) state.layers.jalan.addTo(state.map);
}

function renderKavling(data) {
    if (state.layers.kavling) state.map.removeLayer(state.layers.kavling);
    state.layers.kavling = L.geoJSON(data, {
        style: { color: COLORS.kavling, fillColor: COLORS.kavling, fillOpacity: 0.3, weight: 2 },
        onEachFeature: (f, l) => bindReadOnlyPopup(l, 'kavling', f.properties)
    });
    if (document.getElementById('ly-kavling')?.checked) state.layers.kavling.addTo(state.map);
}

function renderKawasan(data) {
    if (state.layers.kawasan) state.map.removeLayer(state.layers.kawasan);
    state.layers.kawasan = L.geoJSON(data, {
        style: { color: COLORS.kawasan, fillColor: COLORS.kawasan, fillOpacity: 0.25, weight: 2, dashArray: '6 4' },
        onEachFeature: (f, l) => bindReadOnlyPopup(l, 'kawasan', f.properties)
    });
    if (document.getElementById('ly-kawasan')?.checked) state.layers.kawasan.addTo(state.map);
}

function renderRumah(data) {
    if (state.layers.rumah) state.map.removeLayer(state.layers.rumah);
    state.layers.rumah = L.featureGroup();
    (data.features || []).forEach((f) => {
        if (!f.geometry || f.geometry.type !== 'Point') return;
        const props = f.properties || {};
        const ll = L.latLng(f.geometry.coordinates[1], f.geometry.coordinates[0]);
        const circle = L.circle(ll, {
            radius: Number(props.radius_bantuan_meter || 1000),
            color: COLORS.rumah,
            fillColor: COLORS.rumah,
            fillOpacity: 0.08,
            opacity: 0.55,
            weight: 2
        });
        const marker = L.marker(ll, { icon: ICONS.rumah });
        bindReadOnlyPopup(circle, 'rumah', props);
        bindReadOnlyPopup(marker, 'rumah', props);
        state.layers.rumah.addLayer(circle);
        state.layers.rumah.addLayer(marker);
    });
    if (document.getElementById('ly-rumah')?.checked) state.layers.rumah.addTo(state.map);
}

function renderWarga(data) {
    if (state.layers.warga) state.map.removeLayer(state.layers.warga);
    state.layers.warga = L.geoJSON(data, {
        pointToLayer: (f, ll) => L.marker(ll, { icon: ICONS.warga }),
        onEachFeature: (f, l) => bindReadOnlyPopup(l, 'warga', f.properties)
    });
    if (document.getElementById('ly-warga')?.checked) state.layers.warga.addTo(state.map);
}

function renderChoropleth(data) {
    if (state.layers.choropleth) state.map.removeLayer(state.layers.choropleth);
    state.layers.choropleth = L.geoJSON(data, {
        style: (f) => {
            const c = f.properties.jumlah_warga;
            let color = '#10B981';
            if (c > 0) color = '#F59E0B';
            if (c > 3) color = '#EF4444';
            return { color, fillColor: color, fillOpacity: 0.35, weight: 3 };
        },
        onEachFeature: (f, l) => {
            l.bindPopup(`<div class="popup-title">${f.properties.nama_kawasan}</div>
                <div class="popup-row">Jumlah Warga: <strong>${f.properties.jumlah_warga}</strong></div>
                <div class="popup-row">Total Tanggungan: <strong>${f.properties.total_tanggungan}</strong></div>`);
        }
    });
    if (document.getElementById('ly-choropleth')?.checked) state.layers.choropleth.addTo(state.map);
}

function renderBlankSpot(data) {
    if (state.layers.blankSpot) state.map.removeLayer(state.layers.blankSpot);
    state.layers.blankSpot = L.geoJSON(data, {
        pointToLayer: (f, ll) => L.marker(ll, { icon: ICONS.blankSpot }),
        onEachFeature: (f, l) => {
            l.bindPopup(`<div class="popup-title">⚠️ ${f.properties.nama_kk}</div>
                <div class="popup-row">Jarak ke RI terdekat: <strong>${formatKm(f.properties.jarak_km)} km</strong></div>
                <div class="popup-row">Radius bantuan: <strong>${formatKm(f.properties.radius_km)} km</strong></div>
                <div class="popup-row">Di luar radius: <strong>${formatKm(f.properties.selisih_km)} km</strong></div>
                <div class="popup-row">Rumah ibadah terdekat: <strong>${f.properties.rumah_ibadah_terdekat || '-'}</strong></div>
                <div class="popup-row" style="color:#F97316">Blank spot di luar radius bantuan</div>`);
        }
    });
    if (document.getElementById('ly-blankspot')?.checked) state.layers.blankSpot.addTo(state.map);
}

function bindLayerToggles() {
    const map = {
        'ly-spbu': 'spbu', 'ly-jalan': 'jalan', 'ly-kavling': 'kavling',
        'ly-kawasan': 'kawasan', 'ly-rumah': 'rumah', 'ly-warga': 'warga',
        'ly-choropleth': 'choropleth', 'ly-blankspot': 'blankSpot'
    };
    Object.entries(map).forEach(([id, key]) => {
        document.getElementById(id)?.addEventListener('change', (e) => {
            const layer = state.layers[key];
            if (!layer) return;
            if (e.target.checked) state.map.addLayer(layer);
            else state.map.removeLayer(layer);
        });
    });
}

async function findNearestSpbu() {
    const center = state.map.getCenter();
    document.getElementById('spbuPanelResult').innerHTML = '<div class="spinner"></div> Mencari...';
    document.getElementById('spbuPanel').style.display = 'block';

    try {
        const r = await fetch(`${API}/spbu_terdekat.php?lat=${center.lat}&lng=${center.lng}`);
        const j = await r.json();
        if (j.status === 'success' && j.data) {
            const p = j.data.properties;
            const coords = j.data.geometry.coordinates;
            document.getElementById('spbuPanelResult').innerHTML = `<div class="spbu-result">
                <div class="spbu-result-name">⛽ ${p.nama}</div>
                <div class="spbu-result-meta">${p.buka_24_jam ? '🟢 24 Jam' : '🟡 Terbatas'}</div>
                <div class="spbu-result-distance">${p.jarak_km} km</div>
            </div>`;
            if (state.layers.spbuRoute) state.map.removeLayer(state.layers.spbuRoute);
            state.layers.spbuRoute = L.marker([coords[1], coords[0]], { icon: ICONS.spbu }).addTo(state.map)
                .bindPopup(`<b>${p.nama}</b><br>${p.jarak_km} km dari pusat peta`).openPopup();
        } else {
            document.getElementById('spbuPanelResult').innerHTML = `<div class="spbu-result">${j.message || 'Tidak ada SPBU'}</div>`;
        }
    } catch (e) {
        document.getElementById('spbuPanelResult').innerHTML = `<div class="spbu-result">Error: ${e.message}</div>`;
    }
}

async function loadAll() {
    document.getElementById('loadingOverlay').style.display = 'flex';
    try {
        const [spbu, jalan, kavling, kawasan, rumah, warga, stat, blank] = await Promise.all([
            fetchLayer('spbu.php'),
            fetchLayer('jalan.php'),
            fetchLayer('kavling.php'),
            fetchLayer('kawasan_kumuh.php'),
            fetchLayer('rumah_ibadah.php'),
            fetchLayer('warga_miskin.php'),
            fetch(STAT_API).then(r => r.json()).then(j => j.data).catch(() => null),
            fetchLayer('blank_spot.php')
        ]);
        renderSpbu(spbu); renderJalan(jalan); renderKavling(kavling);
        renderKawasan(kawasan); renderRumah(rumah); renderWarga(warga);
        if (stat && stat.choropleth) renderChoropleth(stat.choropleth);
        if (blank) renderBlankSpot(blank);

        if (stat && stat.counts) {
            document.getElementById('qs-spbu').textContent    = stat.counts.total_spbu;
            document.getElementById('qs-jalan').textContent   = stat.counts.total_jalan;
            document.getElementById('qs-kavling').textContent = stat.counts.total_kavling;
            document.getElementById('qs-warga').textContent   = stat.counts.total_warga_miskin;
        }
    } catch (e) {
        toast('Gagal load data: ' + e.message, 'error');
    } finally {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initMap();
    loadAll();
    bindLayerToggles();

    document.getElementById('btn-find-spbu')?.addEventListener('click', findNearestSpbu);
    document.getElementById('btn-close-spbu')?.addEventListener('click', () => {
        document.getElementById('spbuPanel').style.display = 'none';
        if (state.layers.spbuRoute) { state.map.removeLayer(state.layers.spbuRoute); state.layers.spbuRoute = null; }
    });
});
