<?php
require_once '../inc/database.php';
require_once '../inc/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

 $method = $_SERVER['REQUEST_METHOD'];
 $action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($action);
            break;
        case 'POST':
            handlePost($action);
            break;
        case 'PUT':
            handlePut($action);
            break;
        case 'DELETE':
            handleDelete($action);
            break;
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function handleGet($action) {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'content':
            $section = $_GET['section'] ?? '';
            if ($section) {
                $content = $db->fetchAll(
                    "SELECT content_key, content_value FROM content WHERE section = ?",
                    [$section]
                );
                $result = [];
                foreach ($content as $item) {
                    $result[$item['content_key']] = $item['content_value'];
                }
                jsonResponse($result);
            } else {
                $content = $db->fetchAll("SELECT * FROM content");
                jsonResponse($content);
            }
            break;
            
        case 'projects':
            jsonResponse(getAllProjects());
            break;
            
        case 'tools':
            jsonResponse(getAllTools());
            break;
            
        case 'skills':
            jsonResponse(getAllSkills());
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePost($action) {
    requireLogin();
    
    switch ($action) {
        case 'project':
            $data = json_decode(file_get_contents('php://input'), true);
            $db = Database::getInstance();
            $id = $db->insert('projects', [
                'title' => sanitize($data['title']),
                'description' => sanitize($data['description']),
                'image_url' => sanitize($data['image_url']),
                'tags' => json_encode($data['tags']),
                'project_url' => sanitize($data['project_url']),
                'featured' => $data['featured'] ? 1 : 0,
                'order_index' => $data['order_index'] ?? 0
            ]);
            jsonResponse(['success' => true, 'id' => $id]);
            break;
            
        case 'tool':
            $data = json_decode(file_get_contents('php://input'), true);
            $db = Database::getInstance();
            $id = $db->insert('tools', [
                'name' => sanitize($data['name']),
                'icon' => sanitize($data['icon']),
                'description' => sanitize($data['description']),
                'order_index' => $data['order_index'] ?? 0
            ]);
            jsonResponse(['success' => true, 'id' => $id]);
            break;
            
        case 'skill':
            $data = json_decode(file_get_contents('php://input'), true);
            $db = Database::getInstance();
            $id = $db->insert('skills', [
                'name' => sanitize($data['name']),
                'category' => sanitize($data['category']),
                'order_index' => $data['order_index'] ?? 0
            ]);
            jsonResponse(['success' => true, 'id' => $id]);
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePut($action) {
    requireLogin();
    
    switch ($action) {
        case 'content':
            $data = json_decode(file_get_contents('php://input'), true);
            setContent($data['section'], $data['key'], $data['value']);
            jsonResponse(['success' => true]);
            break;
            
        case 'project':
            $data = json_decode(file_get_contents('php://input'), true);
            $db = Database::getInstance();
            $db->update('projects', [
                'title' => sanitize($data['title']),
                'description' => sanitize($data['description']),
                'image_url' => sanitize($data['image_url']),
                'tags' => json_encode($data['tags']),
                'project_url' => sanitize($data['project_url']),
                'featured' => $data['featured'] ? 1 : 0,
                'order_index' => $data['order_index'] ?? 0
            ], 'id = ?', [$data['id']]);
            jsonResponse(['success' => true]);
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handleDelete($action) {
    requireLogin();
    
    switch ($action) {
        case 'project':
            $id = $_GET['id'] ?? '';
            if ($id) {
                $db = Database::getInstance();
                $db->delete('projects', 'id = ?', [$id]);
                jsonResponse(['success' => true]);
            } else {
                jsonResponse(['error' => 'ID required'], 400);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

case 'tool':
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();
        $id = $db->insert('tools', [
            'name' => sanitize($data['name']),
            'icon' => sanitize($data['icon']),
            'description' => sanitize($data['description']),
            'order_index' => $data['order_index'] ?? 0
        ]);
        jsonResponse(['success' => true, 'id' => $id]);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();
        $db->update('tools', [
            'name' => sanitize($data['name']),
            'icon' => sanitize($data['icon']),
            'description' => sanitize($data['description']),
            'order_index' => $data['order_index'] ?? 0
        ], 'id = ?', [$data['id']]);
        jsonResponse(['success' => true]);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $db = Database::getInstance();
            $db->delete('tools', 'id = ?', [$id]);
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'ID required'], 400);
        }
    }
    break;

case 'skill':
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();
        $id = $db->insert('skills', [
            'name' => sanitize($data['name']),
            'category' => sanitize($data['category']),
            'order_index' => $data['order_index'] ?? 0
        ]);
        jsonResponse(['success' => true, 'id' => $id]);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();
        $db->update('skills', [
            'name' => sanitize($data['name']),
            'category' => sanitize($data['category']),
            'order_index' => $data['order_index'] ?? 0
        ], 'id = ?', [$data['id']]);
        jsonResponse(['success' => true]);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $db = Database::getInstance();
            $db->delete('skills', 'id = ?', [$id]);
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'ID required'], 400);
        }
    }
    break;
?>