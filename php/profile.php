<?php
// php/profile.php
header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/mysql.php';
require_once __DIR__ . '/config/mongo.php';

$userId = getAuthenticatedUserId();

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Invalid or expired token.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_profile') {
        // Get update fields
        $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
        $dob = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
        $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
        $address = isset($_POST['address']) ? trim($_POST['address']) : null;

        // Backend validations
        if (empty($fullName) || empty($mobile)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Full Name and Mobile are required.']);
            exit;
        }

        if (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mobile number must be between 10 and 15 digits.']);
            exit;
        }

        if ($age !== null && ($age < 0 || $age > 150)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please provide a valid age.']);
            exit;
        }

        try {
            // Determine fields updated for MongoDB logging
            $getStmt = $pdo->prepare("SELECT full_name, mobile, date_of_birth, age, address FROM users WHERE id = :id");
            $getStmt->execute([':id' => $userId]);
            $current = $getStmt->fetch();

            $fieldsUpdated = [];
            if ($current) {
                if ($current['full_name'] !== $fullName) $fieldsUpdated[] = 'full_name';
                if ($current['mobile'] !== $mobile) $fieldsUpdated[] = 'mobile';
                if ($current['date_of_birth'] !== $dob) $fieldsUpdated[] = 'date_of_birth';
                if ($current['age'] !== $age) $fieldsUpdated[] = 'age';
                if ($current['address'] !== $address) $fieldsUpdated[] = 'address';
            }

            // Update user details
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET full_name = :full_name, 
                    mobile = :mobile, 
                    date_of_birth = :date_of_birth, 
                    age = :age, 
                    address = :address 
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':full_name' => $fullName,
                ':mobile' => $mobile,
                ':date_of_birth' => empty($dob) ? null : $dob,
                ':age' => empty($age) ? null : $age,
                ':address' => empty($address) ? null : $address,
                ':id' => $userId
            ]);

            // Only log if something changed
            if (!empty($fieldsUpdated)) {
                logToMongo('profile_update_logs', [
                    'user_id' => $userId,
                    'action' => 'profile_update',
                    'fields_updated' => $fieldsUpdated
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully.'
            ]);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error occurred during update.']);
            exit;
        }
    }
}

// Default: GET/POST action to retrieve profile details
if ($action === 'get_profile' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT id, full_name, email, mobile, date_of_birth, age, address FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => $user
        ]);
        exit;
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action or request method.']);
