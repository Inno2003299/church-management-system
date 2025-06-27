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
validate_required_fields($data, ['credential_id', 'signature', 'authenticator_data']);

$credential_id = sanitize_input($data['credential_id']);
$signature = $data['signature'];
$authenticator_data = $data['authenticator_data'];

// Get credential and member info
$stmt = $conn->prepare("
    SELECT wc.id, wc.member_id, wc.public_key, wc.counter, m.full_name, m.is_active
    FROM webauthn_credentials wc 
    JOIN members m ON wc.member_id = m.id 
    WHERE wc.credential_id = ?
");
$stmt->bind_param('s', $credential_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    respond_error('Credential not found');
}

if (!$result['is_active']) {
    respond_error('Member account is inactive');
}

// In a real implementation, you would verify the signature here
// For this demo, we'll assume the signature is valid
$is_valid = true; // This should be actual signature verification

if ($is_valid) {
    // Update last used timestamp and counter
    $new_counter = $result['counter'] + 1;
    $stmt = $conn->prepare("UPDATE webauthn_credentials SET last_used = NOW(), counter = ? WHERE id = ?");
    $stmt->bind_param('ii', $new_counter, $result['id']);
    $stmt->execute();
    
    respond_json([
        'success' => true,
        'member_id' => $result['member_id'],
        'member_name' => $result['full_name'],
        'message' => 'Authentication successful'
    ]);
} else {
    respond_error('Authentication failed');
}

$stmt->close();
?>
