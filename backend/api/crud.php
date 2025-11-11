<?php
/**
 * Generic CRUD API for authenticated users
 * Supports: ecosystem, products, news
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

// Require admin authentication
require_auth(true);

$allowed_tables = ['ecosystem', 'products', 'news', 'careers'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Parse request
    $uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $table = sanitize_input($_GET['table'] ?? '');
    $id = sanitize_input($_GET['id'] ?? '');

    if (!in_array($table, $allowed_tables)) {
        error_response('Invalid table', 400);
    }

    // GET - List or single item
    if ($method === 'GET') {
        if ($id) {
            $item = db()->fetchOne("SELECT * FROM $table WHERE id = :id", [':id' => $id]);
            if (!$item) {
                error_response('Item not found', 404);
            }
            success_response('Item retrieved', ['item' => $item]);
        } else {
            $limit = min((int)($_GET['limit'] ?? 50), 100);
            $offset = (int)($_GET['offset'] ?? 0);
            $status = sanitize_input($_GET['status'] ?? '');

            $where = [];
            $params = [];

            if ($status) {
                $where[] = 'status = :status';
                $params[':status'] = $status;
            }

            $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $items = db()->fetchAll(
                "SELECT * FROM $table $where_clause ORDER BY id DESC LIMIT :limit OFFSET :offset",
                array_merge($params, [':limit' => $limit, ':offset' => $offset])
            );

            $total = db()->fetchColumn(
                "SELECT COUNT(*) FROM $table $where_clause",
                $params
            );

            success_response('Items retrieved', [
                'items' => $items,
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
    }

    // POST - Create
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
            error_response('Invalid CSRF token', 403);
        }

        unset($data['csrf_token']);

        // Add metadata
        $data['created_by'] = $_SESSION['user_id'];

        // Table-specific processing
        if ($table === 'news' && !isset($data['slug']) && isset($data['title'])) {
            $data['slug'] = generate_slug($data['title']);
        }

        // Convert arrays to JSON
        foreach (['features', 'specifications', 'tags'] as $json_field) {
            if (isset($data[$json_field]) && is_array($data[$json_field])) {
                $data[$json_field] = json_encode($data[$json_field]);
            }
        }

        $id = db()->insert($table, $data);

        log_activity('create', $table, $id, "Created new $table item");

        success_response('Item created', ['id' => $id], 201);
    }

    // PUT/PATCH - Update
    if ($method === 'PUT' || $method === 'PATCH') {
        if (!$id) {
            error_response('ID is required', 400);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
            error_response('Invalid CSRF token', 403);
        }

        unset($data['csrf_token'], $data['id'], $data['created_by'], $data['created_at']);

        // Convert arrays to JSON
        foreach (['features', 'specifications', 'tags'] as $json_field) {
            if (isset($data[$json_field]) && is_array($data[$json_field])) {
                $data[$json_field] = json_encode($data[$json_field]);
            }
        }

        $affected = db()->update($table, $data, 'id = :id', [':id' => $id]);

        if ($affected === 0) {
            error_response('Item not found or no changes made', 404);
        }

        log_activity('update', $table, $id, "Updated $table item");

        success_response('Item updated', ['affected' => $affected]);
    }

    // DELETE
    if ($method === 'DELETE') {
        if (!$id) {
            error_response('ID is required', 400);
        }

        $affected = db()->delete($table, 'id = :id', [':id' => $id]);

        if ($affected === 0) {
            error_response('Item not found', 404);
        }

        log_activity('delete', $table, $id, "Deleted $table item");

        success_response('Item deleted', ['affected' => $affected]);
    }

    error_response('Method not allowed', 405);

} catch (Exception $e) {
    error_log('CRUD API error: ' . $e->getMessage());
    error_response('An error occurred', 500);
}
