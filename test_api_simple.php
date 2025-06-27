<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background-color: #d4edda; }
        .error { background-color: #f8d7da; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; max-height: 300px; }
    </style>
</head>
<body>
    <h1>Simple API Test</h1>
    
    <div class="test">
        <h3>Test Database Connection</h3>
        <?php
        try {
            require_once 'config/db.php';
            echo "<div class='success'>✅ Database connected successfully</div>";
            
            // Test table existence
            $tables = ['services', 'instrumentalists', 'instrumentalist_payments'];
            foreach ($tables as $table) {
                $result = $conn->query("SELECT COUNT(*) as count FROM $table");
                if ($result) {
                    $count = $result->fetch_assoc()['count'];
                    echo "<p>$table: $count records</p>";
                } else {
                    echo "<p class='error'>❌ Error with table $table: " . $conn->error . "</p>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>Test Services API Manually</h3>
        <?php
        try {
            require_once 'config/db.php';
            
            echo "<h4>Manual Services Query:</h4>";
            $stmt = $conn->prepare("
                SELECT id, service_date, service_type, created_at
                FROM services 
                WHERE service_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY service_date DESC, service_type
                LIMIT 50
            ");
            
            if ($stmt && $stmt->execute()) {
                $result = $stmt->get_result();
                $services = [];
                while ($row = $result->fetch_assoc()) {
                    $services[] = $row;
                }
                echo "<div class='success'>✅ Query successful. Found " . count($services) . " services</div>";
                echo "<pre>" . json_encode($services, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<div class='error'>❌ Query failed: " . ($stmt ? $stmt->error : $conn->error) . "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>Test Payments API Manually</h3>
        <?php
        try {
            require_once 'config/db.php';
            
            echo "<h4>Manual Payments Query:</h4>";
            $stmt = $conn->prepare("
                SELECT ip.*, i.full_name, i.instrument, i.per_service_rate, i.hourly_rate,
                       s.service_date, s.service_type, s.service_title
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
                echo "<div class='success'>✅ Query successful. Found " . count($payments) . " payments</div>";
                echo "<pre>" . json_encode($payments, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<div class='error'>❌ Query failed: " . ($stmt ? $stmt->error : $conn->error) . "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>Test API Endpoints via cURL</h3>
        <?php
        $base_url = "http://localhost/Church-inventory/";
        
        // Test services API
        echo "<h4>Services API:</h4>";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url . "api/services.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>HTTP Status: $http_code</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Test payments API
        echo "<h4>Payments API:</h4>";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url . "api/payment_processing.php?action=pending_payments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>HTTP Status: $http_code</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        ?>
    </div>
    
    <div class="test">
        <h3>Quick Actions</h3>
        <p><a href="create_test_data.php" target="_blank">Create Test Data</a></p>
        <p><a href="index.php">Go to Main App</a></p>
    </div>
</body>
</html>
