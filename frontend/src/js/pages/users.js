// src/js/pages/users.js
import { createTable } from '../components/table.js';
import { showModal } from '../components/modal.js';
import { showToast } from '../components/toast.js';

export function renderUserManagement(container) {
    container.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1>User Management</h1>
                <p>Manage system users and their access roles.</p>
            </div>
            <button class="btn btn-primary" onclick="alert('Invite User flow not in mock')">+ Invite User</button>
        </div>
        
        <div id="users-table-container"></div>
    `;

    // Mock Users Data
    const users = [
        { id: 1, name: 'Admin User', email: 'admin@campus.edu', role: 'Admin', status: 'Active' },
        { id: 2, name: 'John Tech', email: 'tech@campus.edu', role: 'Technician', status: 'Active' },
        { id: 3, name: 'Alice Student', email: 'student@campus.edu', role: 'Student', status: 'Active' },
        { id: 4, name: 'Bob Staff', email: 'staff@campus.edu', role: 'Staff', status: 'Inactive' }
    ];

    const columns = [
        { key: 'name', label: 'Name', render: (val) => `<span class="font-medium">${val}</span>` },
        { key: 'email', label: 'Email' },
        { key: 'role', label: 'Role', render: (val) => {
            if (val === 'Admin') return `<span class="badge badge-danger">${val}</span>`;
            if (val === 'Technician') return `<span class="badge badge-warning">${val}</span>`;
            return `<span class="badge badge-info">${val}</span>`;
        }},
        { key: 'status', label: 'Status', render: (val) => {
            return val === 'Active' ? `<span class="badge badge-success">Active</span>` : `<span class="badge badge-secondary" style="background:#e5e7eb;color:#374151">Inactive</span>`;
        }},
        { key: 'actions', label: 'Actions', render: (_, row) => `
            <button class="btn btn-secondary btn-sm" onclick="window.editUserRole(${row.id})">Edit Role</button>
        `}
    ];

    function render() {
        document.getElementById('users-table-container').innerHTML = createTable({ columns, data: users });
    }

    // Global function for inline handler
    window.editUserRole = (id) => {
        const user = users.find(u => u.id === id);
        if (!user) return;

        showModal({
            title: `Edit Role: ${user.name}`,
            body: `
                <div class="form-group">
                    <label class="form-label">System Role</label>
                    <select id="modal-role" class="form-control">
                        <option value="Student" ${user.role === 'Student' ? 'selected' : ''}>Student</option>
                        <option value="Staff" ${user.role === 'Staff' ? 'selected' : ''}>Staff</option>
                        <option value="Technician" ${user.role === 'Technician' ? 'selected' : ''}>Technician</option>
                        <option value="Admin" ${user.role === 'Admin' ? 'selected' : ''}>Admin</option>
                    </select>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Account Status</label>
                    <select id="modal-status" class="form-control">
                        <option value="Active" ${user.status === 'Active' ? 'selected' : ''}>Active</option>
                        <option value="Inactive" ${user.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
            `,
            actions: [
                {
                    label: 'Save Changes',
                    onClick: (e, close) => {
                        const newRole = document.getElementById('modal-role').value;
                        const newStatus = document.getElementById('modal-status').value;
                        
                        user.role = newRole;
                        user.status = newStatus;
                        
                        showToast(`${user.name}'s account updated successfully!`, 'success');
                        render(); // re-render table
                        close();
                    }
                }
            ]
        });
    };

    render();
}
