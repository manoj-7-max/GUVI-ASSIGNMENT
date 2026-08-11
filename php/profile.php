<?php
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
        $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
        $dob = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
        $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
        $address = isset($_POST['address']) ? trim($_POST['address']) : null;

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
            // Find current profile from MongoDB to log changes
            $filter = ['user_id' => (int)$userId];
            $query = new MongoDB\Driver\Query($filter);
            $cursor = $mongoManager->executeQuery("$mongoDbName.profiles", $query);
            $currentArr = $cursor->toArray();
            $current = !empty($currentArr) ? (array)$currentArr[0] : null;

            $fieldsUpdated = [];
            if ($current) {
                if (($current['full_name'] ?? '') !== $fullName) $fieldsUpdated[] = 'full_name';
                if (($current['mobile'] ?? '') !== $mobile) $fieldsUpdated[] = 'mobile';
                if (($current['date_of_birth'] ?? '') !== $dob) $fieldsUpdated[] = 'date_of_birth';
                if (($current['age'] ?? null) !== $age) $fieldsUpdated[] = 'age';
                if (($current['address'] ?? '') !== $address) $fieldsUpdated[] = 'address';
            } else {
                $fieldsUpdated = ['full_name', 'mobile', 'date_of_birth', 'age', 'address'];
            }

            // Update user details in MongoDB profiles collection
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->update(
                ['user_id' => (int)$userId],
                ['$set' => [
                    'full_name' => $fullName,
                    'mobile' => $mobile,
                    'date_of_birth' => empty($dob) ? null : $dob,
                    'age' => empty($age) ? null : $age,
                    'address' => empty($address) ? null : $address,
                    'updated_at' => new MongoDB\BSON\UTCDateTime(round(microtime(true) * 1000))
                ]],
                ['upsert' => true]
            );
            $mongoManager->executeBulkWrite("$mongoDbName.profiles", $bulk);

            // Log update log in MongoDB
            if (!empty($fieldsUpdated)) {
                logToMongo('profile_update_logs', [
                    'user_id' => (int)$userId,
                    'action' => 'profile_update',
                    'fields_updated' => $fieldsUpdated
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully.'
            ]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error occurred during profile update.']);
            exit;
        }
    }
}

if ($action === 'get_profile' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Query Email from MySQL
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Query Profile details from MongoDB profiles collection
        $filter = ['user_id' => (int)$userId];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $mongoManager->executeQuery("$mongoDbName.profiles", $query);
        $profileArray = $cursor->toArray();
        $profile = !empty($profileArray) ? (array)$profileArray[0] : [];

        // Return combined data
        echo json_encode([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => [
                'id' => (int)$userId,
                'email' => $user['email'],
                'full_name' => $profile['full_name'] ?? '',
                'mobile' => $profile['mobile'] ?? '',
                'date_of_birth' => $profile['date_of_birth'] ?? null,
                'age' => $profile['age'] ?? null,
                'address' => $profile['address'] ?? ''
            ]
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action or request method.']);
