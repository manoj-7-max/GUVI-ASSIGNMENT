<?php
require_once __DIR__ . '/config/redis.php';

// Fallback for getallheaders if not available (e.g. php-fpm or built-in webserver on some systems)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            } elseif ($name === 'CONTENT_TYPE') {
                $headers['Content-Type'] = $value;
            } elseif ($name === 'CONTENT_LENGTH') {
                $headers['Content-Length'] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Validates the auth token against Redis and returns user_id if valid
 */
function getAuthenticatedUserId() {
    global $redis;
    
    $token = null;
    
    // Check Authorization Header
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    if (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }
    }
    
    // Fallback to POST/GET if headers don't have it
    if (!$token && isset($_POST['token'])) {
        $token = $_POST['token'];
    }
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    if (!$token) {
        return null;
    }
    
    // Get session data from Redis
    $sessionKey = "auth_session:" . $token;
    $sessionDataJson = $redis->get($sessionKey);
    
    if (!$sessionDataJson) {
        return null;
    }
    
    $sessionData = json_decode($sessionDataJson, true);
    if (!$sessionData || !isset($sessionData['user_id'])) {
        return null;
    }
    
    // Check expiration
    if (isset($sessionData['expires_at']) && time() > $sessionData['expires_at']) {
        $redis->del($sessionKey);
        return null;
    }
    
    return $sessionData['user_id'];
}
