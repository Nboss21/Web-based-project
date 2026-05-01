// src/js/pages/dashboard-student.js
import { showToast } from '../components/toast.js';
import { RequestsService } from '../api/requests.js';

export function renderStudentDashboard(container) {
    container.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1>My Dashboard</h1>
                <p>Submit and track your maintenance requests.</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- New Request Form -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem;">New Maintenance Request</h3>
                <form id="request-form">
                    <div class="form-group">
                        <label class="form-label">Issue Title</label>
                        <input type="text" id="req-title" class="form-control" placeholder="e.g., Leaking faucet in Lab 3" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select id="req-category" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="plumbing">Plumbing</option>
                                <option value="electrical">Electrical</option>
                                <option value="hvac">HVAC</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select id="req-priority" class="form-control" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea id="req-desc" class="form-control" placeholder="Provide details about the issue..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Upload Image (Optional)</label>
                        <input type="file" id="req-image" class="form-control" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Request</button>
                </form>
            </div>
            
            <!-- Recent Requests -->
            <div>
                <h3 style="margin-bottom: 1.5rem;">Recent Requests</h3>
                <div id="recent-requests-list" style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Mock Data -->
                    <div class="card" style="padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <h4 style="margin: 0; font-size: 1rem;">AC not working</h4>
                            <span class="badge badge-warning">Pending</span>
                        </div>
                        <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Library 2nd Floor</p>
                        <small class="text-tertiary">Submitted: 2 hrs ago</small>
                    </div>
                    
                    <div class="card" style="padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <h4 style="margin: 0; font-size: 1rem;">Broken Projector</h4>
                            <span class="badge badge-success">Resolved</span>
                        </div>
                        <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Room 402</p>
                        <small class="text-tertiary">Submitted: 2 days ago</small>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Form logic
    const form = document.getElementById('request-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const title = document.getElementById('req-title').value;
        const category = document.getElementById('req-category').value;
        const priority = document.getElementById('req-priority').value;
        const description = document.getElementById('req-desc').value;
        const fileInput = document.getElementById('req-image');

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Submitting...';

        try {
            const formData = new FormData();
            formData.append('title', title);
            formData.append('description', description);
            formData.append('category', category);
            formData.append('priority', priority);
            if (fileInput && fileInput.files && fileInput.files[0]) {
                formData.append('images[]', fileInput.files[0]);
            }

            const res = await RequestsService.create(formData);
            showToast(res.message || 'Request submitted', 'success');
            form.reset();

            // Optimistically add to recent list
            const list = document.getElementById('recent-requests-list');
            const newItem = document.createElement('div');
            newItem.className = 'card animate-slide-in';
            newItem.style.padding = '1rem';
            newItem.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <h4 style="margin: 0; font-size: 1rem;">${title}</h4>
                    <span class="badge badge-warning">Pending</span>
                </div>
                <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Just now</p>
            `;
            list.insertBefore(newItem, list.firstChild);

        } catch (err) {
            console.error(err);
            showToast(err.message || 'Failed to submit request', 'danger');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Submit Request';
        }
    });
}
