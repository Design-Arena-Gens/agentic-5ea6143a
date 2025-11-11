<?php
$page_title = 'User Management';
require_once __DIR__ . '/../includes/header.php';
require_auth(true);

try {
    $users = db()->fetchAll('SELECT id, email, full_name, role, status, created_at, last_login FROM users ORDER BY created_at DESC');
} catch (Exception $e) {
    error_log('Users error: ' . $e->getMessage());
    $users = [];
}
?>

<style>
    .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    .section h2 { color: #2c3e50; margin-bottom: 1rem; font-size: 1.3rem; }
    .btn { display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: background 0.3s; font-size: 0.9rem; }
    .btn:hover { background: #2980b9; }
    .btn-danger { background: #e74c3c; }
    .btn-danger:hover { background: #c0392b; }

    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ecf0f1; font-size: 0.9rem; }
    th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
    tr:hover { background: #f8f9fa; }

    .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem; font-weight: 500; }
    .badge.admin { background: #9b59b6; color: white; }
    .badge.user { background: #3498db; color: white; }
    .badge.active { background: #27ae60; color: white; }
    .badge.inactive { background: #95a5a6; color: white; }
    .badge.suspended { background: #e74c3c; color: white; }

    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2c3e50; }
    .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
</style>

<div class="section">
    <h2>Users</h2>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="badge <?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                    <td><span class="badge <?php echo htmlspecialchars($user['status']); ?>"><?php echo htmlspecialchars($user['status']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <button class="btn" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['role']); ?>', '<?php echo htmlspecialchars($user['status']); ?>')">Edit</button>
                            <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                        <?php else: ?>
                            <span style="color: #95a5a6; font-size: 0.85rem;">Current user</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h2>Edit User</h2>
        <form id="editForm">
            <input type="hidden" id="user_id" name="id">

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn">Save Changes</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, role, status) {
    document.getElementById('user_id').value = id;
    document.getElementById('role').value = role;
    document.getElementById('status').value = status;
    document.getElementById('editModal').classList.add('active');
}

function closeModal() {
    document.getElementById('editModal').classList.remove('active');
}

async function deleteUser(id) {
    if (!confirmDelete('Are you sure you want to delete this user? This action cannot be undone.')) return;

    try {
        await apiCall(`/backend/api/crud.php?table=users&id=${id}`, 'DELETE');
        showAlert('User deleted successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const id = data.id;
    delete data.id;

    try {
        await apiCall(`/backend/api/crud.php?table=users&id=${id}`, 'PUT', data);
        showAlert('User updated successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showAlert(error.message, 'error');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
