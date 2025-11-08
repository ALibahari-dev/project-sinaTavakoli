<?php
require_once 'database.php';

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getContent($section, $key) {
    $db = Database::getInstance();
    $result = $db->fetchOne(
        "SELECT content_value FROM content WHERE section = ? AND content_key = ?",
        [$section, $key]
    );
    return $result ? $result['content_value'] : '';
}

function setContent($section, $key, $value) {
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO content (section, content_key, content_value) 
         VALUES (?, ?, ?) 
         ON DUPLICATE KEY UPDATE content_value = VALUES(content_value)",
        [$section, $key, $value]
    );
}

function getAllProjects() {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM projects ORDER BY order_index ASC, created_at DESC"
    );
}

function getAllTools() {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM tools ORDER BY order_index ASC"
    );
}

function getAllSkills() {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM skills ORDER BY order_index ASC"
    );
}

function uploadImage($file, $targetDir = "uploads/") {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Invalid file parameters');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    
    if (!in_array($finfo->file($file['tmp_name']), $allowedTypes)) {
        throw new RuntimeException('Invalid file type');
    }

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $targetPath = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Failed to move uploaded file');
    }

    return $targetPath;
}
?>