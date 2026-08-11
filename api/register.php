<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/mysql.php';
require_once __DIR__ . '/config/mongo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';

if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword) || empty($mobile)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

if (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mobile number must be between 10 and 15 digits.']);
    exit;
}

try {
    // Check if email already exists in MySQL
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email address is already registered.']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Save registration credentials to MySQL
    $insertStmt = $pdo->prepare("
        INSERT INTO users (email, password) 
        VALUES (:email, :password)
    ");
    $insertStmt->execute([
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    $userId = (int)$pdo->lastInsertId();

    // Save profile details to MongoDB profiles collection
    logToMongo('profiles', [
        'user_id' => $userId,
        'full_name' => $fullName,
        'mobile' => $mobile,
        'date_of_birth' => null,
        'age' => null,
        'address' => null
    ]);

    // Keep audit logging
    logToMongo('registration_logs', [
        'user_id' => $userId,
        'email' => $email,
        'action' => 'registration'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! You can now log in.'
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred during registration.']);
}
