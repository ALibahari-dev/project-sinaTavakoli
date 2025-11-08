<?php
// Quick fix script for common issues
require_once 'inc/config.php';
require_once 'inc/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Quick Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .btn { padding: 10px 20px; background: #fbbf24; color: #000; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>🔧 Quick Fix Script</h2>";

try {
    // 1. Create database if not exists
    echo "<h3>1. Creating Database</h3>";
    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "<p class='success'>✅ Database created/verified</p>";
    
    // 2. Create admin user
    echo "<h3>2. Creating Admin User</h3>";
    $db = Database::getInstance();
    
    // Check if admin exists
    $admin = $db->fetchOne("SELECT * FROM admin WHERE username = 'admin'");
    
    if (!$admin) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $db->insert('admin', [
            'username' => 'admin',
            'password' => $hashedPassword,
            'email' => 'admin@example.com'
        ]);
        echo "<p class='success'>✅ Admin user created</p>";
    } else {
        // Reset password
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $db->update('admin', ['password' => $hashedPassword], 'id = ?', [$admin['id']]);
        echo "<p class='success'>✅ Admin password reset to 'admin123'</p>";
    }
    
    // 3. Create tables
    echo "<h3>3. Creating Tables</h3>";
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS content (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section VARCHAR(50) NOT NULL,
            content_key VARCHAR(100) NOT NULL,
            content_value TEXT,
            content_type ENUM('text', 'html', 'json') DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_section_key (section, content_key)
        )",
        "CREATE TABLE IF NOT EXISTS projects (
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
        )",
        "CREATE TABLE IF NOT EXISTS tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(10),
            description VARCHAR(255),
            order_index INT DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50),
            order_index INT DEFAULT 0
        )"
    ];
    
    foreach ($tables as $sql) {
        $db->query($sql);
    }
    echo "<p class='success'>✅ All tables created/verified</p>";
    
    // 4. Insert default data
    echo "<h3>4. Inserting Default Data</h3>";
    
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
    
    foreach ($defaultContent as $content) {
        $db->query(
            "INSERT IGNORE INTO content (section, content_key, content_value) VALUES (?, ?, ?)",
            $content
        );
    }
    echo "<p class='success'>✅ Default content inserted</p>";
    
    echo "<h3 class='success'>🎉 All fixes completed successfully!</h3>";
    echo "<a href='admin/' class='btn'>Go to Admin Login</a>";
    echo "<a href='debug.php' class='btn'>Run Debug Again</a>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>
</body>
</html>";
?>