/* =========================================================
   admin-map.js — Full WebGIS Map (admin mode: edit & draw)
   ========================================================= */

const API = APP_BASE + '/api';
const STAT_API = APP_BASE + '/api/statistik.php';

const state = {
    map: null,
    layers: {
        spbu: null, jalan: null, kavling: null,
        kawasan: null, rumah: null, warga: null,
        choropleth: null, blankSpot: null, spbuRoute: null
    },
    activeDraw: null,   // 'spbu' | 'jalan' | 'kavling' | 'kawasan' | 'rumah' | 'warga'
    drawHandlers: {},
    pendingGeometry: null
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

/* ── Icon factory ── */
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
    spbu24:  makeIcon('⛽', COLORS.spbu24),
    spbu:    makeIcon('⛽', COLORS.spbu),
    rumah:   makeIcon('🕌', COLORS.rumah),
    warga:   makeIcon('👤', COLORS.warga),
    blankSpot: makeIcon('⚠️', COLORS.blankSpot, 36)
};

/* ── Toast ── */
function toast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle')}" style="margin-right:8px;"></i>${msg}`;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
}

/* ── Initialize map ── */
function initMap() {
    state.map = L.map('admin-map', { zoomControl: false })
        .setView([-0.0583, 109.3448], 13);

    L.control.zoom({ position: 'bottomright' }).addTo(state.map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap, © CARTO',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(state.map);
}

/* ── Fetch GeoJSON ── */
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

/* ── Render: SPBU (Point) ── */
function renderSpbu(data) {
    if (state.layers.spbu) state.map.removeLayer(state.layers.spbu);
    state.layers.spbu = L.geoJSON(data, {
        pointToLayer: (f, ll) => L.marker(ll, { icon: f.properties.buka_24_jam ? ICONS.spbu24 : ICONS.spbu }),
        onEachFeature: (f, l) => bindPopup(l, 'spbu', f.properties)
    });
    if (document.getElementById('ly-spbu')?.checked) state.layers.spbu.addTo(state.map);
}

/* ── Render: Jalan (LineString) ── */
function renderJalan(data) {
    if (state.layers.jalan) state.map.removeLayer(state.layers.jalan);
    state.layers.jalan = L.geoJSON(data, {
        style: { color: COLORS.jalan, weight: 5, opacity: 0.85 },
        onEachFeature: (f, l) => bindPopup(l, 'jalan', f.properties)
    });
    if (document.getElementById('ly-jalan')?.checked) state.layers.jalan.addTo(state.map);
}

/* ── Render: Kavling (Polygon) ── */
function renderKavling(data) {
    if (state.layers.kavling) state.map.removeLayer(state.layers.kavling);
    state.layers.kavling = L.geoJSON(data, {
        style: { color: COLORS.kavling, fillColor: COLORS.kavling, fillOpacity: 0.3, weight: 2 },
        onEachFeature: (f, l) => bindPopup(l, 'kavling', f.properties)
    });
    if (document.getElementById('ly-kavling')?.checked) state.layers.kavling.addTo(state.map);
}

/* ── Render: Kawasan Kumuh (Polygon) ── */
function renderKawasan(data) {
    if (state.layers.kawasan) state.map.removeLayer(state.layers.kawasan);
    state.layers.kawasan = L.geoJSON(data, {
        style: { color: COLORS.kawasan, fillColor: COLORS.kawasan, fillOpacity: 0.25, weight: 2, dashArray: '6 4' },
        onEachFeature: (f, l) => bindPopup(l, 'kawasan', f.properties)
    });
    if (document.getElementById('ly-kawasan')?.checked) state.layers.kawasan.addTo(state.map);
}

/* ── Render: Rumah Ibadah (Point) ── */
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
        bindPopup(circle, 'rumah', props);
        bindPopup(marker, 'rumah', props);
        state.layers.rumah.addLayer(circle);
        state.layers.rumah.addLayer(marker);
    });
    if (document.getElementById('ly-rumah')?.checked) state.layers.rumah.addTo(state.map);
}

/* ── Render: Warga Miskin (Point) ── */
function renderWarga(data) {
    if (state.layers.warga) state.map.removeLayer(state.layers.warga);
    state.layers.warga = L.geoJSON(data, {
        pointToLayer: (f, ll) => L.marker(ll, { icon: ICONS.warga }),
        onEachFeature: (f, l) => bindPopup(l, 'warga', f.properties)
    });
    if (document.getElementById('ly-warga')?.checked) state.layers.warga.addTo(state.map);
}

/* ── Render: Choropleth (kawasan + count warga) ── */
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
            const c = f.properties.jumlah_warga;
            l.bindPopup(`<div class="popup-title">${f.properties.nama_kawasan}</div>
                <div class="popup-row">Jumlah Warga: <strong>${c}</strong></div>
                <div class="popup-row">Total Tanggungan: <strong>${f.properties.total_tanggungan}</strong></div>`);
        }
    });
    if (document.getElementById('ly-choropleth')?.checked) state.layers.choropleth.addTo(state.map);
}

/* ── Render: Blank Spot (warga yang jauh dari rumah ibadah) ── */
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

/* ── Popup binding helper ── */
function bindPopup(layer, type, props) {
    let html = `<div class="popup-title">${props.nama || props.nama_pemilik || props.nama_kk || props.nama_kawasan || 'Tanpa Nama'}</div>`;
    if (type === 'spbu') {
        html += `<div class="popup-row">24 Jam: <strong style="color:${props.buka_24_jam ? '#10B981' : '#EF4444'}">${props.buka_24_jam ? 'Ya' : 'Tidak'}</strong></div>`;
        if (props.deskripsi) html += `<div class="popup-row">${props.deskripsi}</div>`;
    } else if (type === 'jalan') {
        html += `<div class="popup-row">Jenis: <strong>${props.jenis_jalan || '-'}</strong></div>`;
    } else if (type === 'kavling') {
        html += `<div class="popup-row">Status: <strong>${props.status_kepemilikan || '-'}</strong></div>`;
        html += `<div class="popup-row">Luas: <strong>${props.luas ? props.luas + ' m²' : '-'}</strong></div>`;
    } else if (type === 'rumah') {
        html += `<div class="popup-row">Agama: <strong>${props.agama || '-'}</strong></div>`;
        html += `<div class="popup-row">Radius Bantuan: <strong>${formatKm(props.radius_bantuan_km)} km</strong></div>`;
    } else if (type === 'warga') {
        html += `<div class="popup-row">Penghasilan: <strong>Rp ${Number(props.penghasilan || 0).toLocaleString('id-ID')}</strong></div>`;
        html += `<div class="popup-row">Tanggungan: <strong>${props.jumlah_tanggungan || 0} org</strong></div>`;
    }
    html += `<div class="popup-actions">
        <button class="btn btn-danger btn-sm" onclick="deleteFeature('${type}', ${props.id})"><i class="fas fa-trash"></i> Hapus</button>
        <button class="btn btn-ghost btn-sm" onclick="window.location.href=APP_BASE + '/admin/data_${type === 'kavling' ? 'kavling' : (type === 'kawasan' ? 'kawasan' : (type === 'rumah' ? 'rumah' : (type === 'warga' ? 'warga' : type)))}.php'"><i class="fas fa-edit"></i> Kelola</button>
    </div>`;
    layer.bindPopup(html);
}

/* ── Delete from map ── */
window.deleteFeature = async function (type, id) {
    if (!confirm('Yakin hapus data ini?')) return;
    const ep = { spbu: 'spbu.php', jalan: 'jalan.php', kavling: 'kavling.php', kawasan: 'kawasan_kumuh.php', rumah: 'rumah_ibadah.php', warga: 'warga_miskin.php' }[type];
    if (!ep) return;
    const r = await fetch(`${API}/${ep}?id=${id}`, { method: 'DELETE' });
    const j = await r.json();
    if (j.status === 'success') { toast('Data dihapus!'); loadAll(); }
    else toast(j.message, 'error');
};

/* ── Layer toggling ── */
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

/* ── Drawing tool activation ── */
function activateDraw(type) {
    if (state.activeDraw) deactivateDraw();
    state.activeDraw = type;

    // Update UI button state
    document.querySelectorAll('[data-draw]').forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-draw="${type}"]`)?.classList.add('active');

    // Create appropriate L.Draw handler
    let handler = null;
    if (type === 'spbu' || type === 'rumah' || type === 'warga') {
        handler = new L.Draw.Marker(state.map);
    } else if (type === 'jalan') {
        handler = new L.Draw.Polyline(state.map, { shapeOptions: { color: COLORS.jalan, weight: 5 } });
    } else if (type === 'kavling' || type === 'kawasan') {
        handler = new L.Draw.Polygon(state.map, { shapeOptions: { color: type === 'kavling' ? COLORS.kavling : COLORS.kawasan } });
    }
    state.drawHandlers[type] = handler;
    handler.enable();
}

/* ── Deactivate drawing ── */
function deactivateDraw() {
    if (state.activeDraw && state.drawHandlers[state.activeDraw]) {
        state.drawHandlers[state.activeDraw].disable();
    }
    state.activeDraw = null;
    document.querySelectorAll('[data-draw]').forEach(b => b.classList.remove('active'));
}

/* ── Draw event listener ── */
function bindDrawEvent() {
    state.map.on(L.Draw.Event.CREATED, (e) => {
        const geom = e.layer.toGeoJSON().geometry;
        state.pendingGeometry = geom;
        openFormModal(state.activeDraw, geom);
    });

    state.map.on(L.Draw.Event.DRAWSTOP, () => {
        // User cancelled drawing
        document.querySelectorAll('[data-draw]').forEach(b => b.classList.remove('active'));
        state.activeDraw = null;
    });
}

/* ── Form modal untuk input data baru ── */
function openFormModal(type, geometry) {
    const modal = document.getElementById('formModal');
    const title = document.getElementById('formModalTitle');
    const body = document.getElementById('formModalBody');

    const titles = { spbu: '⛽ Tambah SPBU', jalan: '🛣️ Tambah Jalan', kavling: '🏘️ Tambah Kavling',
                     kawasan: '⚠️ Tambah Kawasan Kumuh', rumah: '🕌 Tambah Rumah Ibadah', warga: '👤 Tambah Warga Miskin' };
    title.textContent = titles[type] || 'Tambah Data';

    let formHtml = '';
    if (type === 'spbu') {
        formHtml = `
            <div class="form-group"><label class="form-label">Nama SPBU *</label><input type="text" id="f-nama" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Deskripsi</label><textarea id="f-deskripsi" class="form-control"></textarea></div>
            <div class="form-group"><label class="form-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" id="f-24" style="accent-color:var(--primary);"> Buka 24 Jam</label></div>`;
    } else if (type === 'jalan') {
        formHtml = `
            <div class="form-group"><label class="form-label">Nama Jalan *</label><input type="text" id="f-nama" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Jenis Jalan</label>
                <select id="f-jenis" class="form-control">
                    <option>Jalan Arteri</option><option>Jalan Kolektor</option>
                    <option selected>Jalan Lokal</option><option>Jalan Lingkungan</option>
                </select></div>`;
    } else if (type === 'kavling') {
        formHtml = `
            <div class="form-group"><label class="form-label">Nama Pemilik *</label><input type="text" id="f-nama" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Status Kepemilikan *</label>
                <select id="f-status" class="form-control">
                    <option value="SHM">SHM</option><option value="HGB">HGB</option>
                    <option value="HGU">HGU</option><option value="HP">HP</option>
                </select></div>
            <div class="form-group"><label class="form-label">Luas (m²)</label><input type="number" id="f-luas" class="form-control" min="0" step="0.01"></div>`;
    } else if (type === 'kawasan') {
        formHtml = `
            <div class="form-group"><label class="form-label">Nama Kawasan *</label><input type="text" id="f-nama" class="form-control" required></div>`;
    } else if (type === 'rumah') {
        formHtml = `
            <div class="form-group"><label class="form-label">Nama *</label><input type="text" id="f-nama" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Agama *</label>
                <select id="f-agama" class="form-control">
                    <option>Islam</option><option>Kristen</option><option>Katolik</option>
                    <option>Hindu</option><option>Buddha</option><option>Konghucu</option>
                </select></div>
            <div class="form-group"><label class="form-label">Radius Bantuan (meter) *</label><input type="number" id="f-radius" class="form-control" min="100" max="10000" step="50" value="1000" required></div>`;
    } else if (type === 'warga') {
        formHtml = `
            <div class="form-group"><label class="form-label">Nama Kepala Keluarga *</label><input type="text" id="f-nama" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Penghasilan/Bln (Rp)</label><input type="number" id="f-penghasilan" class="form-control" min="0"></div>
            <div class="form-group"><label class="form-label">Jumlah Tanggungan</label><input type="number" id="f-tanggungan" class="form-control" min="0" max="20"></div>`;
    }

    body.innerHTML = formHtml + `
        <div class="modal-footer" style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
            <button class="btn btn-ghost" onclick="closeFormModal()">Batal</button>
            <button class="btn btn-primary" id="btn-simpan" onclick="saveFeature('${type}')"><i class="fas fa-save"></i> Simpan</button>
        </div>`;

    modal.classList.add('active');
}

window.closeFormModal = function () {
    document.getElementById('formModal').classList.remove('active');
    state.pendingGeometry = null;
    deactivateDraw();
};

/* ── Save feature to backend ── */
window.saveFeature = async function (type) {
    const ep = { spbu: 'spbu.php', jalan: 'jalan.php', kavling: 'kavling.php', kawasan: 'kawasan_kumuh.php', rumah: 'rumah_ibadah.php', warga: 'warga_miskin.php' }[type];
    if (!ep) return;

    let payload = { geometry: state.pendingGeometry };
    const v = (id) => document.getElementById(id)?.value;

    if (type === 'spbu') {
        if (!v('f-nama')) return toast('Nama wajib!', 'error');
        payload = { ...payload, nama: v('f-nama'), deskripsi: v('f-deskripsi') || '', buka_24_jam: document.getElementById('f-24').checked ? 1 : 0 };
    } else if (type === 'jalan') {
        if (!v('f-nama')) return toast('Nama wajib!', 'error');
        payload = { ...payload, nama: v('f-nama'), jenis_jalan: v('f-jenis') };
    } else if (type === 'kavling') {
        if (!v('f-nama')) return toast('Nama wajib!', 'error');
        payload = { ...payload, nama_pemilik: v('f-nama'), status_kepemilikan: v('f-status'), luas: parseFloat(v('f-luas')) || 0 };
    } else if (type === 'kawasan') {
        if (!v('f-nama')) return toast('Nama wajib!', 'error');
        payload = { ...payload, nama_kawasan: v('f-nama') };
    } else if (type === 'rumah') {
        if (!v('f-nama')) return toast('Nama wajib!', 'error');
        payload = { ...payload, nama: v('f-nama'), agama: v('f-agama'), radius_bantuan_meter: Math.max(100, Math.min(10000, parseInt(v('f-radius'), 10) || 1000)) };
    } else if (type === 'warga') {
        if (!v('f-nama')) return toast('Nama wajib!', 'error');
        payload = { ...payload, nama_kk: v('f-nama'), penghasilan: parseFloat(v('f-penghasilan')) || 0, jumlah_tanggungan: parseInt(v('f-tanggungan')) || 0 };
    }

    try {
        const r = await fetch(`${API}/${ep}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const j = await r.json();
        if (j.status === 'success') { toast('Data berhasil disimpan!'); closeFormModal(); loadAll(); }
        else toast(j.message || 'Gagal menyimpan', 'error');
    } catch (e) { toast('Error: ' + e.message, 'error'); }
};

/* ── Analysis: SPBU Terdekat ── */
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
            const html = `<div class="spbu-result">
                <div class="spbu-result-name">⛽ ${p.nama}</div>
                <div class="spbu-result-meta">${p.buka_24_jam ? '🟢 24 Jam' : '🟡 Terbatas'}</div>
                <div class="spbu-result-distance">${p.jarak_km} km</div>
            </div>`;
            document.getElementById('spbuPanelResult').innerHTML = html;

            // Show marker
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

/* ── Load all layers ── */
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

        // Update quick stats
        if (stat && stat.counts) {
            document.getElementById('qs-spbu').textContent  = stat.counts.total_spbu;
            document.getElementById('qs-jalan').textContent = stat.counts.total_jalan;
            document.getElementById('qs-kavling').textContent = stat.counts.total_kavling;
            document.getElementById('qs-warga').textContent  = stat.counts.total_warga_miskin;
        }
    } catch (e) {
        toast('Gagal load data: ' + e.message, 'error');
    } finally {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    initMap();
    loadAll();
    bindLayerToggles();
    bindDrawEvent();

    document.querySelectorAll('[data-draw]').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.draw;
            if (state.activeDraw === type) deactivateDraw();
            else activateDraw(type);
        });
    });

    document.getElementById('btn-find-spbu')?.addEventListener('click', findNearestSpbu);
    document.getElementById('btn-close-spbu')?.addEventListener('click', () => {
        document.getElementById('spbuPanel').style.display = 'none';
        if (state.layers.spbuRoute) { state.map.removeLayer(state.layers.spbuRoute); state.layers.spbuRoute = null; }
    });
});
