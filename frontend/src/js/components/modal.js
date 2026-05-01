// src/js/components/modal.js

/**
 * Open a modal dialog
 * @param {Object} options
 * @param {string} options.title - Modal title
 * @param {string|HTMLElement} options.body - Modal content HTML string or DOM Node
 * @param {Array<{label: string, class: string, onClick: Function}>} options.actions - Modal buttons
 */
export function showModal({ title, body, actions = [] }) {
    const container = document.getElementById('modal-container');
    if (!container) return;

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal';

    // Header
    const header = document.createElement('div');
    header.className = 'modal-header';
    header.innerHTML = `
        <h3 class="modal-title font-bold">${title}</h3>
        <button class="modal-close">&times;</button>
    `;

    // Body
    const bodyEl = document.createElement('div');
    bodyEl.className = 'modal-body';
    if (typeof body === 'string') {
        bodyEl.innerHTML = body;
    } else if (body instanceof Node) {
        bodyEl.appendChild(body);
    }

    // Footer
    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    
    const closeBtn = document.createElement('button');
    closeBtn.className = 'btn btn-secondary';
    closeBtn.textContent = 'Cancel';
    closeBtn.onclick = closeModal;
    footer.appendChild(closeBtn);

    actions.forEach(action => {
        const btn = document.createElement('button');
        btn.className = `btn ${action.class || 'btn-primary'}`;
        btn.textContent = action.label;
        btn.onclick = (e) => {
            if (action.onClick) action.onClick(e, closeModal);
        };
        footer.appendChild(btn);
    });

    // Assemble
    modal.appendChild(header);
    modal.appendChild(bodyEl);
    modal.appendChild(footer);
    overlay.appendChild(modal);
    container.appendChild(overlay);

    // Event listeners
    header.querySelector('.modal-close').onclick = closeModal;
    overlay.onclick = (e) => {
        if (e.target === overlay) closeModal();
    };

    // Show modal
    // Trigger reflow to ensure animation works
    void overlay.offsetWidth;
    overlay.classList.add('active');

    function closeModal() {
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.remove();
        }, 300); // Wait for transition
    }
    
    return { closeModal };
}
