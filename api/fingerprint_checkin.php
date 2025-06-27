<?php
require 'helpers.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Method not allowed', 405);
}

$data = json_input();
validate_required_fields($data, ['credential_id', 'service_date', 'service_type']);

$credential_id = sanitize_input($data['credential_id']);
$service_date = sanitize_input($data['service_date']);
$service_type = sanitize_input($data['service_type']);

// Get member from credential
$stmt = $conn->prepare("
    SELECT wc.member_id, m.full_name, m.is_active
    FROM webauthn_credentials wc 
    JOIN members m ON wc.member_id = m.id 
    WHERE wc.credential_id = ?
");
$stmt->bind_param('s', $credential_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    respond_error('Invalid credential');
}

if (!$result['is_active']) {
    respond_error('Member account is inactive');
}

$member_id = $result['member_id'];
$member_name = $result['full_name'];

// Find or create service
$service_id = find_or_create_service($conn, $service_date, $service_type);

// Check if already checked in
$stmt = $conn->prepare("SELECT id FROM attendance WHERE member_id = ? AND service_id = ?");
$stmt->bind_param('ii', $member_id, $service_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    respond_json([
        'success' => true,
        'message' => "Welcome back, $member_name! You were already checked in.",
        'member_name' => $member_name,
        'already_checked_in' => true
    ]);
}

// Record attendance
$stmt = $conn->prepare("INSERT INTO attendance (member_id, service_id, present, check_in_method) VALUES (?, ?, 1, 'Fingerprint')");
$stmt->bind_param('ii', $member_id, $service_id);

if ($stmt->execute()) {
    respond_json([
        'success' => true,
        'message' => "Welcome, $member_name! You have been checked in successfully.",
        'member_name' => $member_name,
        'check_in_time' => date('Y-m-d H:i:s'),
        'already_checked_in' => false
    ]);
} else {
    respond_error('Failed to record attendance');
}

$stmt->close();
?>
