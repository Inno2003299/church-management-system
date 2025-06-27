<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo "Starting payments debug...\n";

try {
    echo "Including database config...\n";
    require_once '../config/db.php';
    echo "Database included successfully\n";
    
    echo "Testing database connection...\n";
    if (!$conn) {
        throw new Exception("Database connection is null");
    }
    
    echo "Database connection OK\n";
    
    echo "Testing payments query...\n";
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
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    echo "Query prepared successfully\n";
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    echo "Query executed successfully\n";
    
    $result = $stmt->get_result();
    $payments = [];
    
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    echo "Found " . count($payments) . " payments\n";
    
    echo "Returning JSON response...\n";
    echo json_encode($payments);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
