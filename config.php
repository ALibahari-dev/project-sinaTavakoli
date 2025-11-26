<?php
// Gmail SMTP Configuration
define('GMAIL_USER', 'chadco.alibahari@gmail.com'); // Your Gmail address
define('GMAIL_PASSWORD', 'dyer ejmb uexs umba'); // Your App Password
define('ADMIN_EMAIL', 'chadco.alibahari@gmail.com'); // Where to send admin notifications

// Email Settings
define('EMAIL_FROM_NAME', 'Sina Tavakoli');
define('EMAIL_REPLY_TO', 'chadco.alibahari@gmail.com');

// Security - Add your domain here for production
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://127.0.0.1',
    'https://localhost',
    'https://127.0.0.1',
    'https://yourdomain.com', // Replace with your actual domain
    'https://www.yourdomain.com' // Replace with your actual domain
]);

// Set CORS headers
 $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Allow localhost for development
    if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Error reporting for development
if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Additional security functions
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && 
           preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
}

// Rate limiting (simple implementation)
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'email_limit_' . md5($ip);
    $limit = 5; // 5 emails per hour
    $window = 3600; // 1 hour
    
    // Simple file-based rate limiting
    $cacheFile = sys_get_temp_dir() . '/' . $key;
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data['count'] >= $limit && (time() - $data['start']) < $window) {
            http_response_code(429);
            echo json_encode([
                'success' => false, 
                'message' => 'Too many requests. Please try again later.'
            ]);
            exit;
        }
    }
    
    // Update or create rate limit file
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ((time() - $data['start']) >= $window) {
            $data = ['count' => 1, 'start' => time()];
        } else {
            $data['count']++;
        }
    } else {
        $data = ['count' => 1, 'start' => time()];
    }
    
    file_put_contents($cacheFile, json_encode($data));
    
    // Clean old files
    foreach (glob(sys_get_temp_dir() . '/email_limit_*') as $file) {
        if (time() - filemtime($file) > $window) {
            unlink($file);
        }
    }
}

// Log function for debugging
function logError($message) {
    $logFile = __DIR__ . '/email_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Check rate limit for email requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkRateLimit();
}

// در send-email.php و oauth-callback.php
define('GOOGLE_CLIENT_ID', 'YOUR_ACTUAL_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_ACTUAL_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'https://yourdomain.com/oauth-callback.php');
?>