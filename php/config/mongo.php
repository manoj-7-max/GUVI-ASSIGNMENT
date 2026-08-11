<?php
require_once __DIR__ . '/env.php';

try {
    $uri = getEnvVar('MONGO_URI', 'mongodb://127.0.0.1:27017');
    $mongoDbName = getEnvVar('MONGO_DATABASE', 'internship_app');
    
    $mongoManager = new MongoDB\Driver\Manager($uri);
} catch (\Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'message' => 'MongoDB connection failed: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Helper function to write to MongoDB
 */
if (!function_exists('logToMongo')) {
    function logToMongo($collection, $document) {
        global $mongoManager, $mongoDbName;
        try {
            $bulk = new MongoDB\Driver\BulkWrite;
            if (!isset($document['timestamp'])) {
                // MongoDB BSON UTCDateTime takes milliseconds
                $document['timestamp'] = new MongoDB\BSON\UTCDateTime(round(microtime(true) * 1000));
            }
            $bulk->insert($document);
            $mongoManager->executeBulkWrite("$mongoDbName.$collection", $bulk);
            return true;
        } catch (\Exception $e) {
            // Silently log/return false to avoid crashing application if MongoDB logging has issues
            return false;
        }
    }
}
