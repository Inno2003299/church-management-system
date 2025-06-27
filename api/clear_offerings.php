<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
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
    
    // Build the delete query for offerings
    $sql = "DELETE FROM offerings WHERE service_date = ?";
    $params = [$date];
    $types = 's';
    
    // First, count how many records will be deleted
    $countSql = "SELECT COUNT(*) as count FROM offerings WHERE service_date = ?";
    $countParams = [$date];
    $countTypes = 's';
    
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
            'message' => 'No offering records found to clear',
            'cleared_count' => 0,
            'date' => $date
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
        
        echo json_encode([
            'success' => true,
            'message' => 'Offering records cleared successfully',
            'cleared_count' => $clearedCount,
            'date' => $date,
            'expected_count' => $recordCount
        ]);
        
    } else {
        throw new Exception('Failed to clear offering records: ' . $stmt->error);
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
