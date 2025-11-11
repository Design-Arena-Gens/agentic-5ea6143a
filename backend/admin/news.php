<?php
$page_title = 'News Management';
require_once __DIR__ . '/../includes/header.php';
require_auth(true);

try {
    $items = db()->fetchAll('SELECT n.*, u.full_name as author_name FROM news n LEFT JOIN users u ON n.author_id = u.id ORDER BY n.created_at DESC');
} catch (Exception $e) {
    error_log('News error: ' . $e->getMessage());
    $items = [];
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

    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ecf0f1; font-size: 0.9rem; }
    th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
    tr:hover { background: #f8f9fa; }

    .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem; font-weight: 500; }
    .badge.published { background: #27ae60; color: white; }
    .badge.draft { background: #95a5a6; color: white; }
    .badge.archived { background: #7f8c8d; color: white; }

    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2c3e50; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
    .form-group textarea { min-height: 150px; resize: vertical; }
</style>

<div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>News Articles</h2>
        <button class="btn btn-success" onclick="openModal()">Add New Article</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Author</th>
                <th>Status</th>
                <th>Published</th>
                <th>Views</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                    <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['author_name'] ?? 'N/A'); ?></td>
                    <td><span class="badge <?php echo htmlspecialchars($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                    <td><?php echo $item['published_at'] ? date('M d, Y', strtotime($item['published_at'])) : 'N/A'; ?></td>
                    <td><?php echo number_format($item['views']); ?></td>
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
        <h2 id="modalTitle">Add News Article</h2>
        <form id="itemForm">
            <input type="hidden" id="item_id" name="id">

            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" placeholder="Technology, Business, etc.">
            </div>

            <div class="form-group">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content" required></textarea>
            </div>

            <div class="form-group">
                <label for="featured_image">Featured Image URL</label>
                <input type="text" id="featured_image" name="featured_image">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
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
        title.textContent = 'Edit News Article';
        document.getElementById('item_id').value = item.id;
        document.getElementById('title').value = item.title;
        document.getElementById('category').value = item.category || '';
        document.getElementById('excerpt').value = item.excerpt || '';
        document.getElementById('content').value = item.content || '';
        document.getElementById('featured_image').value = item.featured_image || '';
        document.getElementById('status').value = item.status;
    } else {
        title.textContent = 'Add News Article';
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
        await apiCall(`/backend/api/crud.php?table=news&id=${id}`, 'DELETE');
        showAlert('Article deleted successfully', 'success');
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

    // Set author_id for new articles
    if (!id) {
        data.author_id = <?php echo $_SESSION['user_id']; ?>;
    }

    // Set published_at if status is published
    if (data.status === 'published' && !id) {
        data.published_at = new Date().toISOString().slice(0, 19).replace('T', ' ');
    }

    try {
        if (id) {
            await apiCall(`/backend/api/crud.php?table=news&id=${id}`, 'PUT', data);
            showAlert('Article updated successfully', 'success');
        } else {
            await apiCall('/backend/api/crud.php?table=news', 'POST', data);
            showAlert('Article created successfully', 'success');
        }
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        showAlert(error.message, 'error');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
