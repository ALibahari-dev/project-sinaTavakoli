<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Include dependencies
require_once '../inc/database.php';
require_once '../inc/functions.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    try {
        $db = Database::getInstance();
        $admin = $db->fetchOne(
            "SELECT * FROM admin WHERE username = ?",
            [$username]
        );
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'] ?? '';
            $_SESSION['login_time'] = time();
            
            // Set remember me cookie if checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $db->update('admin', 
                    ['remember_token' => $token, 'token_expiry' => date('Y-m-d H:i:s', $expiry)],
                    'id = ?', 
                    [$admin['id']]
                );
                
                setcookie('remember_token', $token, $expiry, '/', '', false, true);
            }
            
            // Log the login
            $db->insert('admin_logs', [
                'admin_id' => $admin['id'],
                'action' => 'login',
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } catch (Exception $e) {
        $error = 'Login failed. Please try again.';
        error_log("Login error: " . $e->getMessage());
    }
}

// Check for remember me cookie
if (isset($_COOKIE['remember_token']) && !isset($error)) {
    try {
        $db = Database::getInstance();
        $admin = $db->fetchOne(
            "SELECT * FROM admin WHERE remember_token = ? AND token_expiry > NOW()",
            [$_COOKIE['remember_token']]
        );
        
        if ($admin) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'] ?? '';
            $_SESSION['login_time'] = time();
            
            header('Location: dashboard.php');
            exit;
        } else {
            // Clear invalid cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    } catch (Exception $e) {
        error_log("Remember me error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sina Portfolio CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .input-focus {
            transition: all 0.3s ease;
        }
        
        .input-focus:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(251, 191, 36, 0.3);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.6);
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .error-shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .feature-list li i {
            margin-right: 12px;
            color: #fbbf24;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center relative">
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <!-- Main Login Container -->
    <div class="relative z-10 w-full max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Left Side - Branding & Features -->
            <div class="text-white space-y-8">
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-yellow-400 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cube text-gray-900 text-xl"></i>
                        </div>
                        <h1 class="text-3xl font-bold">Portfolio CMS</h1>
                    </div>
                    <p class="text-xl text-gray-200">Manage your 3D portfolio with ease</p>
                    <p class="text-gray-300">Professional content management system for Sina Tavakoli's portfolio website</p>
                </div>
                
                <!-- Features List -->
                <div class="glass-effect rounded-2xl p-6 space-y-4">
                    <h3 class="text-xl font-semibold mb-4">Powerful Features</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Dynamic Content Management</li>
                        <li><i class="fas fa-check-circle"></i> Project Portfolio Control</li>
                        <li><i class="fas fa-check-circle"></i> Real-time Updates</li>
                        <li><i class="fas fa-check-circle"></i> Media Upload & Management</li>
                        <li><i class="fas fa-check-circle"></i> SEO Optimization</li>
                        <li><i class="fas fa-check-circle"></i> Responsive Design</li>
                    </ul>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="glass-effect rounded-xl p-4">
                        <div class="text-2xl font-bold text-yellow-400">50+</div>
                        <div class="text-sm text-gray-300">Projects</div>
                    </div>
                    <div class="glass-effect rounded-xl p-4">
                        <div class="text-2xl font-bold text-yellow-400">30+</div>
                        <div class="text-sm text-gray-300">Clients</div>
                    </div>
                    <div class="glass-effect rounded-xl p-4">
                        <div class="text-2xl font-bold text-yellow-400">3+</div>
                        <div class="text-sm text-gray-300">Years</div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Login Form -->
            <div class="glass-effect rounded-2xl p-8 shadow-2xl">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-shield text-gray-900 text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Welcome Back</h2>
                    <p class="text-gray-300">Sign in to manage your portfolio</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-500 bg-opacity-20 border border-red-500 text-red-100 px-4 py-3 rounded-lg mb-6 flex items-center error-shake">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                    <div class="bg-green-500 bg-opacity-20 border border-green-500 text-green-100 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        You have been successfully logged out
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm" class="space-y-6">
                    <div>
                        <label class="block text-gray-200 text-sm font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Username
                        </label>
                        <input type="text" 
                               name="username" 
                               required
                               placeholder="Enter your username"
                               class="w-full px-4 py-3 bg-white bg-opacity-10 border border-gray-400 border-opacity-30 rounded-lg text-white placeholder-gray-400 input-focus outline-none"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="relative">
                        <label class="block text-gray-200 text-sm font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <input type="password" 
                               name="password" 
                               id="password"
                               required
                               placeholder="Enter your password"
                               class="w-full px-4 py-3 pr-12 bg-white bg-opacity-10 border border-gray-400 border-opacity-30 rounded-lg text-white placeholder-gray-400 input-focus outline-none">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-gray-300 cursor-pointer">
                            <input type="checkbox" name="remember" class="mr-2 rounded">
                            <span class="text-sm">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-yellow-400 hover:text-yellow-300 transition">
                            Forgot password?
                        </a>
                    </div>
                    
                    <button type="submit" 
                            class="w-full btn-primary text-gray-900 font-bold py-3 px-4 rounded-lg flex items-center justify-center space-x-2">
                        <span id="btnText">Sign In</span>
                        <div class="loading-spinner" id="loadingSpinner"></div>
                    </button>
                </form>
                
                <!-- Default Credentials Info -->
                <div class="mt-8 p-4 bg-yellow-400 bg-opacity-10 border border-yellow-400 border-opacity-30 rounded-lg">
                    <p class="text-sm text-yellow-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Default Credentials:</strong><br>
                        Username: <code class="bg-black bg-opacity-30 px-2 py-1 rounded">admin</code><br>
                        Password: <code class="bg-black bg-opacity-30 px-2 py-1 rounded">admin123</code>
                    </p>
                </div>
                
                <!-- Security Info -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-400">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Secured with SSL encryption
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="absolute bottom-4 left-0 right-0 text-center text-gray-300 text-sm">
        <p>&copy; <?php echo date('Y'); ?> Sina Tavakoli Portfolio CMS. All rights reserved.</p>
    </div>
    
    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Show loading state
            btnText.style.display = 'none';
            loadingSpinner.style.display = 'inline-block';
            submitButton.disabled = true;
            
            // Remove error shake class if present
            const errorDiv = document.querySelector('.error-shake');
            if (errorDiv) {
                errorDiv.classList.remove('error-shake');
            }
        });
        
        // Auto-focus on username field
        window.addEventListener('load', function() {
            document.querySelector('input[name="username"]').focus();
        });
        
        // Add enter key support for form submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                document.getElementById('loginForm').requestSubmit();
            }
        });
        
        // Simple form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.querySelector('input[name="username"]').value.trim();
            const password = this.querySelector('input[name="password"]').value;
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
        });
        
        // Forgot password handler (placeholder)
        document.querySelector('a[href="#"]').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Password reset functionality will be available soon. Please contact the administrator.');
        });
        
        // Add smooth transitions
        document.querySelectorAll('input, button').forEach(element => {
            element.addEventListener('focus', function() {
                this.parentElement.classList.add('transform', 'scale-105');
            });
            
            element.addEventListener('blur', function() {
                this.parentElement.classList.remove('transform', 'scale-105');
            });
        });
    </script>
</body>
</html>