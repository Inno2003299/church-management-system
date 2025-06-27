<?php
// Test services API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    require_once 'config/db.php';
    
    // Test the exact query from services.php
    $stmt = $conn->prepare("
        SELECT id, service_date, service_type, created_at
        FROM services
        WHERE service_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY service_date DESC, service_type
        LIMIT 50
    ");

    if (!$stmt) {
        echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
        exit;
    }

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Database execute error: ' . $stmt->error]);
        exit;
    }

    $result = $stmt->get_result();
    $services = [];

    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }

    echo json_encode(['success' => true, 'services' => $services, 'count' => count($services)]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
