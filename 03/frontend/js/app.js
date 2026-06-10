import { initMap } from './modules/map.js';
import { setupDrawControls } from './modules/draw.js';
import { renderForm } from './modules/form.js';
import { spbuService, jalanService, kavlingService, rumahIbadahService, wargaMiskinService, BASE_URL } from './services/api.service.js';

let appMap;
let drawControl;

const statistikService = {
    getChoropleth: async () => {
        const res = await fetch(`${BASE_URL}/03/backend/api/statistik.php`);
        const json = await res.json();
        return json.data;
    }
};

export const createIcon = (svgPath, color) => L.divIcon({
    className: 'custom-icon',
    html: `<div style="background-color: ${color}; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5); border: 2px solid rgba(255,255,255,0.8); color: white;"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">${svgPath}</svg></div>`,
    iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -34]
});

const iconSPBU = (is24h) => createIcon('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>', is24h ? '#10B981' : '#EF4444');
const iconRumah = createIcon('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>', '#8B5CF6');
const iconWarga = createIcon('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>', '#EF4444');

window.showToast = (msg, type='success') => {
    const t = document.createElement('div'); t.className = `toast ${type}`; t.innerHTML = msg;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => { t.style.opacity=0; setTimeout(()=>t.remove(),300); }, 3000);
};

document.addEventListener('DOMContentLoaded', async () => {
    appMap = initMap('map');
    document.getElementById('btn-draw-rumah')?.addEventListener('click', () => { window.currentDrawType='rumah_ibadah'; new L.Draw.Marker(appMap).enable(); });
    document.getElementById('btn-draw-warga')?.addEventListener('click', () => { window.currentDrawType='warga_miskin'; new L.Draw.Marker(appMap).enable(); });
    drawControl = setupDrawControls(appMap, handleGeometryCreated);
    await loadAllData();
});

const loadAllData = async () => {
    try {
        const [spbu, jalan, choro, rumah, warga] = await Promise.all([
            spbuService.getAll(), jalanService.getAll(), statistikService.getChoropleth(),
            rumahIbadahService.getAll(), wargaMiskinService.getAll()
        ]);
        
        L.geoJSON(spbu, { pointToLayer: (f, ll) => L.marker(ll, { icon: iconSPBU(f.properties.buka_24_jam) }), onEachFeature: (f, l) => bindPopup(l, 'spbu', f.properties) }).addTo(appMap);
        L.geoJSON(jalan, { style: { color: '#F59E0B', weight: 4 }, onEachFeature: (f, l) => bindPopup(l, 'jalan', f.properties) }).addTo(appMap);
        L.geoJSON(rumah, { pointToLayer: (f, ll) => L.marker(ll, { icon: iconRumah }), onEachFeature: (f, l) => bindPopup(l, 'rumah_ibadah', f.properties) }).addTo(appMap);
        L.geoJSON(warga, { pointToLayer: (f, ll) => L.marker(ll, { icon: iconWarga }), onEachFeature: (f, l) => bindPopup(l, 'warga_miskin', f.properties) }).addTo(appMap);
        
        let merahCount = 0;
        L.geoJSON(choro, {
            style: (f) => {
                const count = f.properties.jumlah_warga_miskin;
                let c = '#10B981'; // Hijau (0)
                if(count > 0) c = '#F59E0B'; // Kuning (1-2)
                if(count > 2) { c = '#EF4444'; merahCount++; } // Merah (>2)
                return { color: c, fillColor: c, fillOpacity: 0.4, weight: 2 };
            },
            onEachFeature: (f, l) => bindPopup(l, 'kavling', f.properties)
        }).addTo(appMap);

        document.getElementById('stat-total-warga').innerText = warga.features.length;
        document.getElementById('stat-kavling-merah').innerText = merahCount;

    } catch (e) { window.showToast("Gagal meload data: "+e.message, 'error'); }
};

const bindPopup = (layer, type, props) => {
    let ext = '';
    if(type==='spbu') ext = `<p>24 Jam: <strong style="color:${props.buka_24_jam?'#10B981':'#EF4444'}">${props.buka_24_jam?'Ya':'Tidak'}</strong></p>`;
    if(type==='kavling') ext = `<p>Jml Warga Miskin: <strong>${props.jumlah_warga_miskin||0} Jiwa</strong></p>`;
    
    layer.bindPopup(`
        <div class="popup-custom">
            <h3>${props.nama}</h3>
            <p>${props.deskripsi || ''}</p>
            ${ext}
            <button class="btn-delete" onclick="window.deleteData('${type}', ${props.id})">Hapus</button>
        </div>
    `);
};

window.deleteData = async (type, id) => {
    if(!confirm('Yakin menghapus?')) return;
    try {
        if(type==='spbu') await spbuService.delete(id);
        if(type==='jalan') await jalanService.delete(id);
        if(type==='kavling') await kavlingService.delete(id);
        if(type==='rumah_ibadah') await rumahIbadahService.delete(id);
        if(type==='warga_miskin') await wargaMiskinService.delete(id);
        window.showToast('Data dihapus'); setTimeout(()=>location.reload(), 800);
    } catch(e) { window.showToast('Gagal hapus', 'error'); }
};

const handleGeometryCreated = (type, geometry, layer) => {
    const tempLayer = L.geoJSON(geometry).addTo(appMap);
    renderForm(type, geometry, async (payload) => {
        try {
            if(type==='spbu') await spbuService.create(payload);
            if(type==='jalan') await jalanService.create(payload);
            if(type==='kavling') await kavlingService.create(payload);
            if(type==='rumah_ibadah') await rumahIbadahService.create(payload);
            if(type==='warga_miskin') await wargaMiskinService.create(payload);
            window.showToast('Tersimpan'); setTimeout(()=>location.reload(), 800);
        } catch(e) { window.showToast('Gagal simpan', 'error'); }
    }, () => appMap.removeLayer(tempLayer));
};
