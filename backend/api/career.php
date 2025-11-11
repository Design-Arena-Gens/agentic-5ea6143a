<?php
/**
 * Career Application API
 * GET /backend/api/career.php - List open positions
 * POST /backend/api/career.php - Submit application
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // GET - List open positions
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $positions = db()->fetchAll(
            'SELECT id, position, department, location, type, description, requirements, salary_range
             FROM careers
             WHERE status = "open"
             ORDER BY created_at DESC'
        );

        success_response('Open positions retrieved successfully', [
            'positions' => $positions,
            'total' => count($positions)
        ]);
    }

    // POST - Submit application
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Parse multipart/form-data
        $career_id = sanitize_input($_POST['career_id'] ?? '');
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $cover_letter = sanitize_input($_POST['cover_letter'] ?? '');
        $linkedin = sanitize_input($_POST['linkedin'] ?? '');
        $portfolio = sanitize_input($_POST['portfolio'] ?? '');

        // Validate inputs
        $errors = [];

        if (empty($career_id)) {
            $errors['career_id'] = 'Position is required';
        } else {
            $position = db()->fetchOne(
                'SELECT id FROM careers WHERE id = :id AND status = "open"',
                [':id' => $career_id]
            );
            if (!$position) {
                $errors['career_id'] = 'Invalid or closed position';
            }
        }

        if (empty($full_name)) {
            $errors['full_name'] = 'Full name is required';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!validate_email($email)) {
            $errors['email'] = 'Invalid email format';
        }

        // Check for duplicate application
        if (empty($errors)) {
            $existing = db()->fetchOne(
                'SELECT id FROM career_applications WHERE career_id = :career_id AND email = :email',
                [':career_id' => $career_id, ':email' => $email]
            );
            if ($existing) {
                $errors['email'] = 'You have already applied for this position';
            }
        }

        // Handle CV upload
        $cv_file = null;
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cv'];

            // Validate file
            if ($file['size'] > UPLOAD_MAX_SIZE) {
                $errors['cv'] = 'File too large. Maximum size: ' . format_file_size(UPLOAD_MAX_SIZE);
            } elseif (!in_array($file['type'], ALLOWED_CV_TYPES)) {
                $errors['cv'] = 'Invalid file type. Allowed: PDF, DOC, DOCX';
            } else {
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $cv_file = uniqid('cv_') . '.' . $ext;
                $upload_path = UPLOAD_PATH . 'cv/' . $cv_file;

                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $errors['cv'] = 'Failed to upload file';
                    $cv_file = null;
                }
            }
        } else {
            $errors['cv'] = 'CV file is required';
        }

        if (!empty($errors)) {
            error_response('Validation failed', 422, $errors);
        }

        // Rate limiting
        $ip = get_client_ip();
        $hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));

        $recent_count = db()->fetchColumn(
            'SELECT COUNT(*) FROM career_applications WHERE ip_address = :ip AND created_at > :time',
            [':ip' => $ip, ':time' => $hour_ago]
        );

        if ($recent_count >= 2) {
            error_response('Too many applications. Please try again later.', 429);
        }

        // Insert application
        $application_id = db()->insert('career_applications', [
            'career_id' => $career_id,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'cv_file' => $cv_file,
            'cover_letter' => $cover_letter,
            'linkedin' => $linkedin,
            'portfolio' => $portfolio,
            'status' => 'pending',
            'ip_address' => $ip
        ]);

        // TODO: Send confirmation email

        success_response('Application submitted successfully. We will review and contact you soon.', [
            'application_id' => $application_id
        ], 201);
    }

    error_response('Method not allowed', 405);

} catch (Exception $e) {
    error_log('Career API error: ' . $e->getMessage());
    error_response('An error occurred. Please try again later.', 500);
}
