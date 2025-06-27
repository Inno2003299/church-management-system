<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../config/db.php';
    
    // Get date parameter (default to today)
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Get offerings for the specified date
    $stmt = $conn->prepare("SELECT * FROM offerings WHERE service_date = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $offerings = [];
    $totalAmount = 0;
    $titheTotal = 0;
    $thanksgivingTotal = 0;
    $otherTotal = 0;
    
    while ($row = $result->fetch_assoc()) {
        $amount = floatval($row['amount']);
        $offerings[] = [
            'id' => $row['id'],
            'service_date' => $row['service_date'],
            'service_type' => $row['service_type'],
            'offering_type' => $row['offering_type'],
            'amount' => $amount,
            'notes' => $row['notes'],
            'created_at' => $row['created_at']
        ];
        
        $totalAmount += $amount;
        
        switch (strtolower($row['offering_type'])) {
            case 'tithe':
                $titheTotal += $amount;
                break;
            case 'thanksgiving':
                $thanksgivingTotal += $amount;
                break;
            default:
                $otherTotal += $amount;
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'date' => $date,
        'offerings' => $offerings,
        'totals' => [
            'total' => $totalAmount,
            'tithe' => $titheTotal,
            'thanksgiving' => $thanksgivingTotal,
            'other' => $otherTotal
        ],
        'count' => count($offerings)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'offerings' => [],
        'totals' => [
            'total' => 0,
            'tithe' => 0,
            'thanksgiving' => 0,
            'other' => 0
        ],
        'count' => 0
    ]);
}
?>
