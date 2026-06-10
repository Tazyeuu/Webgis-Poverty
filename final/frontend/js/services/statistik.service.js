export const CONFIG = {
    BASE_URL: '/final/backend/api'
};

export const statistikService = {
    getKepadatan: async () => {
        const res = await fetch(`${CONFIG.BASE_URL}/statistik.php`);
        return await res.json();
    }
};
