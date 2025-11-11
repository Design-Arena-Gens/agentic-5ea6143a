<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_auth(true);

try {
    // Get statistics
    $stats = [
        'users' => db()->fetchColumn('SELECT COUNT(*) FROM users'),
        'contacts' => db()->fetchColumn('SELECT COUNT(*) FROM contacts WHERE status = "new"'),
        'applications' => db()->fetchColumn('SELECT COUNT(*) FROM career_applications WHERE status = "pending"'),
        'news' => db()->fetchColumn('SELECT COUNT(*) FROM news WHERE status = "published"'),
        'products' => db()->fetchColumn('SELECT COUNT(*) FROM products WHERE status = "active"'),
        'careers' => db()->fetchColumn('SELECT COUNT(*) FROM careers WHERE status = "open"')
    ];

    // Recent activity
    $recent_activity = db()->fetchAll(
        'SELECT al.*, u.full_name, u.email
         FROM activity_logs al
         LEFT JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC
         LIMIT 10'
    );

    // Recent contacts
    $recent_contacts = db()->fetchAll(
        'SELECT * FROM contacts
         ORDER BY created_at DESC
         LIMIT 5'
    );

} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $stats = [];
    $recent_activity = [];
    $recent_contacts = [];
}
?>

<style>
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .stat-card h3 { color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 500; }
    .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #2c3e50; }
    .stat-card.new { border-left: 4px solid #3498db; }
    .stat-card.pending { border-left: 4px solid #f39c12; }
    .stat-card.active { border-left: 4px solid #27ae60; }
    .stat-card.total { border-left: 4px solid #9b59b6; }

    .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    .section h2 { color: #2c3e50; margin-bottom: 1rem; font-size: 1.3rem; }

    .activity-list, .contact-list { list-style: none; }
    .activity-list li, .contact-list li { padding: 0.75rem; border-bottom: 1px solid #ecf0f1; }
    .activity-list li:last-child, .contact-list li:last-child { border-bottom: none; }
    .activity-list .time, .contact-list .time { color: #95a5a6; font-size: 0.85rem; }
    .activity-list .action { color: #3498db; font-weight: 500; }
    .contact-list .name { font-weight: 600; color: #2c3e50; }
    .contact-list .email { color: #7f8c8d; font-size: 0.9rem; }

    .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.8rem; font-weight: 500; }
    .badge.new { background: #3498db; color: white; }
    .badge.pending { background: #f39c12; color: white; }
    .badge.active { background: #27ae60; color: white; }
</style>

<div class="dashboard-grid">
    <div class="stat-card total">
        <h3>Total Users</h3>
        <div class="number"><?php echo number_format($stats['users']); ?></div>
    </div>

    <div class="stat-card new">
        <h3>New Contacts</h3>
        <div class="number"><?php echo number_format($stats['contacts']); ?></div>
    </div>

    <div class="stat-card pending">
        <h3>Pending Applications</h3>
        <div class="number"><?php echo number_format($stats['applications']); ?></div>
    </div>

    <div class="stat-card active">
        <h3>Published News</h3>
        <div class="number"><?php echo number_format($stats['news']); ?></div>
    </div>

    <div class="stat-card active">
        <h3>Active Products</h3>
        <div class="number"><?php echo number_format($stats['products']); ?></div>
    </div>

    <div class="stat-card active">
        <h3>Open Positions</h3>
        <div class="number"><?php echo number_format($stats['careers']); ?></div>
    </div>
</div>

<div class="section">
    <h2>Recent Activity</h2>
    <?php if (!empty($recent_activity)): ?>
        <ul class="activity-list">
            <?php foreach ($recent_activity as $activity): ?>
                <li>
                    <span class="action"><?php echo htmlspecialchars($activity['action']); ?></span>
                    <?php if ($activity['full_name']): ?>
                        by <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                    <?php endif; ?>
                    <?php if ($activity['description']): ?>
                        - <?php echo htmlspecialchars($activity['description']); ?>
                    <?php endif; ?>
                    <br>
                    <span class="time"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="color: #95a5a6;">No recent activity</p>
    <?php endif; ?>
</div>

<div class="section">
    <h2>Recent Contacts</h2>
    <?php if (!empty($recent_contacts)): ?>
        <ul class="contact-list">
            <?php foreach ($recent_contacts as $contact): ?>
                <li>
                    <span class="badge <?php echo htmlspecialchars($contact['status']); ?>">
                        <?php echo htmlspecialchars($contact['status']); ?>
                    </span>
                    <span class="name"><?php echo htmlspecialchars($contact['name']); ?></span>
                    <span class="email">&lt;<?php echo htmlspecialchars($contact['email']); ?>&gt;</span>
                    <?php if ($contact['subject']): ?>
                        <br>Subject: <?php echo htmlspecialchars($contact['subject']); ?>
                    <?php endif; ?>
                    <br>
                    <span class="time"><?php echo date('M d, Y H:i', strtotime($contact['created_at'])); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="contacts.php" style="display: inline-block; margin-top: 1rem; color: #3498db;">View all contacts →</a>
    <?php else: ?>
        <p style="color: #95a5a6;">No contacts yet</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
