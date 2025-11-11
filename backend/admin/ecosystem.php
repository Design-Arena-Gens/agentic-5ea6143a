<?php
$page_title = 'Ecosystem Management';
require_once __DIR__ . '/../includes/header.php';
require_auth(true);

try {
    $items = db()->fetchAll('SELECT * FROM ecosystem ORDER BY display_order ASC, id DESC');
} catch (Exception $e) {
    error_log('Ecosystem error: ' . $e->getMessage());
    $items = [];
}
?>

<style>
    .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    .section h2 { color: #2c3e50; margin-bottom: 1rem; font-size: 1.3rem; }
    .btn { display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: background 0.3s; }
    .btn:hover { background: #2980b9; }
    .btn-danger { background: #e74c3c; }
    .btn-danger:hover { background: #c0392b; }
    .btn-success { background: #27ae60; }
    .btn-success:hover { background: #229954; }

    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ecf0f1; }
    th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
    tr:hover { background: #f8f9fa; }

    .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem; font-weight: 500; }
    .badge.active { background: #27ae60; color: white; }
    .badge.inactive { background: #95a5a6; color: white; }

    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2c3e50; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
    .form-group textarea { min-height: 100px; resize: vertical; }
</style>

<div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Ecosystem Items</h2>
        <button class="btn btn-success" onclick="openModal()">Add New Item</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Title</th>
                <th>Description</th>
                <th>Link</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['display_order']); ?></td>
                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                    <td><?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?>...</td>
                    <td><?php echo htmlspecialchars($item['link']); ?></td>
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

<!-- Modal -->
<div id="itemModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Add Ecosystem Item</h2>
        <form id="itemForm">
            <input type="hidden" id="item_id" name="id">

            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>

            <div class="form-group">
                <label for="icon">Icon</label>
                <input type="text" id="icon" name="icon" placeholder="icon.png">
            </div>

            <div class="form-group">
                <label for="link">Link</label>
                <input type="url" id="link" name="link" placeholder="https://">
            </div>

            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" id="display_order" name="display_order" value="0">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
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
        title.textContent = 'Edit Ecosystem Item';
        document.getElementById('item_id').value = item.id;
        document.getElementById('title').value = item.title;
        document.getElementById('description').value = item.description || '';
        document.getElementById('icon').value = item.icon || '';
        document.getElementById('link').value = item.link || '';
        document.getElementById('display_order').value = item.display_order;
        document.getElementById('status').value = item.status;
    } else {
        title.textContent = 'Add Ecosystem Item';
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
        await apiCall(`/backend/api/crud.php?table=ecosystem&id=${id}`, 'DELETE');
        showAlert('Item deleted successfully', 'success');
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
            await apiCall(`/backend/api/crud.php?table=ecosystem&id=${id}`, 'PUT', data);
            showAlert('Item updated successfully', 'success');
        } else {
            await apiCall('/backend/api/crud.php?table=ecosystem', 'POST', data);
            showAlert('Item created successfully', 'success');
        }
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showAlert(error.message, 'error');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
