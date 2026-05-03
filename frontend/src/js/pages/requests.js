// src/js/pages/requests.js
import { createTable } from '../components/table.js';
import { AuthService } from '../api/auth.js';
import { showModal } from '../components/modal.js';
import { showToast } from '../components/toast.js';
import { RequestsService } from '../api/requests.js';
import { apiFetch } from '../api/client.js';

export async function renderRequestsPage(container) {
    container.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1>Maintenance Requests</h1>
                <p>View and filter all campus maintenance requests.</p>
            </div>
        </div>

        <div class="card" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" id="search-input" class="form-control" placeholder="Search by ID, Title, or Location...">
            </div>
            <div style="width: 200px;">
                <select id="status-filter" class="form-control">
                    <option value="all">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
        </div>

        <div id="full-requests-table-container"></div>
    `;

    let allRequests = [];

    const columns = [
        { key: 'id', label: 'ID', render: (val) => `<span class="font-medium text-primary">${val}</span>` },
        { key: 'title', label: 'Title' },
        { key: 'location', label: 'Location' },
        { key: 'category', label: 'Category' },
        { key: 'priority', label: 'Priority', render: (val) => {
            const colors = { 'High': 'danger', 'Medium': 'warning', 'Low': 'info' };
            return `<span class="badge badge-${colors[val]}">${val}</span>`;
        }},
        { key: 'status', label: 'Status', render: (val) => {
            const colors = { 'Resolved': 'success', 'In Progress': 'warning', 'Pending': 'info' };
            return `<span class="badge badge-${colors[val]}">${val}</span>`;
        }},
        { key: 'date', label: 'Submitted' }
    ];

    const currentUser = AuthService.getCurrentUser();
    if (currentUser && currentUser.role === 'Admin') {
        columns.push({
            key: 'actions', label: 'Actions', render: (_, row) => {
                if (row.status !== 'Resolved') {
                    return `<button class="btn btn-secondary btn-sm" onclick="window.assignRequest('${row.id}')">Assign</button>`;
                }
                return '';
            }
        });
    }

    const tableContainer = document.getElementById('full-requests-table-container');
    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');

    // Load requests from backend
    async function loadRequests() {
        tableContainer.innerHTML = '<div class="card">Loading requests...</div>';
        try {
            const status = statusFilter.value !== 'all' ? statusFilter.value : undefined;
            const res = await RequestsService.list({ page: 1, limit: 50, status });
            allRequests = (res && res.data) ? res.data : [];
            renderTable();
        } catch (err) {
            console.error(err);
            tableContainer.innerHTML = '<div class="card text-danger">Failed to load requests</div>';
        }
    }

    // Render Table Function
    function renderTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;

        const filteredData = allRequests.filter(req => {
            // Check status
            if (statusValue !== 'all' && req.status !== statusValue) return false;
            
            // Check search term
            if (searchTerm) {
                return req.id.toLowerCase().includes(searchTerm) || 
                       req.title.toLowerCase().includes(searchTerm) || 
                       req.location.toLowerCase().includes(searchTerm);
            }
            return true;
        });

        tableContainer.innerHTML = createTable({ 
            columns, 
            data: filteredData,
            emptyMessage: 'No requests found matching your filters.' 
        });
    }

    // Bind events
    searchInput.addEventListener('input', renderTable);
    statusFilter.addEventListener('change', renderTable);

    // Initial render
    await loadRequests();

    // Global function for Assign handler
    window.assignRequest = async (id) => {
        const req = allRequests.find(r => String(r.id) === String(id));
        if (!req) return;

        // Fetch available technicians
        let techs = [];
        try {
            const techRes = await apiFetch('/users/technicians', { method: 'GET' });
            techs = (techRes && techRes.data) ? techRes.data : [];
        } catch (e) {
            console.error('Failed to load technicians', e);
        }

        const optionsHtml = techs.map(t => `<option value="${t.id}">${t.name}</option>`).join('') || '<option disabled>No technicians</option>';

        showModal({
            title: `Assign Task: ${req.id}`,
            body: `
                <div class="form-group">
                    <label class="form-label">Select Technician</label>
                    <select id="modal-technician" class="form-control">${optionsHtml}</select>
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date (optional)</label>
                    <input type="date" id="modal-due-date" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea id="modal-notes" class="form-control" rows="3"></textarea>
                </div>
            `,
            actions: [
                {
                    label: 'Assign Task',
                    onClick: async (e, close) => {
                        const techId = document.getElementById('modal-technician').value;
                        const dueDate = document.getElementById('modal-due-date').value || null;
                        const notes = document.getElementById('modal-notes').value || '';
                        try {
                            const resp = await RequestsService.assign(req.id, { technician_id: techId, due_date: dueDate, notes });
                            showToast(resp.message || 'Assigned successfully', 'success');
                            await loadRequests();
                            close();
                        } catch (err) {
                            console.error(err);
                            showToast(err.message || 'Failed to assign', 'danger');
                        }
                    }
                }
            ]
        });
    };
}
