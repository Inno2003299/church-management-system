<?php
// Disable error output to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

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
    require 'helpers.php';

    $sql = "SELECT id, full_name, phone, email, gender, date_of_birth, address, is_active, created_at FROM members ORDER BY full_name";
    $result = $conn->query($sql);

    if ($result) {
        $members = $result->fetch_all(MYSQLI_ASSOC);
        respond_json($members);
    } else {
        respond_error('Failed to fetch members: ' . $conn->error);
    }

} catch (Exception $e) {
    respond_error('Database error: ' . $e->getMessage());
}