<?php
/**
 * Authentication Check Middleware
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Check if user is authenticated
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return is_authenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require authentication
 */
function require_auth($admin_only = false) {
    if (!is_authenticated()) {
        if (is_ajax_request()) {
            error_response('Authentication required', 401);
        } else {
            header('Location: /backend/auth/login.php');
            exit;
        }
    }

    if ($admin_only && !is_admin()) {
        if (is_ajax_request()) {
            error_response('Admin access required', 403);
        } else {
            header('Location: /backend/admin/index.php');
            exit;
        }
    }

    // Update last activity
    $_SESSION['last_activity'] = time();
}

/**
 * Check if request is AJAX
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get current user
 */
function get_current_user() {
    if (!is_authenticated()) {
        return null;
    }

    try {
        $user = db()->fetchOne(
            'SELECT id, email, full_name, role, status FROM users WHERE id = :id',
            [':id' => $_SESSION['user_id']]
        );

        return $user ?: null;
    } catch (Exception $e) {
        error_log('Get current user error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Log activity
 */
function log_activity($action, $entity_type = null, $entity_id = null, $description = null) {
    if (!is_authenticated()) {
        return;
    }

    try {
        db()->insert('activity_logs', [
            'user_id' => $_SESSION['user_id'],
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'description' => $description,
            'ip_address' => get_client_ip(),
            'user_agent' => get_user_agent()
        ]);
    } catch (Exception $e) {
        error_log('Activity log error: ' . $e->getMessage());
    }
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    if (is_authenticated()) {
        $last_activity = $_SESSION['last_activity'] ?? 0;
        if ((time() - $last_activity) > SESSION_LIFETIME) {
            session_destroy();
            if (is_ajax_request()) {
                error_response('Session expired', 401);
            } else {
                header('Location: /backend/auth/login.php?expired=1');
                exit;
            }
        }
    }
}

// Check session timeout on every request
check_session_timeout();
