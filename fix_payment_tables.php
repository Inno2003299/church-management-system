<?php
// Fix payment tables and ensure they exist with correct structure
header('Content-Type: text/html');

echo "<h1>Fixing Payment Tables</h1>";

try {
    require_once 'config/db.php';
    
    echo "<p>✅ Database connection successful</p>";
    
    // Create services table if it doesn't exist
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
        echo "<p>✅ Services table created/verified</p>";
    } else {
        echo "<p>❌ Error creating services table: " . $conn->error . "</p>";
    }
    
    // Create instrumentalists table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS instrumentalists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(255),
        instrument VARCHAR(100) NOT NULL,
        skill_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Professional') DEFAULT 'Intermediate',
        hourly_rate DECIMAL(8,2),
        per_service_rate DECIMAL(8,2),
        momo_provider ENUM('MTN', 'Vodafone', 'AirtelTigo', 'Other') NULL,
        momo_number VARCHAR(20) NULL,
        momo_name VARCHAR(255) NULL,
        bank_account_number VARCHAR(50) NULL,
        bank_name VARCHAR(100) NULL,
        bank_account_name VARCHAR(255) NULL,
        preferred_payment_method ENUM('Mobile Money', 'Bank Transfer', 'Cash') DEFAULT 'Mobile Money',
        paystack_recipient_code VARCHAR(100) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Instrumentalists table created/verified</p>";
    } else {
        echo "<p>❌ Error creating instrumentalists table: " . $conn->error . "</p>";
    }
    
    // Create instrumentalist_payments table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS instrumentalist_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instrumentalist_id INT NOT NULL,
        service_id INT NOT NULL,
        amount DECIMAL(8,2) NOT NULL,
        payment_type ENUM('Per Service', 'Hourly', 'Fixed Amount') DEFAULT 'Per Service',
        hours_worked DECIMAL(4,2),
        payment_status ENUM('Pending', 'Approved', 'Paid', 'Failed', 'Cancelled') DEFAULT 'Pending',
        payment_date DATE,
        payment_method ENUM('Cash', 'Bank Transfer', 'Check', 'Mobile Money', 'Paystack Transfer', 'Other') DEFAULT 'Mobile Money',
        reference_number VARCHAR(100),
        paystack_transfer_code VARCHAR(100) NULL,
        paystack_transfer_id VARCHAR(100) NULL,
        paystack_status VARCHAR(50) NULL,
        paystack_failure_reason TEXT NULL,
        paystack_recipient_code VARCHAR(100) NULL,
        approved_by VARCHAR(255),
        approved_at TIMESTAMP NULL,
        paid_by VARCHAR(255),
        paid_at TIMESTAMP NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (instrumentalist_id) REFERENCES instrumentalists(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
        INDEX idx_instrumentalist_payments (instrumentalist_id),
        INDEX idx_service_payments (service_id),
        INDEX idx_payment_status (payment_status)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Instrumentalist payments table created/verified</p>";
    } else {
        echo "<p>❌ Error creating instrumentalist payments table: " . $conn->error . "</p>";
    }
    
    // Add some sample data if tables are empty
    $result = $conn->query("SELECT COUNT(*) as count FROM instrumentalists");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "<h2>Adding Sample Data</h2>";
        
        // Add sample instrumentalists
        $stmt = $conn->prepare("INSERT INTO instrumentalists (full_name, phone, instrument, skill_level, per_service_rate) VALUES (?, ?, ?, ?, ?)");
        
        $instrumentalists = [
            ['David Brown', '555-0201', 'Piano', 'Advanced', 50.00],
            ['Lisa Davis', '555-0202', 'Guitar', 'Intermediate', 40.00],
            ['Mark Taylor', '555-0203', 'Drums', 'Professional', 60.00]
        ];
        
        foreach ($instrumentalists as $inst) {
            $stmt->bind_param('ssssd', $inst[0], $inst[1], $inst[2], $inst[3], $inst[4]);
            if ($stmt->execute()) {
                echo "<p>✅ Added instrumentalist: {$inst[0]} ({$inst[2]})</p>";
            }
        }
        
        // Add sample service
        $today = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO services (service_date, service_type, service_title) VALUES (?, 'Sunday Morning', 'Morning Worship')");
        $stmt->bind_param('s', $today);
        if ($stmt->execute()) {
            $service_id = $stmt->insert_id;
            echo "<p>✅ Added sample service for today</p>";
            
            // Add sample payments
            $stmt = $conn->prepare("INSERT INTO instrumentalist_payments (instrumentalist_id, service_id, amount, payment_type, payment_status) VALUES (?, ?, ?, 'Per Service', 'Pending')");
            
            for ($i = 1; $i <= 3; $i++) {
                $amount = 50.00;
                $stmt->bind_param('iid', $i, $service_id, $amount);
                if ($stmt->execute()) {
                    echo "<p>✅ Added sample payment for instrumentalist $i</p>";
                }
            }
        }
    }
    
    echo "<h2>Testing Payment Query</h2>";
    
    // Test the payment query
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
        $count = $result->num_rows;
        echo "<p>✅ Payment query successful - found $count payments</p>";
        
        if ($count > 0) {
            echo "<h3>Sample Payment Data:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Instrumentalist</th><th>Instrument</th><th>Amount</th><th>Status</th><th>Service Date</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['full_name']}</td>";
                echo "<td>{$row['instrument']}</td>";
                echo "<td>\${$row['amount']}</td>";
                echo "<td>{$row['payment_status']}</td>";
                echo "<td>{$row['service_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ Payment query failed: " . $conn->error . "</p>";
    }
    
    echo "<h2>Testing Services API</h2>";

    // Test the services API endpoint
    $services_url = "http://localhost/Church-inventory/api/services.php";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);

    $services_response = @file_get_contents($services_url, false, $context);
    if ($services_response !== false) {
        $services_data = json_decode($services_response, true);
        if ($services_data && !isset($services_data['error'])) {
            echo "<p>✅ Services API working - found " . count($services_data) . " services</p>";
        } else {
            echo "<p>❌ Services API error: " . ($services_data['error'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p>❌ Could not connect to Services API</p>";
    }

    // Test the payment processing API endpoint
    $payments_url = "http://localhost/Church-inventory/api/payment_processing.php?action=pending_payments";
    $payments_response = @file_get_contents($payments_url, false, $context);
    if ($payments_response !== false) {
        $payments_data = json_decode($payments_response, true);
        if ($payments_data && !isset($payments_data['error'])) {
            echo "<p>✅ Payment Processing API working - found " . count($payments_data) . " payments</p>";
        } else {
            echo "<p>❌ Payment Processing API error: " . ($payments_data['error'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p>❌ Could not connect to Payment Processing API</p>";
    }

    echo "<h2>✅ All Done!</h2>";
    echo "<p><a href='index.php'>Go back to main page</a></p>";
    echo "<p><a href='api/services.php'>Test Services API directly</a></p>";
    echo "<p><a href='api/payment_processing.php?action=pending_payments'>Test Payment API directly</a></p>";

} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
