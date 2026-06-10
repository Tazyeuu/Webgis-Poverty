export const CONFIG = {
    BASE_URL: '/03/backend/api'
};

export const statistikService = {
    getKepadatan: async () => {
        const res = await fetch(`${CONFIG.BASE_URL}/statistik.php`);
        return await res.json();
    }
};
