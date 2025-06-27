<?php
require_once 'config/db.php';

echo "<h2>Creating Test Data for Church Management System</h2>\n";

try {
    // Create test instrumentalists if they don't exist
    $instrumentalists = [
        ['John Doe', 'Piano', 50.00, 15.00],
        ['Jane Smith', 'Guitar', 45.00, 12.00],
        ['Mike Johnson', 'Drums', 55.00, 18.00],
        ['Sarah Wilson', 'Violin', 40.00, 14.00],
        ['David Brown', 'Bass Guitar', 48.00, 16.00]
    ];
    
    echo "<h3>Creating Instrumentalists...</h3>\n";
    
    foreach ($instrumentalists as $inst) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO instrumentalists 
            (full_name, instrument, per_service_rate, hourly_rate, status, phone, email, created_at) 
            VALUES (?, ?, ?, ?, 'Active', '0241234567', ?, NOW())
        ");
        
        $email = strtolower(str_replace(' ', '.', $inst[0])) . '@church.com';
        $stmt->bind_param('ssdds', $inst[0], $inst[1], $inst[2], $inst[3], $email);
        
        if ($stmt->execute()) {
            echo "✓ Created instrumentalist: {$inst[0]} - {$inst[1]}<br>\n";
        } else {
            echo "• Instrumentalist {$inst[0]} already exists<br>\n";
        }
    }
    
    // Create test services
    echo "<h3>Creating Services...</h3>\n";
    
    $services = [
        [date('Y-m-d'), 'Sunday Morning'],
        [date('Y-m-d', strtotime('-1 day')), 'Sunday Evening'],
        [date('Y-m-d', strtotime('-3 days')), 'Wednesday'],
        [date('Y-m-d', strtotime('-7 days')), 'Sunday Morning'],
        [date('Y-m-d', strtotime('+7 days')), 'Sunday Morning']
    ];
    
    foreach ($services as $service) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO services 
            (service_date, service_type, created_at) 
            VALUES (?, ?, NOW())
        ");
        
        $stmt->bind_param('ss', $service[0], $service[1]);
        
        if ($stmt->execute()) {
            echo "✓ Created service: {$service[1]} on {$service[0]}<br>\n";
        } else {
            echo "• Service {$service[1]} on {$service[0]} already exists<br>\n";
        }
    }
    
    // Create some test payments
    echo "<h3>Creating Test Payments...</h3>\n";
    
    // Get instrumentalist and service IDs
    $inst_result = $conn->query("SELECT id, full_name, per_service_rate FROM instrumentalists LIMIT 3");
    $service_result = $conn->query("SELECT id, service_date, service_type FROM services LIMIT 3");
    
    $instrumentalists_data = [];
    while ($row = $inst_result->fetch_assoc()) {
        $instrumentalists_data[] = $row;
    }
    
    $services_data = [];
    while ($row = $service_result->fetch_assoc()) {
        $services_data[] = $row;
    }
    
    if (!empty($instrumentalists_data) && !empty($services_data)) {
        $payment_statuses = ['Pending', 'Approved', 'Paid'];
        
        foreach ($instrumentalists_data as $inst) {
            foreach ($services_data as $service) {
                // Check if payment already exists
                $check_stmt = $conn->prepare("
                    SELECT id FROM instrumentalist_payments 
                    WHERE instrumentalist_id = ? AND service_id = ?
                ");
                $check_stmt->bind_param('ii', $inst['id'], $service['id']);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows == 0) {
                    $status = $payment_statuses[array_rand($payment_statuses)];
                    $amount = $inst['per_service_rate'];
                    
                    $stmt = $conn->prepare("
                        INSERT INTO instrumentalist_payments 
                        (instrumentalist_id, service_id, amount, payment_type, payment_status, notes, created_at) 
                        VALUES (?, ?, ?, 'Per Service', ?, 'Test payment', NOW())
                    ");
                    
                    $stmt->bind_param('iids', $inst['id'], $service['id'], $amount, $status);
                    
                    if ($stmt->execute()) {
                        echo "✓ Created payment: {$inst['full_name']} - {$service['service_type']} - GH₵{$amount} ({$status})<br>\n";
                    }
                }
            }
        }
    }
    
    // Create some test members
    echo "<h3>Creating Test Members...</h3>\n";
    
    $members = [
        ['John Doe', 'Male', '1990-01-15', '0241234567', 'john.doe@email.com'],
        ['Jane Smith', 'Female', '1985-05-20', '0242345678', 'jane.smith@email.com'],
        ['Mike Johnson', 'Male', '1992-08-10', '0243456789', 'mike.johnson@email.com'],
        ['Sarah Wilson', 'Female', '1988-12-03', '0244567890', 'sarah.wilson@email.com'],
        ['David Brown', 'Male', '1995-03-25', '0245678901', 'david.brown@email.com']
    ];
    
    foreach ($members as $member) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO members 
            (full_name, gender, date_of_birth, phone, email, membership_status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'Active', NOW())
        ");
        
        $stmt->bind_param('sssss', $member[0], $member[1], $member[2], $member[3], $member[4]);
        
        if ($stmt->execute()) {
            echo "✓ Created member: {$member[0]}<br>\n";
        } else {
            echo "• Member {$member[0]} already exists<br>\n";
        }
    }
    
    echo "<h3>✅ Test data creation completed!</h3>\n";
    echo "<p><a href='index.php'>Go to Church Management System</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ Error creating test data:</h3>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>
