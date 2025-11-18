<?php
// Include configuration
require_once 'config.php';

// Include Google API Client
require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Google Calendar Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'https://yourdomain.com/oauth-callback.php');

// Initialize Google Client
function getGoogleClient() {
    $client = new Client();
    $client->setApplicationName('Sina Tavakoli Appointment System');
    $client->setScopes([Calendar::CALENDAR_EVENTS]);
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    
    // Load previously authorized token from file
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
        
        // Refresh the token if it's expired
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                return null; // Need to re-authenticate
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
    }
    
    return $client;
}

// Create Google Meet event
function createGoogleMeetEvent($formData) {
    $client = getGoogleClient();
    
    if (!$client) {
        throw new Exception('Google authentication required');
    }
    
    $service = new Calendar($client);
    
    // Parse date and time
    $date = new DateTime($formData['date']);
    $time = explode(':', $formData['time']);
    $date->setTime($time[0], $time[1]);
    
    $startDateTime = $date->format('c');
    $endDateTime = clone $date;
    $endDateTime->add(new DateInterval('PT30M')); // 30 minutes meeting
    
    // Create event
    $event = new Event();
    $event->setSummary('Meeting with ' . $formData['firstName'] . ' ' . $formData['lastName']);
    $event->setDescription($formData['message']);
    
    // Set start and end time
    $start = new EventDateTime();
    $start->setDateTime($startDateTime);
    $start->setTimeZone('Asia/Tehran');
    $event->setStart($start);
    
    $end = new EventDateTime();
    $end->setDateTime($endDateTime);
    $end->setTimeZone('Asia/Tehran');
    $event->setEnd($end);
    
    // Add Google Meet conference
    $conferenceData = new ConferenceData();
    $createRequest = new CreateConferenceRequest();
    $createRequest->setRequestId('meeting-' . time() . '-' . rand(1000, 9999));
    
    $conferenceSolutionKey = new ConferenceSolutionKey();
    $conferenceSolutionKey->setEventType('hangoutsMeet');
    $createRequest->setConferenceSolutionKey($conferenceSolutionKey);
    
    $conferenceData->setCreateRequest($createRequest);
    $event->setConferenceData($conferenceData);
    
    // Add attendees
    $attendees = [
        [
            'email' => $formData['email'],
            'displayName' => $formData['firstName'] . ' ' . $formData['lastName']
        ]
    ];
    $event->setAttendees($attendees);
    
    // Set reminders
    $reminders = [
        'useDefault' => false,
        'overrides' => [
            ['method' => 'email', 'minutes' => 24 * 60], // 1 day before
            ['method' => 'popup', 'minutes' => 30] // 30 minutes before
        ]
    ];
    $event->setReminders($reminders);
    
    // Insert event
    $calendarId = 'primary';
    $event = $service->events->insert($calendarId, $event, ['conferenceDataVersion' => 1]);
    
    return [
        'meetLink' => $event->getHangoutLink(),
        'eventId' => $event->getId(),
        'eventLink' => $event->getHtmlLink()
    ];
}

// Function to send email
function sendAppointmentEmail($formData, $isAdmin = false, $meetData = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;
        $mail->Password   = GMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // Set timeout
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;
        
        // Recipients
        if ($isAdmin) {
            $mail->setFrom(GMAIL_USER, EMAIL_FROM_NAME);
            $mail->addAddress(ADMIN_EMAIL);
            $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
            $mail->Subject = 'New Appointment Scheduled - ' . $formData['firstName'] . ' ' . $formData['lastName'];
            $emailBody = generateAdminEmailTemplate($formData, $meetData);
        } else {
            $mail->setFrom(GMAIL_USER, EMAIL_FROM_NAME);
            $mail->addAddress($formData['email']);
            $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
            $mail->Subject = 'Appointment Confirmed - Google Meet Link Included';
            $emailBody = generateUserEmailTemplate($formData, $meetData);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['</p>', '</div>', '<br/>'], ["\n\n", "\n\n", "\n"], $emailBody));
        
        // Add calendar invite attachment
        if ($meetData) {
            $icalContent = generateICalendarInvite($formData, $meetData);
            $mail->addStringAttachment($icalContent, 'appointment.ics', 'base64', 'text/calendar');
        }
        
        $mail->send();
        logError("Email sent successfully to " . ($isAdmin ? ADMIN_EMAIL : $formData['email']));
        return true;
        
    } catch (Exception $e) {
        $errorMessage = "Mail Error: " . $mail->ErrorInfo;
        logError($errorMessage);
        return false;
    }
}

// Generate iCalendar invite
function generateICalendarInvite($formData, $meetData) {
    $date = new DateTime($formData['date']);
    $time = explode(':', $formData['time']);
    $date->setTime($time[0], $time[1]);
    
    $start = $date->format('Ymd\THis\Z');
    $end = clone $date;
    $end->add(new DateInterval('PT30M'));
    $end = $end->format('Ymd\THis\Z');
    
    $uid = 'appointment-' . time() . '@yourdomain.com';
    
    return "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sina Tavakoli//Appointment System//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
UID:{$uid}
DTSTART:{$start}
DTEND:{$end}
SUMMARY:Meeting with Sina Tavakoli
DESCRIPTION:{$formData['message']}
LOCATION:{$meetData['meetLink']}
STATUS:CONFIRMED
SEQUENCE:0
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:Reminder: Meeting with Sina Tavakoli in 30 minutes
TRIGGER:-PT30M
END:VALARM
END:VEVENT
END:VCALENDAR";
}

// Generate admin email template with Meet link
function generateAdminEmailTemplate($data, $meetData) {
    $subject = "New Appointment Scheduled";
    $message = nl2br($data['message']);
    $meetLink = $meetData['meetLink'] ?? '';
    $eventLink = $meetData['eventLink'] ?? '';
    
    return "
    <div style='font-family: \"Inter\", sans-serif; background-color: #1a1a2e; color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px;'>
        <div style='text-align: center; margin-bottom: 30px;'>
            <h1 style='font-family: \"Orbitron\", sans-serif; font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #f9c74f;'>{$subject}</h1>
            <p style='color: #aaaaaa;'>You have a new appointment request with Google Meet</p>
        </div>
        
        <div style='background-color: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;'>
            <h2 style='font-size: 20px; margin-bottom: 15px; color: #f9c74f;'>Appointment Details</h2>
            <table style='width: 100%;'>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Name:</td>
                    <td style='padding: 8px 0; font-weight: 600;'>{$data['firstName']} {$data['lastName']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Email:</td>
                    <td style='padding: 8px 0;'>{$data['email']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Phone:</td>
                    <td style='padding: 8px 0;'>{$data['phone']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Date:</td>
                    <td style='padding: 8px 0; font-weight: 600;'>{$data['date']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Time:</td>
                    <td style='padding: 8px 0; font-weight: 600;'>{$data['time']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Message:</td>
                    <td style='padding: 8px 0;'>{$message}</td>
                </tr>
            </table>
        </div>
        
        " . ($meetLink ? "
        <div style='background-color: rgba(249, 199, 79, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #f9c74f;'>
            <h2 style='font-size: 18px; margin-bottom: 10px; color: #f9c74f;'>📹 Google Meet Link</h2>
            <p style='margin-bottom: 10px; color: #dddddd;'>Join the meeting:</p>
            <a href='{$meetLink}' style='color: #f9c74f; text-decoration: none; font-weight: 600; font-size: 16px;'>{$meetLink}</a>
            <p style='margin-top: 10px; font-size: 12px; color: #aaaaaa;'>
                <a href='{$eventLink}' style='color: #f9c74f;'>View in Google Calendar</a>
            </p>
        </div>" : "") . "
        
        <div style='text-align: center; margin-top: 30px;'>
            <p style='color: #aaaaaa; font-size: 14px;'>This is an automated message from your website appointment system.</p>
            <p style='color: #aaaaaa; font-size: 12px; margin-top: 5px;'>Sent at: " . date('Y-m-d H:i:s') . "</p>
        </div>
    </div>";
}

// Generate user email template with Meet link
function generateUserEmailTemplate($data, $meetData) {
    $meetLink = $meetData['meetLink'] ?? '';
    $eventLink = $meetData['eventLink'] ?? '';
    
    return "
    <div style='font-family: \"Inter\", sans-serif; background-color: #1a1a2e; color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px;'>
        <div style='text-align: center; margin-bottom: 30px;'>
            <h1 style='font-family: \"Orbitron\", sans-serif; font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #f9c74f;'>Appointment Confirmed</h1>
            <p style='color: #aaaaaa;'>Your appointment has been scheduled with Google Meet</p>
        </div>
        
        <div style='background-color: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;'>
            <h2 style='font-size: 20px; margin-bottom: 15px; color: #f9c74f;'>Appointment Details</h2>
            <table style='width: 100%;'>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Date:</td>
                    <td style='padding: 8px 0; font-weight: 600;'>{$data['date']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>Time:</td>
                    <td style='padding: 8px 0; font-weight: 600;'>{$data['time']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; width: 150px; color: #aaaaaa;'>With:</td>
                    <td style='padding: 8px 0; font-weight: 600;'>Sina Tavakoli</td>
                </tr>
            </table>
        </div>
        
        " . ($meetLink ? "
        <div style='background: linear-gradient(135deg, rgba(249, 199, 79, 0.15) 0%, rgba(248, 150, 30, 0.15) 100%); padding: 25px; border-radius: 10px; margin-bottom: 20px; border: 2px solid rgba(249, 199, 79, 0.3);'>
            <h2 style='font-size: 20px; margin-bottom: 15px; color: #f9c74f; text-align: center;'>🎥 Join Google Meet</h2>
            <div style='text-align: center; margin-bottom: 15px;'>
                <a href='{$meetLink}' style='background: linear-gradient(135deg, #f9c74f 0%, #f8961e 100%); color: #0a0a0a; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-block;'>Join Meeting Now</a>
            </div>
            <p style='color: #dddddd; font-size: 14px; text-align: center; margin-bottom: 10px;'>Or copy this link:</p>
            <p style='color: #f9c74f; font-size: 12px; text-align: center; word-break: break-all;'>{$meetLink}</p>
            <p style='text-align: center; margin-top: 15px;'>
                <a href='{$eventLink}' style='color: #f9c74f; font-size: 14px;'>📅 Add to Google Calendar</a>
            </p>
        </div>" : "") . "
        
        <div style='background-color: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 20px;'>
            <h2 style='font-size: 18px; margin-bottom: 10px; color: #f9c74f;'>📋 What's Next?</h2>
            <ul style='color: #dddddd; margin: 0; padding-left: 20px;'>
                <li style='margin-bottom: 8px;'>✅ Check your email for calendar invitation</li>
                <li style='margin-bottom: 8px;'>💻 Test your camera and microphone before the call</li>
                <li style='margin-bottom: 8px;'>📝 Prepare any questions or topics to discuss</li>
                <li style='margin-bottom: 8px;'>🔇 Find a quiet place for the meeting</li>
                <li style='margin-bottom: 8px;'>⏰ Join 5 minutes early</li>
            </ul>
        </div>
        
        <div style='text-align: center; margin-top: 30px;'>
            <p style='color: #aaaaaa; font-size: 14px;'>If you need to reschedule, please reply to this email.</p>
            <p style='color: #aaaaaa; font-size: 14px; margin-top: 10px;'>Looking forward to speaking with you!</p>
        </div>
    </div>";
}

// Main execution
try {
    // Get POST data
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception('No data received');
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $requiredFields = ['firstName', 'lastName', 'email', 'date', 'time'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Sanitize input
    $data = sanitizeInput($data);
    
    // Validate email
    if (!validateEmail($data['email'])) {
        throw new Exception('Invalid email address');
    }
    
    // Create Google Meet event
    $meetData = createGoogleMeetEvent($data);
    
    // Send emails with Meet link
    $adminEmailSent = sendAppointmentEmail($data, true, $meetData);
    if (!$adminEmailSent) {
        throw new Exception('Failed to send admin notification');
    }
    
    $userEmailSent = sendAppointmentEmail($data, false, $meetData);
    if (!$userEmailSent) {
        throw new Exception('Failed to send user confirmation');
    }
    
    // Success response
    echo json_encode([
        'success' => true, 
        'message' => 'Appointment scheduled successfully with Google Meet',
        'meetLink' => $meetData['meetLink'],
        'eventLink' => $meetData['eventLink'],
        'data' => [
            'date' => $data['date'],
            'time' => $data['time']
        ]
    ]);
    
} catch (Exception $e) {
    logError("Error in send-email.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>