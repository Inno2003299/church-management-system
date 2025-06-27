<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>API Debug Information</h1>
    
    <?php
    try {
        require_once 'config/db.php';
        echo "<div class='section success'><h3>✅ Database Connection: OK</h3></div>";
        
        // Test Services API
        echo "<div class='section'><h3>Services API Test</h3>";
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM services");
        $count = $stmt->fetch_assoc()['count'];
        echo "<p>Services in database: $count</p>";
        
        if ($count > 0) {
            $stmt = $conn->query("SELECT * FROM services ORDER BY service_date DESC LIMIT 5");
            echo "<h4>Recent Services:</h4><pre>";
            while ($row = $stmt->fetch_assoc()) {
                echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
            }
            echo "</pre>";
        }
        echo "</div>";
        
        // Test Instrumentalists API
        echo "<div class='section'><h3>Instrumentalists API Test</h3>";
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM instrumentalists");
        $count = $stmt->fetch_assoc()['count'];
        echo "<p>Instrumentalists in database: $count</p>";
        
        if ($count > 0) {
            $stmt = $conn->query("SELECT * FROM instrumentalists LIMIT 5");
            echo "<h4>Sample Instrumentalists:</h4><pre>";
            while ($row = $stmt->fetch_assoc()) {
                echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
            }
            echo "</pre>";
        }
        echo "</div>";
        
        // Test Payments API
        echo "<div class='section'><h3>Payments API Test</h3>";
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM instrumentalist_payments");
        $count = $stmt->fetch_assoc()['count'];
        echo "<p>Payments in database: $count</p>";
        
        if ($count > 0) {
            $stmt = $conn->query("
                SELECT ip.*, i.full_name, i.instrument, s.service_date, s.service_type
                FROM instrumentalist_payments ip
                JOIN instrumentalists i ON ip.instrumentalist_id = i.id
                JOIN services s ON ip.service_id = s.id
                ORDER BY ip.created_at DESC
                LIMIT 5
            ");
            echo "<h4>Recent Payments:</h4><pre>";
            while ($row = $stmt->fetch_assoc()) {
                echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
            }
            echo "</pre>";
        }
        echo "</div>";
        
        // Test API endpoints directly
        echo "<div class='section'><h3>Direct API Calls</h3>";
        
        // Test services endpoint
        echo "<h4>Services API Response:</h4>";
        $services_url = "http://localhost/Church-inventory/api/services.php";
        $services_response = @file_get_contents($services_url);
        if ($services_response) {
            echo "<pre>" . htmlspecialchars($services_response) . "</pre>";
        } else {
            echo "<p class='error'>Failed to fetch services API</p>";
        }
        
        // Test instrumentalists endpoint
        echo "<h4>Instrumentalists API Response:</h4>";
        $inst_url = "http://localhost/Church-inventory/api/instrumentalists.php";
        $inst_response = @file_get_contents($inst_url);
        if ($inst_response) {
            echo "<pre>" . htmlspecialchars($inst_response) . "</pre>";
        } else {
            echo "<p class='error'>Failed to fetch instrumentalists API</p>";
        }
        
        // Test payments endpoint
        echo "<h4>Payments API Response:</h4>";
        $payments_url = "http://localhost/Church-inventory/api/payment_processing.php?action=pending_payments";
        $payments_response = @file_get_contents($payments_url);
        if ($payments_response) {
            echo "<pre>" . htmlspecialchars($payments_response) . "</pre>";
        } else {
            echo "<p class='error'>Failed to fetch payments API</p>";
        }
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='section error'><h3>❌ Error: " . $e->getMessage() . "</h3></div>";
    }
    ?>
    
    <div class='section'>
        <h3>Quick Actions</h3>
        <p><a href="create_test_data.php">Create Test Data</a></p>
        <p><a href="test_api_endpoints.html">Test API Endpoints</a></p>
        <p><a href="index.php">Go to Main Application</a></p>
    </div>
</body>
</html>
