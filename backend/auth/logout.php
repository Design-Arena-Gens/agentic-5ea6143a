<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Log activity before destroying session
if (isset($_SESSION['user_id'])) {
    try {
        db()->insert('activity_logs', [
            'user_id' => $_SESSION['user_id'],
            'action' => 'logout',
            'description' => 'User logged out',
            'ip_address' => get_client_ip(),
            'user_agent' => get_user_agent()
        ]);
    } catch (Exception $e) {
        error_log('Logout logging error: ' . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
