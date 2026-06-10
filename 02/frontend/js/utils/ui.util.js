export const showToast = (message, type = 'success') => {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

export const clearFormContainer = () => {
    const container = document.getElementById('form-container');
    if (container) {
        container.innerHTML = '';
        container.style.display = 'none';
    }
};

export const renderResultPanel = (wargaList, radiusKm) => {
    const panel = document.getElementById('result-panel');
    const list = document.getElementById('result-list');
    const title = document.getElementById('result-title');
    
    panel.style.display = 'block';
    title.innerHTML = `Warga Miskin <br><span style="font-size:0.8rem;color:var(--text-muted);font-weight:normal;">dalam radius ${radiusKm} km</span>`;
    
    if (wargaList.length === 0) {
        list.innerHTML = `<p style="font-size:0.85rem;color:var(--text-muted);">Tidak ada data dalam radius ini.</p>`;
        return;
    }

    list.innerHTML = wargaList.map(w => `
        <div class="warga-item">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <strong>${w.nama_kk}</strong>
                <span class="badge-distance">${w.jarak_km} km</span>
            </div>
            <div style="color:var(--text-muted);">Rp ${w.penghasilan.toLocaleString('id-ID')} | ${w.jumlah_tanggungan} orang</div>
        </div>
    `).join('');
};

export const hideResultPanel = () => {
    document.getElementById('result-panel').style.display = 'none';
};
