/**
 * ui.util.js
 * Tanggung Jawab: Manipulasi DOM umum seperti Toast notification.
 */

export const showToast = (message, type = 'success') => {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    // Hapus toast setelah 3 detik
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
