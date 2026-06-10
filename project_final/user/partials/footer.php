    </div><!-- /admin-content -->
</main><!-- /admin-main -->

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ── Clock ──
function updateClock() {
    const now = new Date();
    document.getElementById('clockText').textContent =
        now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
}
updateClock();
setInterval(updateClock, 1000);

// ── Toast utility ──
window.showToast = function(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    const icons = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
    t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}" style="margin-right:8px;"></i>${msg}`;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
};

// ── Modal helpers ──
window.openModal = function(id) {
    document.getElementById(id)?.classList.add('active');
    document.body.style.overflow = 'hidden';
};
window.closeModal = function(id) {
    document.getElementById(id)?.classList.remove('active');
    document.body.style.overflow = '';
};

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal(overlay.id);
    });
});

// ── Search filter ──
window.filterTable = function(inputId, tableId) {
    const q = document.getElementById(inputId)?.value.toLowerCase() || '';
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
};

// Realtime SPBU snackbar
(function initSpbuRealtimeSnackbar() {
    const API_URL = APP_BASE + '/api/spbu_status.php';
    const REFRESH_MS = 60000;
    const SHOW_MS = 7000;
    let lastOpen = null;
    let hideTimer = null;

    function ensureSnackbar() {
        let el = document.getElementById('spbuRealtimeSnackbar');
        if (el) return el;

        el = document.createElement('div');
        el.id = 'spbuRealtimeSnackbar';
        el.className = 'spbu-snackbar';
        el.innerHTML = `
            <div class="spbu-snackbar-icon"><i class="fas fa-gas-pump"></i></div>
            <div>
                <div class="spbu-snackbar-title" id="spbuSnackbarTitle">SPBU sedang buka</div>
                <div class="spbu-snackbar-meta" id="spbuSnackbarMeta">Memuat status terbaru...</div>
            </div>
            <div class="spbu-snackbar-count" id="spbuSnackbarCount">-</div>
        `;
        document.body.appendChild(el);
        return el;
    }

    function showSnackbar(data) {
        const el = ensureSnackbar();
        const open = Number(data.spbu_buka || 0);
        const total = Number(data.total_spbu || 0);
        const closed = Number(data.spbu_tutup || Math.max(0, total - open));
        const time = data.server_time ? data.server_time.slice(11, 16) : new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        document.getElementById('spbuSnackbarTitle').textContent = `${open} SPBU sedang buka`;
        document.getElementById('spbuSnackbarMeta').textContent = `${closed} tutup/terbatas dari ${total} total - update ${time} WIB`;
        document.getElementById('spbuSnackbarCount').textContent = open;

        el.classList.add('show');
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => el.classList.remove('show'), SHOW_MS);
    }

    async function refreshStatus(forceShow = false) {
        try {
            const res = await fetch(API_URL, { cache: 'no-store' });
            const json = await res.json();
            if (json.status !== 'success' || !json.data) return;

            const open = Number(json.data.spbu_buka || 0);
            if (forceShow || lastOpen === null || open !== lastOpen) {
                showSnackbar(json.data);
            }
            lastOpen = open;
        } catch (e) {
            // Snackbar is informational; keep the page quiet if polling fails.
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        refreshStatus(true);
        setInterval(() => refreshStatus(false), REFRESH_MS);
    });
})();
</script>
<?= $extraScript ?? '' ?>
</body>
</html>
