<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;

 $client = new Client();
 $client->setApplicationName('Sina Tavakoli Appointment System');
 $client->setScopes([Calendar::CALENDAR_EVENTS]);
 $client->setClientId('YOUR_GOOGLE_CLIENT_ID');
 $client->setClientSecret('YOUR_GOOGLE_CLIENT_SECRET');
 $client->setRedirectUri('https://yourdomain.com/oauth-callback.php');

// Handle authorization code
if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $token = $client->getAccessToken();
    
    // Save token to file
    file_put_contents('token.json', json_encode($token));
    
    // Redirect to success page
    header('Location: /auth-success.html');
    exit;
} elseif (isset($_GET['error'])) {
    // Handle error
    header('Location: /auth-error.html?error=' . urlencode($_GET['error']));
    exit;
}
?>