export const BASE_URL = '';

const createService = (endpoint) => ({
    getAll: async () => {
        const res = await fetch(`${BASE_URL}/${endpoint}`);
        const json = await res.json();
        return json.data;
    },
    create: async (data) => {
        const res = await fetch(`${BASE_URL}/${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        return json.data;
    },
    delete: async (id) => {
        const res = await fetch(`${BASE_URL}/${endpoint}?id=${id}`, {
            method: 'DELETE'
        });
        const json = await res.json();
        return json.data;
    }
});

// P01
export const spbuService = createService('01/backend/api/spbu.php');
export const jalanService = createService('01/backend/api/jalan.php');
export const kavlingService = createService('01/backend/api/kavling.php');

// P02
export const rumahIbadahService = createService('02/backend/api/rumah_ibadah.php');
export const wargaMiskinService = createService('02/backend/api/warga_miskin.php');
export const haversineService = {
    getDalamRadius: async (id, radius) => {
        const res = await fetch(`${BASE_URL}/02/backend/api/haversine.php?rumah_ibadah_id=${id}&radius_km=${radius}`);
        const json = await res.json();
        return json.data;
    }
};
