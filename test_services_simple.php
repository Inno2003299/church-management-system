<?php
// Simple test for services API
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "Testing Services API...\n\n";

try {
    echo "1. Including database config...\n";
    require_once 'config/db.php';
    echo "   ✅ Database config loaded\n";
    
    echo "2. Testing database connection...\n";
    if (!$conn) {
        throw new Exception("Database connection is null");
    }
    echo "   ✅ Database connection OK\n";
    
    echo "3. Checking if services table exists...\n";
    $result = $conn->query("SHOW TABLES LIKE 'services'");
    if ($result->num_rows == 0) {
        echo "   ❌ Services table does not exist\n";
        echo "   Creating services table...\n";
        
        $sql = "CREATE TABLE services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_date DATE NOT NULL,
            service_type VARCHAR(100) NOT NULL,
            service_title VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "   ✅ Services table created\n";
        } else {
            throw new Exception("Failed to create services table: " . $conn->error);
        }
    } else {
        echo "   ✅ Services table exists\n";
    }
    
    echo "4. Testing services query...\n";
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
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    echo "   ✅ Query successful - found " . count($services) . " services\n";
    
    if (count($services) == 0) {
        echo "5. Adding sample service...\n";
        $today = date('Y-m-d');
        $insert_stmt = $conn->prepare("INSERT INTO services (service_date, service_type, service_title) VALUES (?, 'Sunday Morning', 'Morning Worship')");
        $insert_stmt->bind_param('s', $today);
        if ($insert_stmt->execute()) {
            echo "   ✅ Sample service added\n";
            
            // Query again
            $stmt->execute();
            $result = $stmt->get_result();
            $services = [];
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
            echo "   Now have " . count($services) . " services\n";
        } else {
            echo "   ❌ Failed to add sample service: " . $insert_stmt->error . "\n";
        }
    }
    
    echo "\n6. Testing actual API call...\n";
    
    // Simulate the API call
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include 'api/services.php';
    $api_output = ob_get_clean();
    
    echo "   API Output: " . $api_output . "\n";
    
    $json_data = json_decode($api_output, true);
    if ($json_data && !isset($json_data['error'])) {
        echo "   ✅ API call successful\n";
    } else {
        echo "   ❌ API call failed: " . ($json_data['error'] ?? 'Invalid JSON') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
