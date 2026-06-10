export const renderForm = (type, geometry, onSubmit, onCancel) => {
    const container = document.getElementById('form-container');
    container.style.display = 'flex';
    
    const extraFields = type === 'spbu' ? `
        <div class="form-group">
            <label class="checkbox-group">
                <input type="checkbox" id="input-24jam">
                <span>Buka 24 Jam</span>
            </label>
        </div>
    ` : '';

    container.innerHTML = `
        <div class="form-panel">
            <h3 style="margin:0 0 10px 0; font-size:1rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:8px;">Simpan Data (${type.toUpperCase()})</h3>
            <div class="form-group">
                <input type="text" id="input-nama" class="form-control" placeholder="Nama / Label" required>
            </div>
            <div class="form-group">
                <input type="text" id="input-deskripsi" class="form-control" placeholder="Deskripsi Singkat">
            </div>
            ${extraFields}
            <div style="display:flex; gap:10px; margin-top:8px;">
                <button id="btn-save" class="btn-submit" style="flex:1;">Simpan</button>
                <button id="btn-cancel" class="btn-submit" style="background:rgba(255,255,255,0.1); color:var(--text-light); flex:1;">Batal</button>
            </div>
        </div>
    `;

    document.getElementById('btn-save').addEventListener('click', () => {
        const payload = {
            type,
            geometry,
            nama: document.getElementById('input-nama').value,
            deskripsi: document.getElementById('input-deskripsi').value,
        };
        if (type === 'spbu') payload.buka_24_jam = document.getElementById('input-24jam').checked;
        
        onSubmit(payload);
        container.style.display = 'none';
        container.innerHTML = '';
    });

    document.getElementById('btn-cancel').addEventListener('click', () => {
        if(onCancel) onCancel();
        container.style.display = 'none';
        container.innerHTML = '';
    });
};
