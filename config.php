<?php
// Hosting SMTP Configuration
define('SMTP_HOST', 'smtp.sinadesigner.ir'); // Your SMTP server
define('SMTP_PORT', 587); // Usually 587 for TLS, 465 for SSL, or 25 for none
define('SMTP_USERNAME', 'info@sinadesigner.ir'); // Your email
// Use environment variable for password in production
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '6IeConiQ;C6JHzMX'); // Your email password
define('SMTP_ENCRYPTION', 'tls'); // 'tls', 'ssl', or '' for none

// Email Settings
define('ADMIN_EMAIL', 'info@sinadesigner.ir'); // Where to send notifications
define('EMAIL_FROM_NAME', 'Sina Tavakoli');
define('EMAIL_FROM', 'info@sinadesigner.ir'); // Must match SMTP_USERNAME
define('EMAIL_REPLY_TO', 'info@sinadesigner.ir');

// Security - Add your domain here for production
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://127.0.0.1',
    'https://localhost',
    'https://127.0.0.1',
    'https://sinadesigner.ir',
]);

// Rate limiting settings
define('RATE_LIMIT', 5); // 5 emails per hour
define('RATE_WINDOW', 3600); // 1 hour in seconds

// Debug mode - disable in production
define('DEBUG_MODE', in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']));

// Set CORS headers
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, ALLOWED_ORIGINS) || 
        strpos($origin, 'localhost') !== false || 
        strpos($origin, '127.0.0.1') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
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
}

setCorsHeaders();

// Error reporting configuration
if (DEBUG_MODE) {
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

// Generate CSRF token if session doesn't have one
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security functions
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

// Improved rate limiting with database fallback
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'email_limit_' . md5($ip);
    $limit = RATE_LIMIT;
    $window = RATE_WINDOW;
    
    $cacheFile = sys_get_temp_dir() . '/' . $key;
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data['count'] >= $limit && (time() - $data['start']) < $window) {
            http_response_code(429);
            echo json_encode([
                'success' => false, 
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $window - (time() - $data['start'])
            ]);
            exit;
        }
    }
    
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
    
    // Clean old files periodically (only 1 in 10 times to reduce I/O)
    if (rand(1, 10) === 1) {
        foreach (glob(sys_get_temp_dir() . '/email_limit_*') as $file) {
            if (time() - filemtime($file) > $window) {
                @unlink($file);
            }
        }
    }
}

// Improved log function with rotation
function logError($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Use date-based log files to prevent huge files
    $logFile = $logDir . '/email_errors_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Email sending function with improved error handling
function sendEmail($to, $subject, $body, $isHtml = false, $attachments = []) {
    try {
        // Include PHPMailer
        require_once 'vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Enable debug mode in development
        if (DEBUG_MODE) {
            $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = SMTP_PORT;
        
        // Set encryption if specified
        if (SMTP_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (SMTP_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }
        
        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(EMAIL_REPLY_TO);
        
        // Add attachments if provided
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                $mail->addAttachment(
                    $attachment['path'], 
                    $attachment['name'] ?? basename($attachment['path'])
                );
            }
        }
        
        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->CharSet = 'UTF-8';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        logError("Email sending failed: " . $e->getMessage());
        logError("SMTP Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle file uploads
function handleFileUploads($files) {
    $attachments = [];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    
    if (empty($files)) {
        return $attachments;
    }
    
    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            if ($files['size'][$key] > $maxSize) {
                continue; // Skip files that are too large
            }
            
            $type = $files['type'][$key];
            if (!in_array($type, $allowedTypes)) {
                continue; // Skip disallowed file types
            }
            
            $tmpName = $files['tmp_name'][$key];
            $uploadDir = __DIR__ . '/uploads';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uniqueName = time() . '_' . $name;
            $destination = $uploadDir . '/' . $uniqueName;
            
            if (move_uploaded_file($tmpName, $destination)) {
                $attachments[] = [
                    'path' => $destination,
                    'name' => $name
                ];
            }
        }
    }
    
    return $attachments;
}

// Check rate limit for email requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    checkRateLimit();
    
    // Get JSON data or form data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    // Handle file uploads if present
    $attachments = [];
    if (!empty($_FILES)) {
        $attachments = handleFileUploads($_FILES);
    }
    
    // Sanitize and validate inputs
    $name = sanitizeInput($data['name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $subject = sanitizeInput($data['subject'] ?? '');
    $message = sanitizeInput($data['message'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }
    
    if (!validateEmail($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address']);
        exit;
    }
    
    // Prepare email to admin
    $emailSubject = "New contact from $name: $subject";
    $emailBody = "
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Subject:</strong> $subject</p>
        <hr>
        <p><strong>Message:</strong></p>
        <p>$message</p>
        <hr>
        <p><small>Sent from: " . ($_SERVER['HTTP_REFERER'] ?? 'Unknown') . "</small></p>
        <p><small>IP Address: " . $_SERVER['REMOTE_ADDR'] . "</small></p>
        <p><small>User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</small></p>
    ";
    
    // Send email to admin
    if (sendEmail(ADMIN_EMAIL, $emailSubject, $emailBody, true, $attachments)) {
        // Send confirmation email to user
        $confirmSubject = "Thank you for contacting Sina Tavakoli";
        $confirmBody = "
            <h2>Thank you for your message!</h2>
            <p>Dear $name,</p>
            <p>We have received your message and will get back to you as soon as possible.</p>
            <p><strong>Your message:</strong></p>
            <p>$message</p>
            <hr>
            <p>Best regards,<br>Sina Tavakoli</p>
        ";
        
        // Send confirmation
        sendEmail($email, $confirmSubject, $confirmBody, true);
        
        // Clean up uploaded files after sending
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                unlink($attachment['path']);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send your message. Please try again later']);
    }
}
?>