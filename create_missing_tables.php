<?php
// Create missing tables immediately
header('Content-Type: text/html');

try {
    require_once 'config/db.php';
    echo "<h1>Creating Missing Tables</h1>";
    
    // Create instrumentalist_payments table
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ instrumentalist_payments table created</p>";
        
        // Add sample payments
        $stmt = $conn->prepare("INSERT INTO instrumentalist_payments (instrumentalist_id, service_id, amount, payment_type, payment_status) VALUES (?, ?, ?, 'Per Service', 'Pending')");
        
        // Get service and instrumentalist IDs
        $service_result = $conn->query("SELECT id FROM services LIMIT 1");
        $instrumentalist_result = $conn->query("SELECT id FROM instrumentalists LIMIT 3");
        
        if ($service_result->num_rows > 0 && $instrumentalist_result->num_rows > 0) {
            $service_id = $service_result->fetch_assoc()['id'];
            
            while ($inst = $instrumentalist_result->fetch_assoc()) {
                $amount = 50.00;
                $stmt->bind_param('iid', $inst['id'], $service_id, $amount);
                if ($stmt->execute()) {
                    echo "<p>✅ Added sample payment for instrumentalist {$inst['id']}</p>";
                }
            }
        }
    } else {
        echo "<p>❌ Failed to create instrumentalist_payments table: " . $conn->error . "</p>";
    }
    
    echo "<h2>✅ Tables Created!</h2>";
    echo "<p><a href='api/payment_processing.php?action=pending_payments'>Test Payment API</a></p>";
    echo "<p><a href='api/services.php'>Test Services API</a></p>";
    echo "<p><a href='index.php'>Go to Main Application</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
