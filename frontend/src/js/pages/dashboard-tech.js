// src/js/pages/dashboard-tech.js
import { createTable } from '../components/table.js';
import { showModal } from '../components/modal.js';
import { showToast } from '../components/toast.js';

export function renderTechDashboard(container) {
    container.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1>Technician Dashboard</h1>
                <p>Manage your assigned maintenance tasks.</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="card" style="padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                    <div style="background: var(--warning-bg); color: var(--warning-color); padding: 0.75rem; border-radius: 50%;">
                        <span style="font-size: 1.5rem; font-weight: bold;">5</span>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Pending Tasks</div>
                    </div>
                </div>
            </div>
        </div>
        
        <h3 style="margin-bottom: 1rem;">My Active Tasks</h3>
        <div id="tasks-table-container"></div>
    `;

    // Mock Data
    const tasks = [
        { id: 'REQ-102', title: 'Leaking Pipe', location: 'Main Building Restroom', priority: 'High', status: 'In Progress' },
        { id: 'REQ-105', title: 'Flickering Lights', location: 'Hallway B', priority: 'Medium', status: 'Pending' },
        { id: 'REQ-108', title: 'Door Lock Broken', location: 'Lab 2', priority: 'Low', status: 'Pending' }
    ];

    const columns = [
        { key: 'id', label: 'ID', render: (val) => `<span class="font-medium text-primary">${val}</span>` },
        { key: 'title', label: 'Task' },
        { key: 'location', label: 'Location' },
        { key: 'priority', label: 'Priority', render: (val) => {
            const colors = { 'High': 'danger', 'Medium': 'warning', 'Low': 'info' };
            return `<span class="badge badge-${colors[val]}">${val}</span>`;
        }},
        { key: 'status', label: 'Status' },
        { key: 'actions', label: 'Actions', render: (_, row) => `
            <button class="btn btn-secondary btn-sm" onclick="window.updateTaskStatus('${row.id}')">Update</button>
        `}
    ];

    document.getElementById('tasks-table-container').innerHTML = createTable({ columns, data: tasks });

    // Global function for the inline onclick handler
    window.updateTaskStatus = (id) => {
        const task = tasks.find(t => t.id === id);
        
        showModal({
            title: `Update Task: ${task.id}`,
            body: `
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="modal-status" class="form-control">
                        <option value="Pending" ${task.status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option value="In Progress" ${task.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea id="modal-notes" class="form-control" placeholder="What actions were taken?"></textarea>
                </div>
            `,
            actions: [
                {
                    label: 'Save Changes',
                    onClick: (e, close) => {
                        const newStatus = document.getElementById('modal-status').value;
                        showToast(`Task ${id} marked as ${newStatus}`, 'success');
                        
                        // In a real app, we would re-fetch or update state here
                        task.status = newStatus;
                        document.getElementById('tasks-table-container').innerHTML = createTable({ columns, data: tasks });
                        
                        close();
                    }
                }
            ]
        });
    };
}
