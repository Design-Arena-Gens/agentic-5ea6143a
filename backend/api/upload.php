<?php
/**
 * File Upload API
 * POST /backend/api/upload.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

// Require admin authentication
require_auth(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Method not allowed', 405);
}

try {
    $type = sanitize_input($_POST['type'] ?? ''); // cv, news, products

    if (!in_array($type, ['cv', 'news', 'products'])) {
        error_response('Invalid upload type', 400);
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        error_response('No file uploaded', 400);
    }

    $file = $_FILES['file'];

    // Validate file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        error_response('File too large. Maximum: ' . format_file_size(UPLOAD_MAX_SIZE), 413);
    }

    // Validate file type
    $allowed_types = [];
    if ($type === 'cv') {
        $allowed_types = ALLOWED_CV_TYPES;
    } else {
        $allowed_types = ALLOWED_IMAGE_TYPES;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        error_response('Invalid file type', 400);
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($type . '_') . '.' . $ext;
    $upload_dir = UPLOAD_PATH . $type . '/';

    // Create directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $upload_path = $upload_dir . $filename;

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        error_response('Failed to upload file', 500);
    }

    // Optimize images
    if (in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
        optimize_image($upload_path, $mime_type);
    }

    log_activity('upload', 'file', null, "Uploaded file: $filename");

    success_response('File uploaded successfully', [
        'filename' => $filename,
        'url' => '/backend/uploads/' . $type . '/' . $filename,
        'size' => $file['size'],
        'type' => $mime_type
    ], 201);

} catch (Exception $e) {
    error_log('Upload API error: ' . $e->getMessage());
    error_response('Upload failed', 500);
}

/**
 * Basic image optimization
 */
function optimize_image($path, $mime_type) {
    try {
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                if ($image) {
                    imagejpeg($image, $path, 85);
                    imagedestroy($image);
                }
                break;
            case 'image/png':
                $image = imagecreatefrompng($path);
                if ($image) {
                    imagepng($image, $path, 8);
                    imagedestroy($image);
                }
                break;
        }
    } catch (Exception $e) {
        error_log('Image optimization error: ' . $e->getMessage());
    }
}
