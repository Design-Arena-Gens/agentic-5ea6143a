<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

$current_user = get_current_user();
$page_title = $page_title ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Core Official</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { font-size: 1.5rem; }
        .header .user-info { display: flex; align-items: center; gap: 1rem; }
        .header .user-info span { font-size: 0.9rem; }
        .header a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: #34495e; border-radius: 4px; transition: background 0.3s; }
        .header a:hover { background: #1abc9c; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .nav { background: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .nav ul { list-style: none; display: flex; gap: 1rem; flex-wrap: wrap; }
        .nav a { color: #2c3e50; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.3s; }
        .nav a:hover, .nav a.active { background: #3498db; color: white; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Core Official - <?php echo htmlspecialchars($page_title); ?></h1>
        <div class="user-info">
            <?php if ($current_user): ?>
                <span>Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?> (<?php echo htmlspecialchars($current_user['role']); ?>)</span>
                <a href="/backend/auth/logout.php">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (is_admin()): ?>
    <div class="container">
        <nav class="nav">
            <ul>
                <li><a href="/backend/admin/index.php" <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
                <li><a href="/backend/admin/ecosystem.php" <?php echo basename($_SERVER['PHP_SELF']) === 'ecosystem.php' ? 'class="active"' : ''; ?>>Ecosystem</a></li>
                <li><a href="/backend/admin/products.php" <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'class="active"' : ''; ?>>Products</a></li>
                <li><a href="/backend/admin/news.php" <?php echo basename($_SERVER['PHP_SELF']) === 'news.php' ? 'class="active"' : ''; ?>>News</a></li>
                <li><a href="/backend/admin/contacts.php" <?php echo basename($_SERVER['PHP_SELF']) === 'contacts.php' ? 'class="active"' : ''; ?>>Contacts</a></li>
                <li><a href="/backend/admin/careers.php" <?php echo basename($_SERVER['PHP_SELF']) === 'careers.php' ? 'class="active"' : ''; ?>>Careers</a></li>
                <li><a href="/backend/admin/users.php" <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'class="active"' : ''; ?>>Users</a></li>
            </ul>
        </nav>
    <?php else: ?>
    <div class="container">
    <?php endif; ?>
