<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>API Error Debug</h2>";

try {
    require_once '../config/db.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Test the services API
    echo "<h3>Testing Services API</h3>";
    
    // Check if services table exists and has correct structure
    $result = $conn->query("DESCRIBE services");
    if ($result) {
        echo "<h4>Services table structure:</h4><pre>";
        while ($row = $result->fetch_assoc()) {
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p>❌ Services table not found</p>";
    }
    
    // Test services query
    echo "<h4>Testing services query:</h4>";
    $stmt = $conn->prepare("
        SELECT id, service_date, service_type, created_at
        FROM services 
        WHERE service_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY service_date DESC, service_type
        LIMIT 50
    ");
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        echo "<p>✅ Services query successful. Found " . count($services) . " services</p>";
        if (count($services) > 0) {
            echo "<pre>" . json_encode($services, JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<p>❌ Services query failed: " . $stmt->error . "</p>";
    }
    
    // Test the payments API
    echo "<h3>Testing Payments API</h3>";
    
    // Check instrumentalist_payments table structure
    $result = $conn->query("DESCRIBE instrumentalist_payments");
    if ($result) {
        echo "<h4>Instrumentalist payments table structure:</h4><pre>";
        while ($row = $result->fetch_assoc()) {
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p>❌ Instrumentalist payments table not found</p>";
    }
    
    // Check instrumentalists table structure
    $result = $conn->query("DESCRIBE instrumentalists");
    if ($result) {
        echo "<h4>Instrumentalists table structure:</h4><pre>";
        while ($row = $result->fetch_assoc()) {
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p>❌ Instrumentalists table not found</p>";
    }
    
    // Test payments query
    echo "<h4>Testing payments query:</h4>";
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument, i.per_service_rate, i.hourly_rate,
               s.service_date, s.service_type, s.service_title
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE ip.payment_status IN ('Pending', 'Approved')
        ORDER BY s.service_date DESC, i.full_name
    ");
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        echo "<p>✅ Payments query successful. Found " . count($payments) . " payments</p>";
        if (count($payments) > 0) {
            echo "<pre>" . json_encode($payments, JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<p>❌ Payments query failed: " . $stmt->error . "</p>";
    }
    
    // Test individual table counts
    echo "<h3>Table Counts</h3>";
    
    $tables = ['services', 'instrumentalists', 'instrumentalist_payments', 'members'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p>$table: $count records</p>";
        } else {
            echo "<p>❌ Error counting $table: " . $conn->error . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
