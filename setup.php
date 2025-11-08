<?php
// Database setup script
require_once 'inc/config.php';

try {
    // Create database connection without selecting database
    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->exec("USE " . DB_NAME);
    
    echo "<h2>Database Setup</h2>";
    
    // Create admin table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Admin table created<br>";
    
    // Create content table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS content (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section VARCHAR(50) NOT NULL,
            content_key VARCHAR(100) NOT NULL,
            content_value TEXT,
            content_type ENUM('text', 'html', 'json') DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_section_key (section, content_key)
        )
    ");
    echo "✓ Content table created<br>";
    
    // Create projects table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            tags JSON,
            project_url VARCHAR(500),
            featured BOOLEAN DEFAULT FALSE,
            order_index INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Projects table created<br>";
    
    // Create tools table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(10),
            description VARCHAR(255),
            order_index INT DEFAULT 0
        )
    ");
    echo "✓ Tools table created<br>";
    
    // Create skills table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50),
            order_index INT DEFAULT 0
        )
    ");
    echo "✓ Skills table created<br>";
    
    // Create admin_logs table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            action VARCHAR(50),
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Admin logs table created<br>";
    
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin WHERE username = 'admin'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Create admin user with password: admin123
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'admin@example.com']);
        echo "✓ Admin user created (username: admin, password: admin123)<br>";
    } else {
        echo "ℹ Admin user already exists<br>";
    }
    
    // Insert default content
    $defaultContent = [
        ['hero', 'subtitle', 'Hi, my name is'],
        ['hero', 'name', 'Sina Tavakoli'],
        ['hero', 'title', 'I create 3D experiences'],
        ['hero', 'description', 'Product designer crafting immersive digital experiences that blend creativity with cutting-edge 3D technology'],
        ['about', 'title', 'About Me'],
        ['about', 'paragraph1', 'Hello! I\'m Sina, a passionate product designer with over 3 years of experience creating beautiful, functional digital products with immersive 3D elements.'],
        ['about', 'paragraph2', 'My passion lies in solving complex problems through design and creating experiences that users love. I specialize in UI/UX design, 3D visualization, and creative direction.'],
        ['contact', 'title', 'Let\'s Work Together'],
        ['contact', 'subtitle', 'Ready to create something amazing? Let\'s discuss your project'],
        ['stats', 'projects', '50'],
        ['stats', 'clients', '30'],
        ['stats', 'experience', '3'],
        ['stats', 'awards', '15']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO content (section, content_key, content_value) VALUES (?, ?, ?)");
    foreach ($defaultContent as $content) {
        $stmt->execute($content);
    }
    echo "✓ Default content inserted<br>";
    
    // Insert default tools
    $defaultTools = [
        ['Figma', '🎨', '3D Plugins', 0],
        ['Spline', '🧊', '3D Design', 1],
        ['Blender', '🟦', '3D Modeling', 2],
        ['After Effects', '🎬', '3D Motion', 3],
        ['Three.js', '🌐', 'Web 3D', 4],
        ['Principle', '📱', 'Prototyping', 5],
        ['Cinema 4D', '✨', '3D Animation', 6],
        ['React Three', '🎭', '3D Components', 7]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO tools (name, icon, description, order_index) VALUES (?, ?, ?, ?)");
    foreach ($defaultTools as $tool) {
        $stmt->execute($tool);
    }
    echo "✓ Default tools inserted<br>";
    
    // Insert default skills
    $defaultSkills = [
        ['Product Design', 'Design', 0],
        ['UI/UX Design', 'Design', 1],
        ['3D Design', 'Design', 2],
        ['Prototyping', 'Design', 3],
        ['Creative Direction', 'Design', 4],
        ['Motion Design', 'Design', 5]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO skills (name, category, order_index) VALUES (?, ?, ?)");
    foreach ($defaultSkills as $skill) {
        $stmt->execute($skill);
    }
    echo "✓ Default skills inserted<br>";
    
    // Insert sample projects
    $defaultProjects = [
        ['SaaS Platform 3D', 'Complete redesign with immersive 3D dashboard and interactions', 'https://picsum.photos/seed/project1/400/300.jpg', '["UI Design", "3D"]', '#', 1, 0],
        ['Mobile Experience', 'iOS app with cutting-edge 3D animations and micro-interactions', 'https://picsum.photos/seed/project2/400/300.jpg', '["Mobile", "Motion"]', '#', 0, 1],
        ['Design System', '3D component library with interactive elements', 'https://picsum.photos/seed/project3/400/300.jpg', '["Design System", "3D"]', '#', 0, 2]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO projects (title, description, image_url, tags, project_url, featured, order_index) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($defaultProjects as $project) {
        $stmt->execute($project);
    }
    echo "✓ Sample projects inserted<br>";
    
    echo "<h3 style='color: green;'>✅ Setup completed successfully!</h3>";
    echo "<p><a href='admin/' style='color: blue; text-decoration: underline;'>Go to Admin Panel</a></p>";
    echo "<p><a href='index.php' style='color: blue; text-decoration: underline;'>View Portfolio</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please check your database credentials in inc/config.php</p>";
}
// Create uploaded_images table
 $conn->exec("
    CREATE TABLE IF NOT EXISTS uploaded_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
echo "✓ Uploaded images table created<br>";

// Create visitors table
 $conn->exec("
    CREATE TABLE IF NOT EXISTS visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        page_visited VARCHAR(255) NOT NULL,
        user_agent TEXT,
        visit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
echo "✓ Visitors table created<br>";
?>