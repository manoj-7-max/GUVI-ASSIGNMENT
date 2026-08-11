<?php
// php/login.php
header('Content-Type: application/json');

require_once __DIR__ . '/config/mysql.php';
require_once __DIR__ . '/config/redis.php';
require_once __DIR__ . '/config/mongo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and Password are required.']);
    exit;
}

try {
    // Find user by email
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, mobile FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Verify password
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    // Generate secure token
    $token = bin2hex(random_bytes(32));

    // Expiry: 1 hour (3600 seconds)
    $expiresIn = 3600;
    $createdAt = date('Y-m-d H:i:s');
    $expiresAt = time() + $expiresIn;

    // Session payload
    $sessionData = [
        'user_id' => (int)$user['id'],
        'email' => $user['email'],
        'created_at' => $createdAt,
        'expires_at' => $expiresAt
    ];

    // Store in Redis
    $sessionKey = "auth_session:" . $token;
    $redis->setex($sessionKey, $expiresIn, json_encode($sessionData));

    // Log login activity in MongoDB
    logToMongo('login_logs', [
        'user_id' => (int)$user['id'],
        'email' => $user['email'],
        'action' => 'login'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => (int)$user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email']
        ]
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred during login.']);
}
