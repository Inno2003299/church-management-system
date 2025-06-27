<?php
// Test payment API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    require_once 'config/db.php';
    
    // Test the exact query from payment_processing.php
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument, i.per_service_rate, i.hourly_rate,
               s.service_date, s.service_type, s.service_title
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE ip.payment_status IN ('Pending', 'Approved')
        ORDER BY s.service_date DESC, i.full_name
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
    $payments = [];

    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }

    echo json_encode(['success' => true, 'payments' => $payments, 'count' => count($payments)]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
