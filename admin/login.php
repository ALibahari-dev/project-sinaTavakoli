<?php
session_start();
require_once '../inc/database.php';
require_once '../inc/functions.php';

// Simple debug mode - remove in production
 $debug = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if ($debug) {
        echo "<h3>Debug Info:</h3>";
        echo "<p>Username: " . htmlspecialchars($username) . "</p>";
        echo "<p>Password length: " . strlen($password) . "</p>";
    }
    
    try {
        $db = Database::getInstance();
        
        // Get admin user
        $admin = $db->fetchOne(
            "SELECT * FROM admin WHERE username = ?",
            [$username]
        );
        
        if ($debug) {
            if ($admin) {
                echo "<p>User found in database</p>";
                echo "<p>Stored password hash: " . $admin['password'] . "</p>";
                echo "<p>Password verify result: " . (password_verify($password, $admin['password']) ? 'true' : 'false') . "</p>";
            } else {
                echo "<p>User NOT found in database</p>";
            }
        }
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Successful login
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['login_time'] = time();
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } catch (Exception $e) {
        $error = 'Login system error. Please try again.';
        if ($debug) {
            $error .= ' (' . $e->getMessage() . ')';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sina Portfolio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-yellow-400">Admin Login</h1>
            <p class="text-gray-400 mt-2">Sina Portfolio CMS</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Username</label>
                <input type="text" 
                       name="username" 
                       required
                       value="admin"
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Password</label>
                <input type="password" 
                       name="password" 
                       required
                       value="admin123"
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-400">
            </div>
            
            <button type="submit" 
                    class="w-full bg-yellow-400 text-gray-900 font-bold py-3 rounded-lg hover:bg-yellow-300 transition">
                Login
            </button>
        </form