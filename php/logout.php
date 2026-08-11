<?php
header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/redis.php';

$token = null;
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
if (isset($headers['authorization'])) {
    $authHeader = $headers['authorization'];
    if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $authHeader;
    }
}
if (!$token && isset($_POST['token'])) {
    $token = $_POST['token'];
}
if (!$token && isset($_GET['token'])) {
    $token = $_GET['token'];
}

if ($token) {
    $sessionKey = "auth_session:" . $token;
    $redis->del($sessionKey);
}

echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
