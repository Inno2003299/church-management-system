<?php
// Complete database fix - run this once to fix everything
header('Content-Type: text/html');

try {
    require_once 'config/db.php';
    
    // Drop and recreate all tables with correct structure
    echo "<h1>Complete Database Fix</h1>";
    
    // Drop existing tables if they exist
    $tables_to_drop = ['instrumentalist_payments', 'instrumentalists', 'services'];
    foreach ($tables_to_drop as $table) {
        $conn->query("DROP TABLE IF EXISTS $table");
        echo "<p>Dropped table: $table</p>";
    }
    
    // Create services table
    $sql = "CREATE TABLE services (
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
        throw new Exception("Failed to create services table: " . $conn->error);
    }
    
    // Create instrumentalists table
    $sql = "CREATE TABLE instrumentalists (
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
        echo "<p>✅ Instrumentalists table created</p>";
    } else {
        throw new Exception("Failed to create instrumentalists table: " . $conn->error);
    }
    
    // Create instrumentalist_payments table
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (instrumentalist_id) REFERENCES instrumentalists(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
        INDEX idx_instrumentalist_payments (instrumentalist_id),
        INDEX idx_service_payments (service_id),
        INDEX idx_payment_status (payment_status)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Instrumentalist payments table created</p>";
    } else {
        throw new Exception("Failed to create instrumentalist payments table: " . $conn->error);
    }
    
    // Add sample data
    echo "<h2>Adding Sample Data</h2>";
    
    // Add sample instrumentalists
    $stmt = $conn->prepare("INSERT INTO instrumentalists (full_name, phone, instrument, skill_level, per_service_rate, hourly_rate) VALUES (?, ?, ?, ?, ?, ?)");
    
    $instrumentalists = [
        ['David Brown', '555-0201', 'Piano', 'Advanced', 50.00, 25.00],
        ['Lisa Davis', '555-0202', 'Guitar', 'Intermediate', 40.00, 20.00],
        ['Mark Taylor', '555-0203', 'Drums', 'Professional', 60.00, 30.00]
    ];
    
    foreach ($instrumentalists as $inst) {
        $stmt->bind_param('ssssdd', $inst[0], $inst[1], $inst[2], $inst[3], $inst[4], $inst[5]);
        if ($stmt->execute()) {
            echo "<p>✅ Added instrumentalist: {$inst[0]}</p>";
        }
    }
    
    // Add sample service
    $today = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO services (service_date, service_type, service_title) VALUES (?, 'Sunday Morning', 'Morning Worship')");
    $stmt->bind_param('s', $today);
    if ($stmt->execute()) {
        $service_id = $stmt->insert_id;
        echo "<p>✅ Added sample service</p>";
        
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
    
    echo "<h2>✅ ALL FIXED!</h2>";
    echo "<p><strong>Your Church Management System is now ready to use.</strong></p>";
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Main Application</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
