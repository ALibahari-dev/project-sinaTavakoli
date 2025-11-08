<?php
session_start();
require_once '../db.php';

// Check user login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get site information
 $stmt = $pdo->query("SELECT * FROM site_info LIMIT 1");
 $siteInfo = $stmt->fetch();

// Get projects
 $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
 $projects = $stmt->fetchAll();

// Get tools
 $stmt = $pdo->query("SELECT * FROM tools ORDER BY name");
 $tools = $stmt->fetchAll();

// Get visitor statistics
 $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as total_visitors FROM visitors");
 $visitorCount = $stmt->fetch()['total_visitors'];

// Get today's visitors
 $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as today_visitors FROM visitors WHERE visit_date = CURDATE()");
 $todayVisitors = $stmt->fetch()['today_visitors'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0a0e27;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            background: rgba(10, 14, 39, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            z-index: 100;
            padding-top: 60px;
        }
        .main-content {
            margin-left: 250px;
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
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: #f9c74f;
            background: rgba(249, 199, 79, 0.1);
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
        .table {
            color: #fff;
        }
        .table th {
            border-color: rgba(255, 255, 255, 0.1);
            color: #f9c74f;
        }
        .table td {
            border-color: rgba(255, 255, 255, 0.05);
        }
        .stats-card {
            background: linear-gradient(135deg, rgba(249, 199, 79, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            border: 1px solid rgba(249, 199, 79, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #f9c74f 0%, #ffd166 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .top-bar {
            background: rgba(10, 14, 39, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 250px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 99;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .top-bar {
                right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <h4 class="mb-0">Admin Dashboard</h4>
        <div class="d-flex align-items-center gap-3">
            <span>Welcome, Admin</span>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="p-3">
            <h5 class="text-center mb-4">Admin Panel</h5>
            <nav class="nav flex-column">
                <a href="#dashboard" class="nav-link active" data-bs-toggle="pill">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#site-info" class="nav-link" data-bs-toggle="pill">
                    <i class="fas fa-info-circle"></i>
                    <span>Site Info</span>
                </a>
                <a href="#projects" class="nav-link" data-bs-toggle="pill">
                    <i class="fas fa-briefcase"></i>
                    <span>Projects</span>
                </a>
                <a href="#tools" class="nav-link" data-bs-toggle="pill">
                    <i class="fas fa-tools"></i>
                    <span>Tools</span>
                </a>
                <a href="#visitors" class="nav-link" data-bs-toggle="pill">
                    <i class="fas fa-users"></i>
                    <span>Visitors</span>
                </a>
            </nav>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="tab-content pt-5">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard">
                <h2 class="mb-4">Overview</h2>
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <h3><?php echo count($projects); ?></h3>
                            <p>Total Projects</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <h3><?php echo count($tools); ?></h3>
                            <p>Total Tools</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <h3><?php echo $visitorCount; ?></h3>
                            <p>Total Visitors</p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <h3><?php echo $todayVisitors; ?></h3>
                            <p>Today's Visitors</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Projects</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recentProjects = array_slice($projects, 0, 5);
                                    foreach ($recentProjects as $project): 
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Site Info Tab -->
            <div class="tab-pane fade" id="site-info">
                <h2 class="mb-4">Site Information</h2>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Site Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="update_site_info.php" method="post">
                            <div class="mb-3">
                                <label for="title" class="form-label">Site Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($siteInfo['title'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="subtitle" class="form-label">Subtitle</label>
                                <textarea class="form-control" id="subtitle" name="subtitle" rows="3"><?php echo htmlspecialchars($siteInfo['subtitle'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="about_text" class="form-label">About Text</label>
                                <textarea class="form-control" id="about_text" name="about_text" rows="5"><?php echo htmlspecialchars($siteInfo['about_text'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($siteInfo['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($siteInfo['phone'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="linkedin" class="form-label">LinkedIn</label>
                                <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($siteInfo['linkedin'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="dribbble" class="form-label">Dribbble</label>
                                <input type="url" class="form-control" id="dribbble" name="dribbble" value="<?php echo htmlspecialchars($siteInfo['dribbble'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="behance" class="form-label">Behance</label>
                                <input type="url" class="form-control" id="behance" name="behance" value="<?php echo htmlspecialchars($siteInfo['behance'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="github" class="form-label">GitHub</label>
                                <input type="url" class="form-control" id="github" name="github" value="<?php echo htmlspecialchars($siteInfo['github'] ?? ''); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Projects Tab -->
            <div class="tab-pane fade" id="projects">
                <h2 class="mb-4">Manage Projects</h2>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Projects List</h5>
                        <a href="add_project.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Project
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Tags</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($project['tags']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tools Tab -->
            <div class="tab-pane fade" id="tools">
                <h2 class="mb-4">Manage Tools</h2>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Tools List</h5>
                        <a href="add_tool.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Tool
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Icon</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tools as $tool): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tool['name']); ?></td>
                                        <td><?php echo htmlspecialchars($tool['icon']); ?></td>
                                        <td><?php echo htmlspecialchars($tool['description']); ?></td>
                                        <td>
                                            <a href="edit_tool.php?id=<?php echo $tool['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_tool.php?id=<?php echo $tool['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this tool?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Visitors Tab -->
            <div class="tab-pane fade" id="visitors">
                <h2 class="mb-4">Visitor Statistics</h2>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Visit Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="stats-card">
                                    <h3><?php echo $visitorCount; ?></h3>
                                    <p>Total Visitors</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stats-card">
                                    <h3><?php echo $todayVisitors; ?></h3>
                                    <p>Today's Visitors</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>IP Address</th>
                                        <th>Visit Date</th>
                                        <th>Page Visited</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stmt = $pdo->query("SELECT * FROM visitors ORDER BY created_at DESC LIMIT 50");
                                    $visitors = $stmt->fetchAll();
                                    foreach ($visitors as $visitor): 
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($visitor['ip_address']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($visitor['visit_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['page_visited']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const topBar = document.querySelector('.top-bar');
            
            // Add mobile menu button to top bar
            const mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.className = 'btn btn-outline-light d-md-none';
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            topBar.appendChild(mobileMenuBtn);
            
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        });
    </script>
</body>
</html>