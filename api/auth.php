<?php
require_once '../inc/database.php';
require_once '../inc/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

 $action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'update-password':
            requireLogin();
            updatePassword();
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function updatePassword() {
    $data = json_decode(file_get_contents('php://input'), true);
    $password = $data['password'] ?? '';
    
    if (empty($password)) {
        jsonResponse(['error' => 'Password is required'], 400);
    }
    
    if (strlen($password) < 6) {
        jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }
    
    $db = Database::getInstance();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $db->update('admin', ['password' => $hashedPassword], 'id = ?', [$_SESSION['admin_id']]);
    
    jsonResponse(['success' => true]);
}
?>