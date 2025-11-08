<?php
require_once '../inc/database.php';
require_once '../inc/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

 $action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'upload':
            handleUpload();
            break;
        case 'delete':
            handleDelete();
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function handleUpload() {
    if (!isset($_FILES['images']) || !is_array($_FILES['images']['name'])) {
        jsonResponse(['error' => 'No files uploaded'], 400);
    }
    
    $db = Database::getInstance();
    $uploadedFiles = [];
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    foreach ($_FILES['images']['name'] as $i => $name) {
        $tmpName = $_FILES['images']['tmp_name'][$i];
        $error = $_FILES['images']['error'][$i];
        $size = $_FILES['images']['size'][$i];
        
        if ($error === UPLOAD_ERR_OK) {
            // Generate unique filename
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            // Move file to uploads directory
            if (move_uploaded_file($tmpName, $filePath)) {
                // Save to database
                $imageId = $db->insert('uploaded_images', [
                    'file_name' => $name,
                    'file_path' => 'uploads/images/' . $filename,
                    'file_size' => $size,
                    'upload_date' => date('Y-m-d H:i:s')
                ]);
                
                $uploadedFiles[] = [
                    'id' => $imageId,
                    'name' => $name,
                    'path' => 'uploads/images/' . $filename
                ];
            }
        }
    }
    
    jsonResponse(['success' => true, 'files' => $uploadedFiles]);
}

function handleDelete() {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        jsonResponse(['error' => 'ID required'], 400);
    }
    
    $db = Database::getInstance();
    
    // Get image info before deleting
    $image = $db->fetchOne("SELECT * FROM uploaded_images WHERE id = ?", [$id]);
    
    if (!$image) {
        jsonResponse(['error' => 'Image not found'], 404);
    }
    
    // Delete file from filesystem
    $filePath = '../' . $image['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete from database
    $db->delete('uploaded_images', 'id = ?', [$id]);
    
    jsonResponse(['success' => true]);
}
?>