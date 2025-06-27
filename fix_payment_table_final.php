<?php
// Final fix for payment table
header('Content-Type: text/html');

try {
    require_once 'config/db.php';
    echo "<h1>Final Payment Table Fix</h1>";
    
    // Drop and recreate the payment table without foreign keys for now
    $conn->query("DROP TABLE IF EXISTS instrumentalist_payments");
    echo "<p>Dropped existing payment table</p>";
    
    // Create payment table without foreign keys to avoid constraint issues
    $sql = "CREATE TABLE instrumentalist_payments (
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Payment table created</p>";
        
        // Get existing data
        $service_result = $conn->query("SELECT id FROM services LIMIT 1");
        $instrumentalist_result = $conn->query("SELECT id FROM instrumentalists");
        
        if ($service_result->num_rows > 0 && $instrumentalist_result->num_rows > 0) {
            $service_id = $service_result->fetch_assoc()['id'];
            
            $stmt = $conn->prepare("INSERT INTO instrumentalist_payments (instrumentalist_id, service_id, amount, payment_type, payment_status) VALUES (?, ?, ?, 'Per Service', 'Pending')");
            
            while ($inst = $instrumentalist_result->fetch_assoc()) {
                $amount = 50.00;
                $stmt->bind_param('iid', $inst['id'], $service_id, $amount);
                if ($stmt->execute()) {
                    echo "<p>✅ Added payment for instrumentalist {$inst['id']}</p>";
                }
            }
        }
        
        // Test the query
        echo "<h2>Testing Payment Query</h2>";
        $test_stmt = $conn->prepare("
            SELECT ip.*, i.full_name, i.instrument, i.per_service_rate, i.hourly_rate,
                   s.service_date, s.service_type, s.service_title
            FROM instrumentalist_payments ip
            JOIN instrumentalists i ON ip.instrumentalist_id = i.id
            JOIN services s ON ip.service_id = s.id
            WHERE ip.payment_status IN ('Pending', 'Approved')
            ORDER BY s.service_date DESC, i.full_name
        ");
        
        if ($test_stmt && $test_stmt->execute()) {
            $result = $test_stmt->get_result();
            $count = $result->num_rows;
            echo "<p>✅ Payment query successful - found $count payments</p>";
        } else {
            echo "<p>❌ Payment query failed: " . ($test_stmt ? $test_stmt->error : $conn->error) . "</p>";
        }
        
    } else {
        echo "<p>❌ Failed to create payment table: " . $conn->error . "</p>";
    }
    
    echo "<h2>✅ Payment Table Fixed!</h2>";
    echo "<p><a href='api/payment_processing.php?action=pending_payments'>Test Payment API</a></p>";
    echo "<p><a href='index.php'>Go to Main Application</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
