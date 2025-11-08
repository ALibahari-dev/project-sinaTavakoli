<?php
session_start();
require_once '../inc/functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get data from database
 $heroContent = [];
 $aboutContent = [];
 $contactContent = [];
 $stats = [];
 $projects = [];
 $tools = [];
 $skills = [];
 $visitors = [];

try {
    require_once '../inc/database.php';
    $db = Database::getInstance();
    
    // Get hero content
    $heroData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'hero'");
    foreach ($heroData as $item) {
        $heroContent[$item['content_key']] = $item['content_value'];
    }
    
    // Get about content
    $aboutData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'about'");
    foreach ($aboutData as $item) {
        $aboutContent[$item['content_key']] = $item['content_value'];
    }
    
    // Get contact content
    $contactData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'contact'");
    foreach ($contactData as $item) {
        $contactContent[$item['content_key']] = $item['content_value'];
    }
    
    // Get stats
    $statsData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'stats'");
    foreach ($statsData as $item) {
        $stats[$item['content_key']] = $item['content_value'];
    }
    
    // Get projects
    $projects = $db->fetchAll("SELECT * FROM projects ORDER BY order_index ASC, created_at DESC");
    
    // Get tools
    $tools = $db->fetchAll("SELECT * FROM tools ORDER BY order_index ASC");
    
    // Get skills
    $skills = $db->fetchAll("SELECT * FROM skills ORDER BY order_index ASC");
    
    // Get visitor statistics
    $visitors = $db->fetchAll("SELECT * FROM visitors ORDER BY visit_date DESC LIMIT 10");
    $totalVisitors = $db->fetchOne("SELECT COUNT(*) as count FROM visitors");
    $todayVisitors = $db->fetchOne("SELECT COUNT(*) as count FROM visitors WHERE DATE(visit_date) = CURDATE()");
    $thisMonthVisitors = $db->fetchOne("SELECT COUNT(*) as count FROM visitors WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())");
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error loading data from database";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sina Portfolio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-active {
            background: rgba(251, 191, 36, 0.1);
            border-left: 3px solid #fbbf24;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(251, 191, 36, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 2000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.success {
            background: #10b981;
        }
        
        .toast.error {
            background: #ef4444;
        }
        
        .loading {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        .upload-area {
            border: 2px dashed #4b5563;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: rgba(75, 85, 99, 0.1);
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #fbbf24;
            background: rgba(251, 191, 36, 0.1);
        }
        
        .upload-area.dragover {
            border-color: #fbbf24;
            background: rgba(251, 191, 36, 0.2);
        }
        
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin: 5px;
            border: 2px solid #374151;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .image-preview:hover {
            border-color: #fbbf24;
            transform: scale(1.05);
        }
        
        .image-preview.selected {
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.3);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>
    
    <!-- Header -->
    <header class="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button id="sidebarToggle" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold">Portfolio Dashboard</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../" target="_blank" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-external-link-alt mr-2"></i>View Site
                </a>
                <div class="flex items-center space-x-2">
                    <img src="https://picsum.photos/seed/admin/40/40.jpg" alt="Admin" class="w-8 h-8 rounded-full">
                    <span class="hidden md:block"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
                <a href="logout.php" class="text-red-400 hover:text-red-300 transition">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>
    
    <div class="flex h-screen pt-16">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-gray-800 border-r border-gray-700 fixed lg:relative h-full z-40 transform -translate-x-full lg:translate-x-0 transition-transform">
            <nav class="p-4 space-y-2">
                <a href="#dashboard" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition sidebar-active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#content" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition" data-section="content">
                    <i class="fas fa-edit w-5"></i>
                    <span>Content</span>
                </a>
                <a href="#projects" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition" data-section="projects">
                    <i class="fas fa-project-diagram w-5"></i>
                    <span>Projects</span>
                </a>
                <a href="#tools" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition" data-section="tools">
                    <i class="fas fa-tools w-5"></i>
                    <span>Tools</span>
                </a>
                <a href="#skills" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition" data-section="skills">
                    <i class="fas fa-cogs w-5"></i>
                    <span>Skills</span>
                </a>
                <a href="#library" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition" data-section="library">
                    <i class="fas fa-images w-5"></i>
                    <span>Image Library</span>
                </a>
                <a href="#visitors" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition" data-section="visitors">
                    <i class="fas fa-users w-5"></i>
                    <span>Visitors</span>
                </a>
                <a href="#settings" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition" data-section="settings">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto lg:ml-0">
            <div class="p-6">
                <!-- Dashboard Section -->
                <section id="dashboard-section" class="content-section">
                    <h2 class="text-2xl font-bold mb-6">Dashboard Overview</h2>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-gray-800 p-6 rounded-lg card-hover">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Total Projects</p>
                                    <p class="text-3xl font-bold text-yellow-400"><?php echo count($projects); ?></p>
                                </div>
                                <i class="fas fa-project-diagram text-3xl text-yellow-400 opacity-50"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 p-6 rounded-lg card-hover">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Tools</p>
                                    <p class="text-3xl font-bold text-blue-400"><?php echo count($tools); ?></p>
                                </div>
                                <i class="fas fa-tools text-3xl text-blue-400 opacity-50"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 p-6 rounded-lg card-hover">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Skills</p>
                                    <p class="text-3xl font-bold text-green-400"><?php echo count($skills); ?></p>
                                </div>
                                <i class="fas fa-cogs text-3xl text-green-400 opacity-50"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 p-6 rounded-lg card-hover">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Total Visitors</p>
                                    <p class="text-3xl font-bold text-purple-400"><?php echo $totalVisitors['count'] ?? '0'; ?></p>
                                </div>
                                <i class="fas fa-users text-3xl text-purple-400 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visitor Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4 text-yellow-400">Today's Visitors</h3>
                            <p class="text-3xl font-bold text-yellow-400"><?php echo $todayVisitors['count'] ?? '0'; ?></p>
                        </div>
                        
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4 text-blue-400">This Month</h3>
                            <p class="text-3xl font-bold text-blue-400"><?php echo $thisMonthVisitors['count'] ?? '0'; ?></p>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-gray-800 p-6 rounded-lg mb-8">
                        <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button onclick="openProjectModal()" class="btn-primary text-gray-900 px-4 py-3 rounded-lg font-medium">
                                <i class="fas fa-plus mr-2"></i>Add New Project
                            </button>
                            <button onclick="openToolModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg font-medium transition">
                                <i class="fas fa-plus mr-2"></i>Add New Tool
                            </button>
                            <button onclick="openSkillModal()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg font-medium transition">
                                <i class="fas fa-plus mr-2"></i>Add New Skill
                            </button>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold mb-4">Recent Projects</h3>
                        <div class="space-y-3">
                            <?php 
                            $recentProjects = array_slice($projects, 0, 5);
                            foreach ($recentProjects as $project): 
                            ?>
                                <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-12 h-12 rounded object-cover">
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($project['title']); ?></p>
                                            <p class="text-sm text-gray-400"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="editProject(<?php echo $project['id']; ?>)" class="text-blue-400 hover:text-blue-300">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteProject(<?php echo $project['id']; ?>)" class="text-red-400 hover:text-red-300">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                
                <!-- Content Section -->
                <section id="content-section" class="content-section hidden">
                    <h2 class="text-2xl font-bold mb-6">Content Management</h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Hero Section -->
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <h3 class="text-xl font-semibold mb-4 text-yellow-400">Hero Section</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-300 mb-2">Subtitle</label>
                                    <input type="text" id="hero-subtitle" value="<?php echo htmlspecialchars($heroContent['subtitle'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
                                </div>
                                <div>
                                    <label class="block text-gray-300 mb-2">Name</label>
                                    <input type="text" id="hero-name" value="<?php echo htmlspecialchars($heroContent['name'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
                                </div>
                                <div>
                                    <label class="block text-gray-300 mb-2">Title</label>
                                    <input type="text" id="hero-title" value="<?php echo htmlspecialchars($heroContent['title'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
                                </div>
                                <div>
                                    <label class="block text-gray-300 mb-2">Description</label>
                                    <textarea id="hero-description" rows="3" 
                                              class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400"><?php echo htmlspecialchars($heroContent['description'] ?? ''); ?></textarea>
                                </div>
                                <button onclick="saveContent('hero')" class="btn-primary text-gray-900 px-4 py-2 rounded-lg font-medium">
                                    <i class="fas fa-save mr-2"></i>Save Hero Content
                                </button>
                            </div>
                        </div>
                        
                        <!-- About Section -->
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <h3 class="text-xl font-semibold mb-4 text-yellow-400">About Section</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-300 mb-2">Title</label>
                                    <input type="text" id="about-title" value="<?php echo htmlspecialchars($aboutContent['title'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
                                </div>
                                <div>
                                    <label class="block text-gray-300 mb-2">Paragraph 1</label>
                                    <textarea id="about-paragraph1" rows="3" 
                                              class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400"><?php echo htmlspecialchars($aboutContent['paragraph1'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-gray-300 mb-2">Paragraph 2</label>
                                    <textarea id="about-paragraph2" rows="3" 
                                              class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400"><?php echo htmlspecialchars($aboutContent['paragraph2'] ?? ''); ?></textarea>
                                </div>
                                <button onclick="saveContent('about')" class="btn-primary text-gray-900 px-4 py-2 rounded-lg font-medium">
                                    <i class="fas fa-save mr-2"></i>Save About Content
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Projects Section -->
                <section id="projects-section" class="content-section hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Projects Management</h2>
                        <button onclick="openProjectModal()" class="btn-primary text-gray-900 px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-plus mr-2"></i>Add New Project
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="projects-grid">
                        <?php foreach ($projects as $project): ?>
                            <div class="bg-gray-800 rounded-lg overflow-hidden card-hover">
                                <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover">
                                <div class="p-4">
                                    <h4 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($project['title']); ?></h4>
                                    <p class="text-gray-400 text-sm mb-3"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?></p>
                                    <div class="flex justify-between items-center">
                                        <div class="flex gap-2">
                                            <?php 
                                            $tags = json_decode($project['tags'], true) ?: [];
                                            foreach (array_slice($tags, 0, 2) as $tag): 
                                            ?>
                                                <span class="text-xs bg-gray-700 px-2 py-1 rounded"><?php echo htmlspecialchars($tag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button onclick="editProject(<?php echo $project['id']; ?>)" class="text-blue-400 hover:text-blue-300">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteProject(<?php echo $project['id']; ?>)" class="text-red-400 hover:text-red-300">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <!-- Image Library Section -->
                <section id="library-section" class="content-section hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Image Library</h2>
                        <button onclick="document.getElementById('uploadInput').click()" class="btn-primary text-gray-900 px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-upload mr-2"></i>Upload Images
                        </button>
                    </div>
                    
                    <div class="upload-area mb-6" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt text-4xl mb-4 text-gray-400"></i>
                        <p class="text-lg mb-2">Drag & Drop images here</p>
                        <p class="text-sm text-gray-400">or click the button above to select files</p>
                        <p class="text-xs text-gray-500 mt-2">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</p>
                    </div>
                    
                    <input type="file" id="uploadInput" accept="image/*" multiple style="display: none;">
                    
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold mb-4">Uploaded Images</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="imageLibrary">
                            <?php
                            // Get uploaded images from database
                            $uploadedImages = $db->fetchAll("SELECT * FROM uploaded_images ORDER BY upload_date DESC");
                            foreach ($uploadedImages as $image):
                            ?>
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="<?php echo htmlspecialchars($image['file_name']); ?>" 
                                         class="image-preview" onclick="selectImage('<?php echo htmlspecialchars($image['file_path']); ?>')">
                                    <div class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center cursor-pointer opacity-0 hover:opacity-100 transition" 
                                         onclick="deleteImage(<?php echo $image['id']; ?>)">
                                        <i class="fas fa-times text-xs"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                
                <!-- Visitors Section -->
                <section id="visitors-section" class="content-section hidden">
                    <h2 class="text-2xl font-bold mb-6">Visitor Statistics</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4 text-yellow-400">Total Visitors</h3>
                            <p class="text-3xl font-bold text-yellow-400"><?php echo $totalVisitors['count'] ?? '0'; ?></p>
                        </div>
                        
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4 text-blue-400">Today's Visitors</h3>
                            <p class="text-3xl font-bold text-blue-400"><?php echo $todayVisitors['count'] ?? '0'; ?></p>
                        </div>
                        
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4 text-green-400">This Month</h3>
                            <p class="text-3xl font-bold text-green-400"><?php echo $thisMonthVisitors['count'] ?? '0'; ?></p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold mb-4">Recent Visitors</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-gray-700">
                                        <th class="px-4 py-2">IP Address</th>
                                        <th class="px-4 py-2">Page</th>
                                        <th class="px-4 py-2">Date</th>
                                        <th class="px-4 py-2">User Agent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visitors as $visitor): ?>
                                        <tr class="border-b border-gray-700">
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($visitor['ip_address']); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($visitor['page_visited']); ?></td>
                                            <td class="px-4 py-2"><?php echo date('M d, Y H:i', strtotime($visitor['visit_date'])); ?></td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(substr($visitor['user_agent'], 0, 50)) . '...'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <!-- Project Modal -->
    <div id="projectModal" class="modal">
        <div class="bg-gray-800 p-8 rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold">Add/Edit Project</h3>
                <button onclick="closeProjectModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="projectForm" class="space-y-4">
                <input type="hidden" id="projectId">
                <div>
                    <label class="block text-gray-300 mb-2">Title *</label>
                    <input type="text" id="projectTitle" required
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Description *</label>
                    <textarea id="projectDescription" rows="3" required
                              class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400"></textarea>
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Image URL</label>
                    <div class="flex">
                        <input type="text" id="projectImage"
                               class="flex-1 px-4 py-2 bg-gray-700 border border-gray-600 rounded-l-lg focus:outline-none focus:border-yellow-400">
                        <button type="button" onclick="openImageSelector()" 
                                class="px-4 py-2 bg-gray-700 border border-gray-600 border-l-0 rounded-r-lg hover:bg-gray-600 transition">
                            <i class="fas fa-images"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Tags (comma separated)</label>
                    <input type="text" id="projectTags"
                           class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
                </div>
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeProjectModal()" 
                            class="px-4 py-2 bg-gray-600 rounded-lg hover:bg-gray-700 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="btn-primary text-gray-900 px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Image Selector Modal -->
    <div id="imageSelectorModal" class="modal">
        <div class="bg-gray-800 p-8 rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold">Select Image</h3>
                <button onclick="closeImageSelector()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="selectorImageLibrary">
                <?php
                $uploadedImages = $db->fetchAll("SELECT * FROM uploaded_images ORDER BY upload_date DESC");
                foreach ($uploadedImages as $image):
                ?>
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="<?php echo htmlspecialchars($image['file_name']); ?>" 
                             class="image-preview" onclick="selectImageForProject('<?php echo htmlspecialchars($image['file_path']); ?>')">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Global variables
        let currentSection = 'dashboard';
        let selectedImageUrl = '';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Show dashboard by default
            showSection('dashboard');
            
            // Setup sidebar navigation
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.dataset.section;
                    showSection(section);
                });
            });
            
            // Setup mobile sidebar toggle
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('-translate-x-full');
            });
            
            // Setup form submissions
            document.getElementById('projectForm').addEventListener('submit', saveProject);
            
            // Setup image upload
            setupImageUpload();
        });
        
        // Show section
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(sec => {
                sec.classList.add('hidden');
            });
            
            // Show selected section
            document.getElementById(section + '-section').classList.remove('hidden');
            
            // Update sidebar active state
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('sidebar-active');
                if (link.dataset.section === section) {
                    link.classList.add('sidebar-active');
                }
            });
            
            currentSection = section;
            
            // Close mobile sidebar
            if (window.innerWidth < 1024) {
                document.getElementById('sidebar').classList.add('-translate-x-full');
            }
        }
        
        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type} show`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // Content management
        async function saveContent(section) {
            const inputs = document.querySelectorAll(`[id^="${section}-"]`);
            const data = {};
            
            inputs.forEach(input => {
                const key = input.id.replace(`${section}-`, '');
                data[key] = input.value;
            });
            
            try {
                const response = await fetch('../api/content.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ section, ...data })
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Content saved successfully!');
                } else {
                    showToast('Error saving content', 'error');
                }
            } catch (error) {
                console.error('Error saving content:', error);
                showToast('Error saving content', 'error');
            }
        }
        
        // Project management
        function openProjectModal(project = null) {
            const modal = document.getElementById('projectModal');
            const form = document.getElementById('projectForm');
            
            if (project) {
                document.getElementById('projectId').value = project.id;
                document.getElementById('projectTitle').value = project.title;
                document.getElementById('projectDescription').value = project.description;
                document.getElementById('projectImage').value = project.image_url;
                document.getElementById('projectTags').value = project.tags ? project.tags.join(', ') : '';
            } else {
                form.reset();
            }
            
            modal.classList.add('show');
        }
        
        function closeProjectModal() {
            document.getElementById('projectModal').classList.remove('show');
        }
        
        async function saveProject(e) {
            e.preventDefault();
            
            const projectData = {
                id: document.getElementById('projectId').value,
                title: document.getElementById('projectTitle').value,
                description: document.getElementById('projectDescription').value,
                image_url: document.getElementById('projectImage').value,
                tags: document.getElementById('projectTags').value.split(',').map(t => t.trim())
            };
            
            try {
                const url = projectData.id ? '../api/content.php?action=project' : '../api/content.php?action=project';
                const method = projectData.id ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(projectData)
                });
                
                const result = await response.json();
                if (result.success) {
                    closeProjectModal();
                    location.reload();
                } else {
                    showToast('Error saving project', 'error');
                }
            } catch (error) {
                console.error('Error saving project:', error);
                showToast('Error saving project', 'error');
            }
        }
        
        function editProject(id) {
            const projects = <?php echo json_encode($projects); ?>;
            const project = projects.find(p => p.id == id);
            if (project) {
                openProjectModal(project);
            }
        }
        
        async function deleteProject(id) {
            if (confirm('Are you sure you want to delete this project?')) {
                try {
                    const response = await fetch(`../api/content.php?action=project&id=${id}`, {
                        method: 'DELETE'
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        location.reload();
                    } else {
                        showToast('Error deleting project', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting project:', error);
                    showToast('Error deleting project', 'error');
                }
            }
        }
        
        // Image upload and library
        function setupImageUpload() {
            const uploadInput = document.getElementById('uploadInput');
            const uploadArea = document.getElementById('uploadArea');
            
            // Drag and drop
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                handleFiles(e.dataTransfer.files);
            });
            
            // File input change
            uploadInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
        }
        
        async function handleFiles(files) {
            const formData = new FormData();
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Validate file
                if (!file.type.startsWith('image/')) {
                    showToast('Only image files are allowed', 'error');
                    continue;
                }
                
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    showToast('File size must be less than 5MB', 'error');
                    continue;
                }
                
                formData.append('images[]', file);
            }
            
            try {
                const response = await fetch('../api/upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Images uploaded successfully!');
                    // Refresh the image library
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast('Error uploading images', 'error');
                }
            } catch (error) {
                console.error('Error uploading images:', error);
                showToast('Error uploading images', 'error');
            }
        }
        
        function selectImage(imageUrl) {
            // Copy image URL to clipboard
            navigator.clipboard.writeText(imageUrl).then(() => {
                showToast('Image URL copied to clipboard!');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = imageUrl;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('Image URL copied to clipboard!');
            });
        }
        
        function selectImageForProject(imageUrl) {
            selectedImageUrl = imageUrl;
            document.getElementById('projectImage').value = imageUrl;
            closeImageSelector();
        }
        
        function openImageSelector() {
            document.getElementById('imageSelectorModal').classList.add('show');
        }
        
        function closeImageSelector() {
            document.getElementById('imageSelectorModal').classList.remove('show');
        }
        
        async function deleteImage(id) {
            if (confirm('Are you sure you want to delete this image?')) {
                try {
                    const response = await fetch(`../api/upload.php?action=delete&id=${id}`, {
                        method: 'DELETE'
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        location.reload();
                    } else {
                        showToast('Error deleting image', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting image:', error);
                    showToast('Error deleting image', 'error');
                }
            }
        }
        
        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
        });
        
        // Close modals on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>