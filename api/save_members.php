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

    $data = json_input();

    // Validate required fields
    if (empty($data['full_name']) || empty($data['gender'])) {
        respond_error('Full name and gender are required');
        exit;
    }

    $full_name = sanitize_input($data['full_name']);
    $phone = isset($data['phone']) ? sanitize_input($data['phone']) : '';
    $email = isset($data['email']) ? sanitize_input($data['email']) : '';
    $gender = sanitize_input($data['gender']);
    $date_of_birth = isset($data['date_of_birth']) ? sanitize_input($data['date_of_birth']) : null;
    $address = isset($data['address']) ? sanitize_input($data['address']) : '';

    $stmt = $conn->prepare("INSERT INTO members (full_name, phone, email, gender, date_of_birth, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $full_name, $phone, $email, $gender, $date_of_birth, $address);

    if ($stmt->execute()) {
        $member_id = $stmt->insert_id;
        respond_json([
            'id' => $member_id,
            'message' => 'Member added successfully',
            'success' => true
        ]);
    } else {
        respond_error('Failed to add member: ' . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    respond_error('Database error: ' . $e->getMessage());
}