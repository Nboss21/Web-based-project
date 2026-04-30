// src/js/pages/dashboard-admin.js
import { createTable } from '../components/table.js';
import { showModal } from '../components/modal.js';
import { showToast } from '../components/toast.js';
import { InventoryService } from '../api/inventory.js';

export function renderAdminDashboard(container) {
    container.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1>Admin Dashboard</h1>
                <p>System overview and inventory management.</p>
            </div>
            <button class="btn btn-primary" id="btn-generate-report">Generate Report</button>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card" style="text-align: center;">
                <h2 style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.5rem;">24</h2>
                <p class="text-secondary font-medium">Open Requests</p>
            </div>
            <div class="card" style="text-align: center;">
                <h2 style="font-size: 2.5rem; color: var(--warning-color); margin-bottom: 0.5rem;">8</h2>
                <p class="text-secondary font-medium">Low Stock Items</p>
            </div>
            <div class="card" style="text-align: center;">
                <h2 style="font-size: 2.5rem; color: var(--success-color); margin-bottom: 0.5rem;">12</h2>
                <p class="text-secondary font-medium">Active Techs</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div>
                <h3 style="margin-bottom: 1rem;">Inventory Status</h3>
                <div id="inventory-table-container"></div>
            </div>
            <div>
                <h3 style="margin-bottom: 1rem;">Recent Activity</h3>
                <div class="card" style="padding: 1rem;">
                    <ul style="display: flex; flex-direction: column; gap: 1rem;">
                        <li style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                            <p style="margin-bottom: 0.25rem;"><strong>John Tech</strong> resolved request <span class="text-primary">#REQ-098</span></p>
                            <small class="text-tertiary">10 mins ago</small>
                        </li>
                        <li style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                            <p style="margin-bottom: 0.25rem;"><strong>Alice Student</strong> submitted request <span class="text-primary">#REQ-109</span></p>
                            <small class="text-tertiary">1 hr ago</small>
                        </li>
                        <li>
                            <p style="margin-bottom: 0.25rem;">System alert: <span class="text-danger">Lightbulbs stock low</span></p>
                            <small class="text-tertiary">2 hrs ago</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    `;

    let inventory = [];

    const columns = [
        { key: 'item', label: 'Item Name', render: (val) => `<span class="font-medium">${val}</span>` },
        { key: 'category', label: 'Category' },
        { key: 'quantity', label: 'Qty' },
        { key: 'status', label: 'Status', render: (val) => {
            if (val === 'In Stock') return `<span class="badge badge-success">${val}</span>`;
            if (val === 'Low Stock') return `<span class="badge badge-warning">${val}</span>`;
            return `<span class="badge badge-danger">${val}</span>`;
        }},
        { key: 'actions', label: '', render: (_, row) => `
            <button class="btn btn-secondary btn-sm" onclick="window.orderItem('${row.id}')">Order</button>
        `}
    ];

    async function renderInventory() {
        document.getElementById('inventory-table-container').innerHTML = '<div class="card">Loading inventory...</div>';
        try {
            const res = await InventoryService.list({ page: 1, limit: 20 });
            const items = (res && res.data) ? res.data : [];

            // Map backend fields to UI-friendly shape
            inventory = items.map(i => ({
                id: i.id,
                item: i.item_name || i.name || 'Unnamed',
                category: i.category,
                quantity: i.quantity,
                threshold: i.reorder_level || i.threshold || 0,
                status: i.stock_status || (i.quantity <= (i.reorder_level ?? 0) ? 'Low Stock' : 'In Stock')
            }));

            document.getElementById('inventory-table-container').innerHTML = createTable({ columns, data: inventory });
        } catch (e) {
            document.getElementById('inventory-table-container').innerHTML = '<div class="card text-danger">Failed to load inventory</div>';
            console.error(e);
        }
    }

    renderInventory();

    // Interactive Generate Report Logic
    document.getElementById('btn-generate-report').addEventListener('click', () => {
        showModal({
            title: 'Generate System Report',
            body: `
                <div class="form-group">
                    <label class="form-label">Report Type</label>
                    <select id="report-type" class="form-control">
                        <option value="summary">Monthly Summary</option>
                        <option value="inventory">Inventory Status</option>
                        <option value="performance">Technician Performance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Export Format</label>
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <label><input type="radio" name="format" value="pdf" checked> PDF</label>
                        <label><input type="radio" name="format" value="csv"> CSV/Excel</label>
                    </div>
                </div>
            `,
            actions: [
                {
                    label: 'Download Report',
                    onClick: (e, close) => {
                        const type = document.getElementById('report-type').value;
                        const btn = e.target;
                        btn.disabled = true;
                        btn.textContent = 'Generating...';
                        
                        // Simulate generation delay
                        setTimeout(() => {
                            showToast(`${type.toUpperCase()} report generated successfully!`, 'success');
                            close();
                        }, 1200);
                    }
                }
            ]
        });
    });

    // Interactive Inventory Ordering
    window.orderItem = (id) => {
        const item = inventory.find(i => i.id == id);
        if (!item) return;

        showModal({
            title: `Order Stock: ${item.item}`,
            body: `
                <div style="margin-bottom: 1rem;">
                    <p>Current Quantity: <strong>${item.quantity}</strong></p>
                    <p>Low Stock Threshold: <strong>${item.threshold}</strong></p>
                </div>
                <div class="form-group">
                    <label class="form-label">Order Quantity</label>
                    <input type="number" id="order-quantity" class="form-control" value="10" min="1">
                </div>
            `,
            actions: [
                {
                    label: 'Place Order',
                    onClick: async (e, close) => {
                        const qty = parseInt(document.getElementById('order-quantity').value, 10);
                        if (isNaN(qty) || qty <= 0) return;
                        try {
                            const resp = await InventoryService.adjust(item.id, { adjustment_type: 'Add', quantity: qty, reason: 'Manual order via admin UI' });
                            showToast(resp.message || 'Order placed', 'success');
                            await renderInventory();
                            close();
                        } catch (err) {
                            console.error(err);
                            showToast(err.message || 'Failed to order', 'danger');
                        }
                    }
                }
            ]
        });
    };
}
