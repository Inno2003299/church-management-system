<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo "Starting debug...\n";

try {
    echo "Including database config...\n";
    require_once '../config/db.php';
    echo "Database included successfully\n";
    
    echo "Testing database connection...\n";
    if (!$conn) {
        throw new Exception("Database connection is null");
    }
    
    echo "Database connection OK\n";
    
    echo "Testing services query...\n";
    $stmt = $conn->prepare("
        SELECT id, service_date, service_type, created_at
        FROM services 
        WHERE service_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY service_date DESC, service_type
        LIMIT 50
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
    $services = [];
    
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    echo "Found " . count($services) . " services\n";
    
    // If no services, create one
    if (empty($services)) {
        echo "No services found, creating one...\n";
        $today = date('Y-m-d');
        $create_stmt = $conn->prepare("
            INSERT IGNORE INTO services (service_date, service_type, created_at) 
            VALUES (?, 'Sunday Morning', NOW())
        ");
        
        if ($create_stmt) {
            $create_stmt->bind_param('s', $today);
            if ($create_stmt->execute()) {
                echo "Service created successfully\n";
                
                // Try again
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $services[] = $row;
                }
                echo "Now found " . count($services) . " services\n";
            } else {
                echo "Failed to create service: " . $create_stmt->error . "\n";
            }
        } else {
            echo "Failed to prepare create statement: " . $conn->error . "\n";
        }
    }
    
    echo "Returning JSON response...\n";
    echo json_encode($services);
    
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
