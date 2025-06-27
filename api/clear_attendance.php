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
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }
    
    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('No valid JSON data received');
    }
    
    // Validate required fields
    $date = $data['date'] ?? null;
    $serviceType = $data['service_type'] ?? null;
    $action = $data['action'] ?? null;
    
    if (!$date) {
        throw new Exception('Date is required');
    }
    
    if ($action !== 'clear') {
        throw new Exception('Invalid action');
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }
    
    // Build the delete query
    $sql = "DELETE a FROM attendance a 
            JOIN services s ON a.service_id = s.id 
            WHERE s.service_date = ?";
    $params = [$date];
    $types = 's';
    
    // Add service type filter if specified
    if ($serviceType && !empty($serviceType)) {
        $sql .= " AND s.service_type = ?";
        $params[] = $serviceType;
        $types .= 's';
    }
    
    // First, count how many records will be deleted
    $countSql = "SELECT COUNT(*) as count FROM attendance a 
                 JOIN services s ON a.service_id = s.id 
                 WHERE s.service_date = ?";
    $countParams = [$date];
    $countTypes = 's';
    
    if ($serviceType && !empty($serviceType)) {
        $countSql .= " AND s.service_type = ?";
        $countParams[] = $serviceType;
        $countTypes .= 's';
    }
    
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        throw new Exception('Failed to prepare count statement: ' . $conn->error);
    }
    
    $countStmt->bind_param($countTypes, ...$countParams);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $recordCount = $countResult->fetch_assoc()['count'];
    
    if ($recordCount == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No attendance records found to clear',
            'cleared_count' => 0,
            'date' => $date,
            'service_type' => $serviceType
        ]);
        exit;
    }
    
    // Execute the delete query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare delete statement: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $clearedCount = $stmt->affected_rows;
        
        // Also clean up any orphaned services (services with no attendance records)
        $cleanupSql = "DELETE FROM services WHERE id NOT IN (SELECT DISTINCT service_id FROM attendance)";
        $conn->query($cleanupSql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance records cleared successfully',
            'cleared_count' => $clearedCount,
            'date' => $date,
            'service_type' => $serviceType,
            'expected_count' => $recordCount
        ]);
        
    } else {
        throw new Exception('Failed to clear attendance records: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'cleared_count' => 0
    ]);
}
?>
