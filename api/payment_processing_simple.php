<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../config/db.php';
    
    if ($_GET['action'] === 'pending_payments') {
        $stmt = $conn->prepare("
            SELECT ip.id, ip.instrumentalist_id, ip.service_id, ip.amount, 
                   ip.payment_type, ip.payment_status, ip.payment_date, ip.created_at,
                   i.full_name, i.instrument, 
                   COALESCE(i.per_service_rate, 0) as per_service_rate,
                   COALESCE(i.hourly_rate, 0) as hourly_rate,
                   s.service_date, s.service_type
            FROM instrumentalist_payments ip
            JOIN instrumentalists i ON ip.instrumentalist_id = i.id
            JOIN services s ON ip.service_id = s.id
            WHERE ip.payment_status IN ('Pending', 'Approved')
            ORDER BY s.service_date DESC, i.full_name
        ");
        
        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
            echo json_encode($payments);
        } else {
            echo json_encode(['error' => 'Query failed: ' . ($stmt ? $stmt->error : $conn->error)]);
        }
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
