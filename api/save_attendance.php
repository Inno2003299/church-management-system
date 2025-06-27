<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../config/db.php';

    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }

    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('No valid JSON data received');
    }
    // Simple table creation
    $conn->query("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_date DATE NOT NULL,
        service_type VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        service_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Get data fields
    $date = $data['date'] ?? $data['service_date'] ?? null;
    $service = $data['service'] ?? $data['service_type'] ?? null;
    $attendees = $data['attendees'] ?? $data['members'] ?? null;

    if (!$date || !$service || !$attendees) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields',
            'received' => array_keys($data)
        ]);
        exit;
    }

    // Find or create service
    $stmt = $conn->prepare("SELECT id FROM services WHERE service_date = ? AND service_type = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare select statement: ' . $conn->error);
    }
    $stmt->bind_param('ss', $date, $service);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $serviceId = $result->fetch_assoc()['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO services (service_date, service_type) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement: ' . $conn->error);
        }
        $stmt->bind_param('ss', $date, $service);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create service: ' . $stmt->error);
        }
        $serviceId = $conn->insert_id;
    }

    // Record attendance (simplified - just member_id and service_id)
    $successCount = 0;
    foreach ($attendees as $memberId) {
        $stmt = $conn->prepare("INSERT INTO attendance (member_id, service_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare attendance statement: ' . $conn->error);
        }
        $stmt->bind_param('ii', $memberId, $serviceId);
        if ($stmt->execute()) {
            $successCount++;
        } else {
            throw new Exception('Failed to execute attendance insert: ' . $stmt->error);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Attendance recorded for $successCount members",
        'service_id' => $serviceId,
        'date' => $date,
        'service' => $service,
        'members_count' => $successCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
