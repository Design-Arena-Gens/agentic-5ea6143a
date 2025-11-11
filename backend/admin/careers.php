<?php
$page_title = 'Careers Management';
require_once __DIR__ . '/../includes/header.php';
require_auth(true);

$view = sanitize_input($_GET['view'] ?? 'positions');

try {
    if ($view === 'applications') {
        $applications = db()->fetchAll(
            'SELECT ca.*, c.position, c.department
             FROM career_applications ca
             JOIN careers c ON ca.career_id = c.id
             ORDER BY ca.created_at DESC'
        );
    } else {
        $positions = db()->fetchAll('SELECT * FROM careers ORDER BY created_at DESC');
    }
} catch (Exception $e) {
    error_log('Careers error: ' . $e->getMessage());
    $positions = [];
    $applications = [];
}
?>

<style>
    .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    .section h2 { color: #2c3e50; margin-bottom: 1rem; font-size: 1.3rem; }
    .btn { display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: background 0.3s; font-size: 0.9rem; }
    .btn:hover { background: #2980b9; }
    .btn-danger { background: #e74c3c; }
    .btn-danger:hover { background: #c0392b; }
    .btn-success { background: #27ae60; }
    .btn-success:hover { background: #229954; }

    .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; }
    .tabs a { padding: 0.75rem 1.5rem; background: white; color: #2c3e50; text-decoration: none; border-radius: 8px 8px 0 0; transition: all 0.3s; }
    .tabs a:hover { background: #ecf0f1; }
    .tabs a.active { background: #3498db; color: white; }

    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ecf0f1; font-size: 0.9rem; }
    th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
    tr:hover { background: #f8f9fa; }

    .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem; font-weight: 500; }
    .badge.open { background: #27ae60; color: white; }
    .badge.closed { background: #e74c3c; color: white; }
    .badge.on-hold { background: #f39c12; color: white; }
    .badge.pending { background: #3498db; color: white; }
    .badge.reviewing { background: #f39c12; color: white; }
    .badge.shortlisted { background: #9b59b6; color: white; }
    .badge.rejected { background: #e74c3c; color: white; }
    .badge.hired { background: #27ae60; color: white; }

    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2c3e50; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
    .form-group textarea { min-height: 100px; resize: vertical; }
</style>

<div class="tabs">
    <a href="careers.php?view=positions" class="<?php echo $view === 'positions' ? 'active' : ''; ?>">Positions</a>
    <a href="careers.php?view=applications" class="<?php echo $view === 'applications' ? 'active' : ''; ?>">Applications</a>
</div>

<?php if ($view === 'positions'): ?>
<div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Career Positions</h2>
        <button class="btn btn-success" onclick="openModal()">Add New Position</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Department</th>
                <th>Location</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($positions as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['position']); ?></td>
                    <td><?php echo htmlspecialchars($item['department'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                    <td><?php echo htmlspecialchars($item['type']); ?></td>
                    <td><span class="badge <?php echo htmlspecialchars($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                    <td>
                        <button class="btn" onclick='editItem(<?php echo json_encode($item); ?>)'>Edit</button>
                        <button class="btn btn-danger" onclick="deleteItem(<?php echo $item['id']; ?>)">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Position Modal -->
<div id="itemModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Add Career Position</h2>
        <form id="itemForm">
            <input type="hidden" id="item_id" name="id">

            <div class="form-group">
                <label for="position">Position Title *</label>
                <input type="text" id="position" name="position" required>
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department">
            </div>

            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" required>
            </div>

            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="contract">Contract</option>
                    <option value="internship">Internship</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>

            <div class="form-group">
                <label for="requirements">Requirements</label>
                <textarea id="requirements" name="requirements"></textarea>
            </div>

            <div class="form-group">
                <label for="salary_range">Salary Range</label>
                <input type="text" id="salary_range" name="salary_range" placeholder="e.g., $80,000 - $120,000">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                    <option value="on-hold">On Hold</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-success">Save</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(item = null) {
    const modal = document.getElementById('itemModal');
    const form = document.getElementById('itemForm');
    const title = document.getElementById('modalTitle');

    if (item) {
        title.textContent = 'Edit Career Position';
        document.getElementById('item_id').value = item.id;
        document.getElementById('position').value = item.position;
        document.getElementById('department').value = item.department || '';
        document.getElementById('location').value = item.location;
        document.getElementById('type').value = item.type;
        document.getElementById('description').value = item.description || '';
        document.getElementById('requirements').value = item.requirements || '';
        document.getElementById('salary_range').value = item.salary_range || '';
        document.getElementById('status').value = item.status;
    } else {
        title.textContent = 'Add Career Position';
        form.reset();
    }

    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('itemModal').classList.remove('active');
}

function editItem(item) {
    openModal(item);
}

async function deleteItem(id) {
    if (!confirmDelete()) return;

    try {
        await apiCall(`/backend/api/crud.php?table=careers&id=${id}`, 'DELETE');
        showAlert('Position deleted successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

document.getElementById('itemForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    delete data.id;

    try {
        if (id) {
            await apiCall(`/backend/api/crud.php?table=careers&id=${id}`, 'PUT', data);
            showAlert('Position updated successfully', 'success');
        } else {
            await apiCall('/backend/api/crud.php?table=careers', 'POST', data);
            showAlert('Position created successfully', 'success');
        }
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showAlert(error.message, 'error');
    }
});
</script>

<?php else: ?>
<div class="section">
    <h2>Career Applications</h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Position</th>
                <th>Applicant</th>
                <th>Email</th>
                <th>CV</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($app['position']); ?></td>
                    <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                    <td><a href="/backend/uploads/cv/<?php echo htmlspecialchars($app['cv_file']); ?>" target="_blank" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">View CV</a></td>
                    <td><span class="badge <?php echo htmlspecialchars($app['status']); ?>"><?php echo htmlspecialchars($app['status']); ?></span></td>
                    <td>
                        <button class="btn" onclick='viewApplication(<?php echo json_encode($app); ?>)' style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">View</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Application Modal -->
<div id="appModal" class="modal">
    <div class="modal-content">
        <h3>Application Details</h3>
        <div id="appDetails"></div>
        <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem; flex-wrap: wrap;">
            <button class="btn" onclick="updateAppStatus('reviewing')" style="background: #f39c12;">Mark Reviewing</button>
            <button class="btn" onclick="updateAppStatus('shortlisted')" style="background: #9b59b6;">Shortlist</button>
            <button class="btn" onclick="updateAppStatus('hired')" style="background: #27ae60;">Mark Hired</button>
            <button class="btn btn-danger" onclick="updateAppStatus('rejected')">Reject</button>
            <button class="btn" onclick="closeAppModal()">Close</button>
        </div>
    </div>
</div>

<script>
let currentApp = null;

function viewApplication(app) {
    currentApp = app;

    const html = `
        <div style="margin-bottom: 1rem;"><strong>Position:</strong> ${app.position} (${app.department})</div>
        <div style="margin-bottom: 1rem;"><strong>Applicant:</strong> ${app.full_name}</div>
        <div style="margin-bottom: 1rem;"><strong>Email:</strong> <a href="mailto:${app.email}">${app.email}</a></div>
        ${app.phone ? `<div style="margin-bottom: 1rem;"><strong>Phone:</strong> ${app.phone}</div>` : ''}
        <div style="margin-bottom: 1rem;"><strong>CV:</strong> <a href="/backend/uploads/cv/${app.cv_file}" target="_blank">Download</a></div>
        ${app.linkedin ? `<div style="margin-bottom: 1rem;"><strong>LinkedIn:</strong> <a href="${app.linkedin}" target="_blank">View Profile</a></div>` : ''}
        ${app.portfolio ? `<div style="margin-bottom: 1rem;"><strong>Portfolio:</strong> <a href="${app.portfolio}" target="_blank">View</a></div>` : ''}
        ${app.cover_letter ? `<div style="margin-bottom: 1rem;"><strong>Cover Letter:</strong><br>${app.cover_letter}</div>` : ''}
        <div style="margin-bottom: 1rem;"><strong>Applied:</strong> ${new Date(app.created_at).toLocaleString()}</div>
        <div><strong>Status:</strong> <span class="badge ${app.status}">${app.status}</span></div>
    `;

    document.getElementById('appDetails').innerHTML = html;
    document.getElementById('appModal').classList.add('active');
}

function closeAppModal() {
    document.getElementById('appModal').classList.remove('active');
}

async function updateAppStatus(status) {
    if (!currentApp) return;

    try {
        await apiCall(`/backend/api/crud.php?table=career_applications&id=${currentApp.id}`, 'PUT', { status });
        showAlert('Status updated successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showAlert(error.message, 'error');
    }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
