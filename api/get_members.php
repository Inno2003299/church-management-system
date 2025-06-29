<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../config/db.php';

    // Check database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, full_name, phone, email, gender, date_of_birth, address, occupation, status, has_fingerprint, created_at, updated_at FROM members ORDER BY full_name";
    $result = $conn->query($sql);

    if ($result) {
        $members = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($members);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch members: ' . $conn->error]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}