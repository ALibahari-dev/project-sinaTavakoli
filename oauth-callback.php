<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;

// Initialize session
session_start();

// Check if we're already authenticated
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    header('Location: /dashboard.html');
    exit;
}

try {
    $client = new Client();
    $client->setApplicationName('Sina Tavakoli Appointment System');
    $client->setScopes([Calendar::CALENDAR_EVENTS]);
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    
    // Enable offline access for refresh token
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    
    // Handle authorization code
    if (isset($_GET['code'])) {
        // Exchange authorization code for access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Check if we got a valid token
        if (isset($token['error'])) {
            throw new Exception('Token error: ' . $token['error']);
        }
        
        // Store token in session
        $_SESSION['access_token'] = $token;
        
        // Also save to file as backup
        $tokenFile = __DIR__ . '/token.json';
        if (file_put_contents($tokenFile, json_encode($token)) === false) {
            error_log('Failed to save token to file');
            // Continue anyway since we have it in session
        }
        
        // Set secure session cookie
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $httponly = true;
        $samesite = 'Strict';
        
        session_set_cookie_params([
            'lifetime' => 86400, // 1 day
            'path' => '/',
            'domain' => '.sinadesigner.ir', // Your domain
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
        
        // Redirect to success page
        header('Location: /auth-success.html');
        exit;
    } elseif (isset($_GET['error'])) {
        // Handle error
        $error = $_GET['error'];
        $error_description = $_GET['error_description'] ?? 'Unknown error';
        
        // Log the error
        error_log("OAuth Error: $error - $error_description");
        
        // Redirect to error page with details
        header('Location: /auth-error.html?error=' . urlencode($error) . '&description=' . urlencode($error_description)));
        exit;
    } else {
        // If no code or error, redirect to authorization
        $authUrl = $client->createAuthUrl();
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }
} catch (Exception $e) {
    // Log the error
    error_log('OAuth Exception: ' . $e->getMessage());
    
    // Redirect to error page
    header('Location: /auth-error.html?error=' . urlencode('exception') . '&description=' . urlencode($e->getMessage())));
    exit;
}
?>