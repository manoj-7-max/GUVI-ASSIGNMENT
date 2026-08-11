<?php
require_once __DIR__ . '/env.php';

try {
    $host = getEnvVar('REDIS_HOST', '127.0.0.1');
    $port = getEnvVar('REDIS_PORT', 6379);
    $pass = getEnvVar('REDIS_PASSWORD', null);

    $redis = new Redis();
    $redis->connect($host, $port);
    if (!empty($pass)) {
        $redis->auth($pass);
    }
} catch (\Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'message' => 'Redis connection failed'
    ]);
    exit;
}
