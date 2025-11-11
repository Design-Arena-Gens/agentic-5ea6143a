<?php
/**
 * Contact Form API
 * POST /backend/api/contact.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Method not allowed', 405);
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_response('Invalid JSON data', 400);
    }

    // Validate inputs
    $errors = [];

    $name = sanitize_input($data['name'] ?? '');
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }

    $email = sanitize_input($data['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!validate_email($email)) {
        $errors['email'] = 'Invalid email format';
    }

    $phone = sanitize_input($data['phone'] ?? '');
    $company = sanitize_input($data['company'] ?? '');
    $subject = sanitize_input($data['subject'] ?? '');

    $message = sanitize_input($data['message'] ?? '');
    if (empty($message)) {
        $errors['message'] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $errors['message'] = 'Message must be at least 10 characters';
    }

    if (!empty($errors)) {
        error_response('Validation failed', 422, $errors);
    }

    // Rate limiting - max 3 submissions per hour from same IP
    $ip = get_client_ip();
    $hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));

    $recent_count = db()->fetchColumn(
        'SELECT COUNT(*) FROM contacts WHERE ip_address = :ip AND created_at > :time',
        [':ip' => $ip, ':time' => $hour_ago]
    );

    if ($recent_count >= 3) {
        error_response('Too many submissions. Please try again later.', 429);
    }

    // Insert contact
    $contact_id = db()->insert('contacts', [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company' => $company,
        'subject' => $subject,
        'message' => $message,
        'status' => 'new',
        'ip_address' => $ip,
        'user_agent' => get_user_agent()
    ]);

    // TODO: Send email notification to admin

    success_response('Thank you for contacting us. We will get back to you soon.', [
        'contact_id' => $contact_id
    ], 201);

} catch (Exception $e) {
    error_log('Contact API error: ' . $e->getMessage());
    error_response('An error occurred. Please try again later.', 500);
}
