// src/js/components/toast.js

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {'success'|'error'|'warning'|'info'} type - The type of toast
 * @param {number} duration - Duration in milliseconds before hiding
 */
export function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';
    if (type === 'warning') icon = '⚠️';

    toast.innerHTML = `
        <span class="icon" style="font-size: 1.25rem;">${icon}</span>
        <p style="margin: 0;">${message}</p>
    `;

    container.appendChild(toast);

    // Auto remove
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, duration);
}
