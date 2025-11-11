<?php
$page_title = 'Contact Messages';
require_once __DIR__ . '/../includes/header.php';
require_auth(true);

try {
    $status_filter = sanitize_input($_GET['status'] ?? '');
    $where = '';
    $params = [];

    if ($status_filter) {
        $where = 'WHERE status = :status';
        $params[':status'] = $status_filter;
    }

    $items = db()->fetchAll("SELECT * FROM contacts $where ORDER BY created_at DESC");
    $stats = [
        'new' => db()->fetchColumn('SELECT COUNT(*) FROM contacts WHERE status = "new"'),
        'read' => db()->fetchColumn('SELECT COUNT(*) FROM contacts WHERE status = "read"'),
        'replied' => db()->fetchColumn('SELECT COUNT(*) FROM contacts WHERE status = "replied"'),
        'archived' => db()->fetchColumn('SELECT COUNT(*) FROM contacts WHERE status = "archived"')
    ];
} catch (Exception $e) {
    error_log('Contacts error: ' . $e->getMessage());
    $items = [];
    $stats = [];
}
?>

<style>
    .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    .section h2 { color: #2c3e50; margin-bottom: 1rem; font-size: 1.3rem; }

    .stats { display: flex; gap: 1rem; margin-bottom: 2rem; }
    .stat { background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex: 1; text-align: center; }
    .stat .number { font-size: 2rem; font-weight: bold; color: #2c3e50; }
    .stat .label { color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; }

    .filters { margin-bottom: 1rem; }
    .filters a { display: inline-block; padding: 0.5rem 1rem; margin-right: 0.5rem; background: #ecf0f1; color: #2c3e50; text-decoration: none; border-radius: 4px; transition: background 0.3s; }
    .filters a:hover, .filters a.active { background: #3498db; color: white; }

    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #ecf0f1; font-size: 0.9rem; }
    th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
    tr:hover { background: #f8f9fa; }
    tr.new { font-weight: 600; }

    .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem; font-weight: 500; }
    .badge.new { background: #3498db; color: white; }
    .badge.read { background: #95a5a6; color: white; }
    .badge.replied { background: #27ae60; color: white; }
    .badge.archived { background: #7f8c8d; color: white; }

    .btn { display: inline-block; padding: 0.4rem 0.8rem; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: background 0.3s; font-size: 0.85rem; }
    .btn:hover { background: #2980b9; }

    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
    .modal-content h3 { color: #2c3e50; margin-bottom: 1rem; }
    .modal-content .field { margin-bottom: 1rem; }
    .modal-content .field label { display: block; font-weight: 600; color: #7f8c8d; margin-bottom: 0.25rem; }
    .modal-content .field p { color: #2c3e50; }
    .status-buttons { display: flex; gap: 0.5rem; margin-top: 1.5rem; }
</style>

<div class="stats">
    <div class="stat">
        <div class="number"><?php echo number_format($stats['new']); ?></div>
        <div class="label">New</div>
    </div>
    <div class="stat">
        <div class="number"><?php echo number_format($stats['read']); ?></div>
        <div class="label">Read</div>
    </div>
    <div class="stat">
        <div class="number"><?php echo number_format($stats['replied']); ?></div>
        <div class="label">Replied</div>
    </div>
    <div class="stat">
        <div class="number"><?php echo number_format($stats['archived']); ?></div>
        <div class="label">Archived</div>
    </div>
</div>

<div class="section">
    <h2>Contact Messages</h2>

    <div class="filters">
        <a href="contacts.php" class="<?php echo !$status_filter ? 'active' : ''; ?>">All</a>
        <a href="contacts.php?status=new" class="<?php echo $status_filter === 'new' ? 'active' : ''; ?>">New</a>
        <a href="contacts.php?status=read" class="<?php echo $status_filter === 'read' ? 'active' : ''; ?>">Read</a>
        <a href="contacts.php?status=replied" class="<?php echo $status_filter === 'replied' ? 'active' : ''; ?>">Replied</a>
        <a href="contacts.php?status=archived" class="<?php echo $status_filter === 'archived' ? 'active' : ''; ?>">Archived</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr class="<?php echo $item['status'] === 'new' ? 'new' : ''; ?>">
                    <td><?php echo date('M d, Y H:i', strtotime($item['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['email']); ?></td>
                    <td><?php echo htmlspecialchars($item['subject'] ?: 'No subject'); ?></td>
                    <td><span class="badge <?php echo htmlspecialchars($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                    <td>
                        <button class="btn" onclick='viewContact(<?php echo json_encode($item); ?>)'>View</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <h3>Contact Details</h3>
        <div id="contactDetails"></div>
        <div class="status-buttons">
            <button class="btn" onclick="updateStatus('read')">Mark as Read</button>
            <button class="btn" onclick="updateStatus('replied')" style="background: #27ae60;">Mark as Replied</button>
            <button class="btn" onclick="updateStatus('archived')" style="background: #95a5a6;">Archive</button>
            <button class="btn" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
let currentContact = null;

function viewContact(contact) {
    currentContact = contact;

    const html = `
        <div class="field"><label>Name:</label><p>${contact.name}</p></div>
        <div class="field"><label>Email:</label><p><a href="mailto:${contact.email}">${contact.email}</a></p></div>
        ${contact.phone ? `<div class="field"><label>Phone:</label><p>${contact.phone}</p></div>` : ''}
        ${contact.company ? `<div class="field"><label>Company:</label><p>${contact.company}</p></div>` : ''}
        ${contact.subject ? `<div class="field"><label>Subject:</label><p>${contact.subject}</p></div>` : ''}
        <div class="field"><label>Message:</label><p>${contact.message}</p></div>
        <div class="field"><label>Date:</label><p>${new Date(contact.created_at).toLocaleString()}</p></div>
        <div class="field"><label>Status:</label><p><span class="badge ${contact.status}">${contact.status}</span></p></div>
    `;

    document.getElementById('contactDetails').innerHTML = html;
    document.getElementById('viewModal').classList.add('active');

    // Mark as read
    if (contact.status === 'new') {
        updateStatus('read', false);
    }
}

function closeModal() {
    document.getElementById('viewModal').classList.remove('active');
}

async function updateStatus(status, reload = true) {
    if (!currentContact) return;

    try {
        await apiCall(`/backend/api/crud.php?table=contacts&id=${currentContact.id}`, 'PUT', { status });
        if (reload) {
            showAlert('Status updated successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showAlert(error.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
