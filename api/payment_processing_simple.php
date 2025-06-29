<?php
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
    require_once __DIR__ . '/../config/db.php';

    // Check database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Log the request for debugging
    error_log("Payment API Request - Method: $method, Action: $action");

    if ($method === 'GET' && $action === 'pending_payments') {
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

    } elseif ($method === 'POST' && $action === 'approve_payment') {
        $data = json_decode(file_get_contents('php://input'), true);
        $payment_id = (int)$data['payment_id'];
        $approved_by = $data['approved_by'] ?? 'Admin';

        $stmt = $conn->prepare("UPDATE instrumentalist_payments SET payment_status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $approved_by, $payment_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Payment approved successfully']);
        } else {
            echo json_encode(['error' => 'Failed to approve payment or payment not found']);
        }

    } else {
        echo json_encode(['error' => 'Invalid action or method']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
