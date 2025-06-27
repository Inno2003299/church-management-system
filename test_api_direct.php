<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background-color: #d4edda; }
        .error { background-color: #f8d7da; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Direct API Test</h1>
    
    <div class="test">
        <h3>Test 1: Services API</h3>
        <?php
        try {
            // Capture output
            ob_start();
            include 'api/services.php';
            $output = ob_get_clean();
            
            echo "<div class='success'>✅ Services API executed successfully</div>";
            echo "<h4>Output:</h4><pre>" . htmlspecialchars($output) . "</pre>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Services API error: " . $e->getMessage() . "</div>";
        } catch (Error $e) {
            echo "<div class='error'>❌ Services API fatal error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>Test 2: Payment Processing API</h3>
        <?php
        try {
            // Set the GET parameter for pending payments
            $_GET['action'] = 'pending_payments';
            
            // Capture output
            ob_start();
            include 'api/payment_processing.php';
            $output = ob_get_clean();
            
            echo "<div class='success'>✅ Payment Processing API executed successfully</div>";
            echo "<h4>Output:</h4><pre>" . htmlspecialchars($output) . "</pre>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Payment Processing API error: " . $e->getMessage() . "</div>";
        } catch (Error $e) {
            echo "<div class='error'>❌ Payment Processing API fatal error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>Test 3: Database Connection</h3>
        <?php
        try {
            require_once 'config/db.php';
            echo "<div class='success'>✅ Database connection successful</div>";
            
            // Test basic queries
            $result = $conn->query("SELECT COUNT(*) as count FROM services");
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                echo "<p>Services count: $count</p>";
            }
            
            $result = $conn->query("SELECT COUNT(*) as count FROM instrumentalists");
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                echo "<p>Instrumentalists count: $count</p>";
            }
            
            $result = $conn->query("SELECT COUNT(*) as count FROM instrumentalist_payments");
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                echo "<p>Payments count: $count</p>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>Test 4: Manual API Calls</h3>
        <?php
        try {
            require_once 'config/db.php';
            
            echo "<h4>Testing Services Query:</h4>";
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
                echo "<div class='success'>✅ Services query successful. Found " . count($services) . " services</div>";
                echo "<pre>" . json_encode($services, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<div class='error'>❌ Services query failed: " . $stmt->error . "</div>";
            }
            
            echo "<h4>Testing Payments Query:</h4>";
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
                echo "<div class='success'>✅ Payments query successful. Found " . count($payments) . " payments</div>";
                echo "<pre>" . json_encode($payments, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<div class='error'>❌ Payments query failed: " . $stmt->error . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Manual test error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
</body>
</html>
