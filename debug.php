<?php
// Debug script to check database connection and admin user
require_once 'inc/config.php';
require_once 'inc/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #fbbf24; padding-bottom: 10px; }
        h3 { color: #555; margin-top: 20px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .info { color: #3b82f6; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #fbbf24; color: #000; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn:hover { background: #f59e0b; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>🔍 Database Debug Information</h2>";

// Test basic MySQL connection first
echo "<h3>1. Testing Basic MySQL Connection</h3>";
try {
    if (Database::testDatabaseConnection()) {
        echo "<p class='success'>✅ MySQL server connection successful</p>";
    } else {
        echo "<p class='error'>❌ Cannot connect to MySQL server</p>";
        echo "<p class='info'>Check if MySQL is running and credentials are correct</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check if database exists
echo "<h3>2. Checking Database Existence</h3>";
try {
    if (Database::databaseExists()) {
        echo "<p class='success'>✅ Database '" . DB_NAME . "' exists</p>";
    } else {
        echo "<p class='error'>❌ Database '" . DB_NAME . "' does not exist</p>";
        echo "<p class='info'><a href='setup.php' class='btn'>Create Database</a></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test database connection with database
echo "<h3>3. Testing Database Connection</h3>";
try {
    $db = Database::getInstance();
    if ($db->testConnection()) {
        echo "<p class='success'>✅ Database connection successful</p>";
    } else {
        echo "<p class='error'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check if admin table exists
echo "<h3>4. Checking Tables</h3>";
try {
    $db = Database::getInstance();
    $tables = ['admin', 'content', 'projects', 'tools', 'skills'];
    
    foreach ($tables as $table) {
        $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
        if ($result) {
            echo "<p class='success'>✅ Table '$table' exists</p>";
            
            // Count records
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM $table");
            echo "<p class='info'>   Records: " . $count['count'] . "</p>";
        } else {
            echo "<p class='error'>❌ Table '$table' does not exist</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking tables: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check configuration
echo "<h3>5. Configuration Check</h3>";
echo "<pre>";
echo "Database Host: " . DB_HOST . "\n";
echo "Database Name: " . DB_NAME . "\n";
echo "Database User: " . DB_USER . "\n";
echo "Database Pass: " . (empty(DB_PASS) ? "(empty)" : str_repeat("*", strlen(DB_PASS))) . "\n";
echo "Site URL: " . (defined('SITE_URL') ? SITE_URL : 'Not defined') . "\n";
echo "</pre>";

// Test password verification
echo "<h3>6. Testing Admin User</h3>";
try {
    $db = Database::getInstance();
    $admin = $db->fetchOne("SELECT * FROM admin WHERE username = 'admin'");
    
    if ($admin) {
        echo "<p class='success'>✅ Admin user found</p>";
        echo "<p class='info'>ID: {$admin['id']}, Username: {$admin['username']}, Email: " . ($admin['email'] ?? 'Not set') . "</p>";
        echo "<p class='info'>Created: {$admin['created_at']}</p>";
        
        // Test password
        if (password_verify('admin123', $admin['password'])) {
            echo "<p class='success'>✅ Password 'admin123' verifies correctly</p>";
        } else {
            echo "<p class='error'>❌ Password 'admin123' does not verify</p>";
            
            // Create new password hash
            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            echo "<p class='info'>New hash for 'admin123':</p>";
            echo "<pre>" . htmlspecialchars($newHash) . "</pre>";
            
            // Update password
            $db->update('admin', ['password' => $newHash], 'id = ?', [$admin['id']]);
            echo "<p class='success'>✅ Password updated successfully</p>";
        }
    } else {
        echo "<p class='error'>❌ Admin user not found</p>";
        
        // Create admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $db->insert('admin', [
            'username' => 'admin',
            'password' => $hashedPassword,
            'email' => 'admin@example.com'
        ]);
        echo "<p class='success'>✅ Admin user created with password 'admin123'</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test API endpoints
echo "<h3>7. Testing API Endpoints</h3>";
try {
    // Test content API
    $contentUrl = (defined('SITE_URL') ? SITE_URL : 'http://localhost/project-sina') . '/api/content.php?action=content&section=hero';
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET'
        ]
    ]);
    
    $response = @file_get_contents($contentUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && is_array($data)) {
            echo "<p class='success'>✅ Content API working</p>";
            echo "<p class='info'>Found " . count($data) . " content items</p>";
        } else {
            echo "<p class='error'>❌ Content API returned invalid JSON</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Could not test Content API</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ API test error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>🚀 Quick Actions</h3>";
echo "<a href='setup.php' class='btn'>🔧 Run Setup Script</a>";
echo "<a href='admin/' class='btn'>👤 Go to Admin Login</a>";
echo "<a href='index.php' class='btn'>🌐 View Portfolio</a>";
echo "<a href='' class='btn'>🔄 Refresh Debug</a>";

echo "<h3>📝 Login Information</h3>";
echo "<div style='background: #f3f4f6; padding: 15px; border-radius: 4px; border-left: 4px solid #fbbf24;'>";
echo "<strong>Default Credentials:</strong><br>";
echo "Username: <code>admin</code><br>";
echo "Password: <code>admin123</code><br>";
echo "<small class='warning'>⚠️ Change password after first login</small>";
echo "</div>";

echo "</div>
</body>
</html>";
?>