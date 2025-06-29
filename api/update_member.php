<?php
/**
 * Update Member API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (!isset($input['id']) || !isset($input['full_name'])) {
        throw new Exception('Member ID and full name are required');
    }
    
    $id = (int)$input['id'];
    $full_name = trim($input['full_name']);
    
    if (empty($full_name)) {
        throw new Exception('Full name cannot be empty');
    }
    
    // Check if member exists
    $check_stmt = $conn->prepare("SELECT id FROM members WHERE id = ?");
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    
    if (!$check_stmt->get_result()->fetch_assoc()) {
        throw new Exception('Member not found');
    }
    
    // Prepare update data
    $phone = isset($input['phone']) ? trim($input['phone']) : null;
    $gender = isset($input['gender']) ? trim($input['gender']) : null;
    $email = isset($input['email']) ? trim($input['email']) : null;
    $address = isset($input['address']) ? trim($input['address']) : null;
    $date_of_birth = isset($input['date_of_birth']) ? trim($input['date_of_birth']) : null;
    $occupation = isset($input['occupation']) ? trim($input['occupation']) : null;
    $has_fingerprint = isset($input['has_fingerprint']) ? (bool)$input['has_fingerprint'] : null;
    
    // Convert empty strings to null
    $phone = empty($phone) ? null : $phone;
    $email = empty($email) ? null : $email;
    $address = empty($address) ? null : $address;
    $date_of_birth = empty($date_of_birth) ? null : $date_of_birth;
    $occupation = empty($occupation) ? null : $occupation;
    
    // Validate email format if provided
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate date format if provided
    if ($date_of_birth && !DateTime::createFromFormat('Y-m-d', $date_of_birth)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }
    
    // Update member
    if ($has_fingerprint !== null) {
        // If updating fingerprint status
        $stmt = $conn->prepare("
            UPDATE members
            SET has_fingerprint = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('ii', $has_fingerprint, $id);
    } else {
        // Regular member update
        $stmt = $conn->prepare("
            UPDATE members
            SET full_name = ?,
                phone = ?,
                gender = ?,
                email = ?,
                address = ?,
                date_of_birth = ?,
                occupation = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param('sssssssi',
            $full_name,
            $phone,
            $gender,
            $email,
            $address,
            $date_of_birth,
            $occupation,
            $id
        );
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Member updated successfully',
            'member_id' => $id
        ]);
    } else {
        throw new Exception('Failed to update member: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
