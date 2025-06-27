<?php
// Debug API Issues
header('Content-Type: text/html');

echo "<h1>Debugging API Issues</h1>";

try {
    require_once 'config/db.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Check if services table exists and its structure
    echo "<h2>Services Table Analysis</h2>";
    
    $result = $conn->query("SHOW TABLES LIKE 'services'");
    if ($result->num_rows == 0) {
        echo "<p>❌ Services table does not exist</p>";
        
        // Create services table
        $sql = "CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_date DATE NOT NULL,
            service_type ENUM('Sunday Morning', 'Sunday Evening', 'Wednesday', 'Friday', 'Special Event') NOT NULL,
            service_title VARCHAR(255),
            start_time TIME,
            end_time TIME,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_service (service_date, service_type)
        )";
        
        if ($conn->query($sql)) {
            echo "<p>✅ Services table created</p>";
        } else {
            echo "<p>❌ Error creating services table: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ Services table exists</p>";
        
        // Show table structure
        $desc = $conn->query("DESCRIBE services");
        echo "<h3>Services Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($col = $desc->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the services query
    echo "<h3>Testing Services Query</h3>";
    try {
        $stmt = $conn->prepare("
            SELECT id, service_date, service_type, created_at
            FROM services
            WHERE service_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY service_date DESC, service_type
            LIMIT 50
        ");
        
        if (!$stmt) {
            echo "<p>❌ Prepare failed: " . $conn->error . "</p>";
        } else {
            if (!$stmt->execute()) {
                echo "<p>❌ Execute failed: " . $stmt->error . "</p>";
            } else {
                $result = $stmt->get_result();
                $services = [];
                while ($row = $result->fetch_assoc()) {
                    $services[] = $row;
                }
                echo "<p>✅ Services query successful - found " . count($services) . " services</p>";
                
                if (count($services) == 0) {
                    echo "<p>No services found, adding sample service...</p>";
                    $today = date('Y-m-d');
                    $insert_stmt = $conn->prepare("INSERT INTO services (service_date, service_type, service_title) VALUES (?, 'Sunday Morning', 'Morning Worship')");
                    $insert_stmt->bind_param('s', $today);
                    if ($insert_stmt->execute()) {
                        echo "<p>✅ Sample service added</p>";
                    } else {
                        echo "<p>❌ Failed to add sample service: " . $insert_stmt->error . "</p>";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ Services query error: " . $e->getMessage() . "</p>";
    }
    
    // Check payment tables
    echo "<h2>Payment Tables Analysis</h2>";
    
    $payment_tables = ['instrumentalists', 'instrumentalist_payments'];
    foreach ($payment_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            echo "<p>❌ Table '$table' does not exist</p>";
        } else {
            echo "<p>✅ Table '$table' exists</p>";
            
            // Count records
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc()['count'];
            echo "<p>  - Contains $count records</p>";
        }
    }
    
    // Test the payment query
    echo "<h3>Testing Payment Query</h3>";
    try {
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
            echo "<p>❌ Payment query prepare failed: " . $conn->error . "</p>";
        } else {
            if (!$stmt->execute()) {
                echo "<p>❌ Payment query execute failed: " . $stmt->error . "</p>";
            } else {
                $result = $stmt->get_result();
                $payments = [];
                while ($row = $result->fetch_assoc()) {
                    $payments[] = $row;
                }
                echo "<p>✅ Payment query successful - found " . count($payments) . " payments</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ Payment query error: " . $e->getMessage() . "</p>";
    }
    
    // Test API endpoints directly
    echo "<h2>Testing API Endpoints</h2>";
    
    // Enable error reporting for this test
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    echo "<h3>Services API Test</h3>";
    echo "<iframe src='api/services.php' width='100%' height='200' style='border: 1px solid #ccc;'></iframe>";
    
    echo "<h3>Payment Processing API Test</h3>";
    echo "<iframe src='api/payment_processing.php?action=pending_payments' width='100%' height='200' style='border: 1px solid #ccc;'></iframe>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
