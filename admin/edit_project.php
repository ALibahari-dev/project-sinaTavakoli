<?php
session_start();
require_once '../db.php';

// Check user login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get project information
if (!isset($_GET['id'])) {
    header('Location: dashboard.php#projects');
    exit;
}

 $projectId = $_GET['id'];
 $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
 $stmt->execute([$projectId]);
 $project = $stmt->fetch();

if (!$project) {
    header('Location: dashboard.php#projects');
    exit;
}

// Process edit project form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $image = $_POST['image'];
    $tags = $_POST['tags'];
    
    $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, image = ?, tags = ? WHERE id = ?");
    $stmt->execute([$title, $description, $image, $tags, $projectId]);
    
    header('Location: dashboard.php#projects');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0a0e27;
            color: #fff;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .card-header {
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f9c74f 0%, #ffd166 100%);
            color: #0a0e27;
            font-weight: 600;
            border: none;
            border-radius: 10px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(249, 199, 79, 0.3);
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 10px;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #f9c74f;
            box-shadow: 0 0 0 0.25rem rgba(249, 199, 79, 0.25);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit Project</h2>
            <a href="dashboard.php#projects" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Project Information</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="title" class="form-label">Project Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Image URL</label>
                        <input type="text" class="form-control" id="image" name="image" value="<?php echo htmlspecialchars($project['image']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags (comma separated)</label>
                        <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($project['tags']); ?>" placeholder="e.g., UI Design, 3D, Motion">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>