<?php
// Simple API test without database
header('Content-Type: application/json');

try {
    // Test 1: Basic PHP functionality
    $response = [
        'status' => 'success',
        'message' => 'PHP is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion()
    ];
    
    // Test 2: Database connection
    try {
        require_once '../config/db.php';
        $response['database'] = 'connected';
        $response['database_info'] = $conn->server_info;
    } catch (Exception $e) {
        $response['database'] = 'failed';
        $response['database_error'] = $e->getMessage();
    }
    
    // Test 3: Check if tables exist
    if (isset($conn)) {
        try {
            $result = $conn->query("SHOW TABLES");
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            $response['tables'] = $tables;
        } catch (Exception $e) {
            $response['tables_error'] = $e->getMessage();
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
