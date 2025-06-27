<?php
require 'helpers.php';

// Enable CORS for WebAuthn
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
validate_required_fields($data, ['member_id', 'credential_id', 'public_key']);

$member_id = (int)$data['member_id'];
$credential_id = sanitize_input($data['credential_id']);
$public_key = $data['public_key']; // Base64 encoded public key
$device_name = isset($data['device_name']) ? sanitize_input($data['device_name']) : 'Unknown Device';

// Verify member exists
$stmt = $conn->prepare("SELECT id FROM members WHERE id = ? AND is_active = 1");
$stmt->bind_param('i', $member_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    respond_error('Member not found or inactive');
}
$stmt->close();

// Check if credential already exists
$stmt = $conn->prepare("SELECT id FROM webauthn_credentials WHERE credential_id = ?");
$stmt->bind_param('s', $credential_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    respond_error('Credential already registered');
}
$stmt->close();

// Store the credential
$stmt = $conn->prepare("INSERT INTO webauthn_credentials (member_id, credential_id, public_key, device_name) VALUES (?, ?, ?, ?)");
$stmt->bind_param('isss', $member_id, $credential_id, $public_key, $device_name);

if ($stmt->execute()) {
    respond_json([
        'success' => true,
        'message' => 'Fingerprint registered successfully',
        'credential_id' => $credential_id
    ]);
} else {
    respond_error('Failed to register fingerprint');
}

$stmt->close();
?>
