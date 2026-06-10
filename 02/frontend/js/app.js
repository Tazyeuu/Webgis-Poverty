import { initMap } from './modules/map.js';
import { setupDrawControls } from './modules/draw.js';
import { renderForm } from './modules/form.js';
import { spbuService, jalanService, kavlingService, rumahIbadahService, wargaMiskinService, haversineService } from './services/api.service.js';

let appMap;
let drawControl;
let currentRadiusCircle = null;
let currentHighlightLayer = null;

export const createIcon = (svgPath, color) => {
    return L.divIcon({
        className: 'custom-icon',
        html: `<div style="background-color: ${color}; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5); border: 2px solid rgba(255,255,255,0.8); color: white;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">${svgPath}</svg>
               </div>`,
        iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -34]
    });
};

const iconSPBU = (is24h) => createIcon('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>', is24h ? '#10B981' : '#EF4444');
const iconRumah = createIcon('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>', '#8B5CF6');
const iconWarga = createIcon('<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>', '#EF4444');

window.showToast = (msg, type='success') => {
    const t = document.createElement('div');
    t.className = `toast ${type}`; t.innerHTML = msg;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => { t.style.transform='translateX(100%)'; t.style.opacity=0; setTimeout(()=>t.remove(),300); }, 3000);
};

document.addEventListener('DOMContentLoaded', async () => {
    appMap = initMap('map');
    
    // Bind extra draw events for 02
    document.getElementById('btn-draw-rumah')?.addEventListener('click', () => { window.currentDrawType='rumah_ibadah'; new L.Draw.Marker(appMap).enable(); });
    document.getElementById('btn-draw-warga')?.addEventListener('click', () => { window.currentDrawType='warga_miskin'; new L.Draw.Marker(appMap).enable(); });
    
    drawControl = setupDrawControls(appMap, handleGeometryCreated);
    await loadAllData();
});

const loadAllData = async () => {
    try {
        const [spbu, jalan, kavling, rumah, warga] = await Promise.all([
            spbuService.getAll(), jalanService.getAll(), kavlingService.getAll(),
            rumahIbadahService.getAll(), wargaMiskinService.getAll()
        ]);
        
        L.geoJSON(spbu, { pointToLayer: (f, ll) => L.marker(ll, { icon: iconSPBU(f.properties.buka_24_jam) }), onEachFeature: (f, l) => bindPopup(l, 'spbu', f.properties) }).addTo(appMap);
        L.geoJSON(jalan, { style: { color: '#F59E0B', weight: 4 }, onEachFeature: (f, l) => bindPopup(l, 'jalan', f.properties) }).addTo(appMap);
        L.geoJSON(kavling, { style: { color: '#3B82F6', weight: 2, fillColor: '#3B82F6', fillOpacity: 0.3 }, onEachFeature: (f, l) => bindPopup(l, 'kavling', f.properties) }).addTo(appMap);
        L.geoJSON(rumah, { pointToLayer: (f, ll) => L.marker(ll, { icon: iconRumah }), onEachFeature: (f, l) => bindPopup(l, 'rumah_ibadah', f.properties, f) }).addTo(appMap);
        L.geoJSON(warga, { pointToLayer: (f, ll) => L.marker(ll, { icon: iconWarga }), onEachFeature: (f, l) => bindPopup(l, 'warga_miskin', f.properties) }).addTo(appMap);
    } catch (e) {
        window.showToast("Gagal meload data: "+e.message, 'error');
    }
};

const bindPopup = (layer, type, props, feature=null) => {
    let ext = '';
    if(type==='spbu') ext = `<p>24 Jam: <strong style="color:${props.buka_24_jam?'#10B981':'#EF4444'}">${props.buka_24_jam?'Ya':'Tidak'}</strong></p>`;
    if(type==='rumah_ibadah' && feature) ext = `<button class="btn-submit" style="width:100%; margin-top:8px;" onclick="window.checkRadius(${props.id}, ${feature.geometry.coordinates[1]}, ${feature.geometry.coordinates[0]})">Cek Radius 1km</button>`;
    
    layer.bindPopup(`
        <div class="popup-custom">
            <h3>${props.nama}</h3>
            <p>${props.deskripsi || ''}</p>
            ${ext}
            <button class="btn-delete" onclick="window.deleteData('${type}', ${props.id})">Hapus</button>
        </div>
    `);
};

window.checkRadius = async (rumahId, lat, lng) => {
    if (currentRadiusCircle) appMap.removeLayer(currentRadiusCircle);
    if (currentHighlightLayer) appMap.removeLayer(currentHighlightLayer);

    currentRadiusCircle = L.circle([lat, lng], { radius: 1000, color: '#8B5CF6', fillColor: '#8B5CF6', fillOpacity: 0.15, weight: 2 }).addTo(appMap);
    appMap.flyTo([lat, lng], 15);
    window.showToast('Menghitung jarak...');

    try {
        const res = await haversineService.getDalamRadius(rumahId, 1);
        const container = document.getElementById('radius-results');
        container.innerHTML = res.features.length ? '' : '<p>Tidak ada warga miskin.</p>';
        
        res.features.forEach(f => {
            const d = parseFloat(f.properties.jarak_km).toFixed(2);
            container.innerHTML += `<div class="result-item"><span>${f.properties.nama}</span><span class="badge">${d} km</span></div>`;
        });
        
        currentHighlightLayer = L.geoJSON(res, {
            pointToLayer: (f, ll) => L.circleMarker(ll, { radius:8, fillColor:'#EF4444', color:'#fff', weight:2, fillOpacity:1 })
        }).addTo(appMap);
        
    } catch(e) { window.showToast('Gagal kalkulasi', 'error'); }
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
