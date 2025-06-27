<?php
// Church Inventory Management System - Database Setup
require_once 'config/db.php';

echo "<h1>Church Inventory Management System - Database Setup</h1>";

try {
    // Read and execute the schema file
    $schema = file_get_contents('database/schema.sql');
    
    if (!$schema) {
        throw new Exception('Could not read schema.sql file');
    }
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    echo "<h2>Executing Database Schema...</h2>";
    echo "<ul>";
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        // Skip comments
        if (strpos(trim($statement), '--') === 0) continue;
        
        try {
            $conn->query($statement);
            
            // Extract table name for display
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<li>✓ Created table: <strong>{$matches[1]}</strong></li>";
            } elseif (preg_match('/INSERT.*?INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<li>✓ Inserted data into: <strong>{$matches[1]}</strong></li>";
            } elseif (preg_match('/CREATE DATABASE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<li>✓ Created database: <strong>{$matches[1]}</strong></li>";
            } else {
                echo "<li>✓ Executed statement</li>";
            }
        } catch (Exception $e) {
            echo "<li>⚠ Warning: " . $e->getMessage() . "</li>";
        }
    }
    
    echo "</ul>";
    
    // Test the database connection and tables
    echo "<h2>Testing Database Tables...</h2>";
    echo "<ul>";
    
    $tables = [
        'members',
        'webauthn_credentials',
        'services',
        'attendance',
        'offering_types',
        'offerings',
        'instrumentalists',
        'instrumentalist_payments',
        'payment_batches',
        'payment_batch_items',
        'admin_users',
        'system_settings'
    ];
    
    foreach ($tables as $table) {
        try {
            $result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $row = $result->fetch_assoc();
            echo "<li>✓ Table <strong>$table</strong> exists with {$row['count']} records</li>";
        } catch (Exception $e) {
            echo "<li>❌ Error with table <strong>$table</strong>: " . $e->getMessage() . "</li>";
        }
    }
    
    echo "</ul>";
    
    // Add some sample data for testing
    echo "<h2>Adding Sample Data...</h2>";
    echo "<ul>";
    
    // Sample members
    $sampleMembers = [
        ['John Doe', '555-0101', 'Male'],
        ['Jane Smith', '555-0102', 'Female'],
        ['Michael Johnson', '555-0103', 'Male'],
        ['Sarah Wilson', '555-0104', 'Female']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO members (full_name, phone, gender) VALUES (?, ?, ?)");
    foreach ($sampleMembers as $member) {
        $stmt->bind_param('sss', $member[0], $member[1], $member[2]);
        if ($stmt->execute()) {
            echo "<li>✓ Added member: <strong>{$member[0]}</strong></li>";
        }
    }
    
    // Sample instrumentalists
    $sampleInstrumentalists = [
        ['David Brown', '555-0201', 'Piano', 'Advanced', 50.00],
        ['Lisa Davis', '555-0202', 'Guitar', 'Intermediate', 40.00],
        ['Mark Taylor', '555-0203', 'Drums', 'Professional', 60.00]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO instrumentalists (full_name, phone, instrument, skill_level, per_service_rate) VALUES (?, ?, ?, ?, ?)");
    foreach ($sampleInstrumentalists as $instrumentalist) {
        $stmt->bind_param('ssssd', $instrumentalist[0], $instrumentalist[1], $instrumentalist[2], $instrumentalist[3], $instrumentalist[4]);
        if ($stmt->execute()) {
            echo "<li>✓ Added instrumentalist: <strong>{$instrumentalist[0]}</strong> ({$instrumentalist[2]})</li>";
        }
    }
    
    // Sample services and payments for demonstration
    $today = date('Y-m-d');
    $lastSunday = date('Y-m-d', strtotime('last Sunday'));

    // Create sample services
    $stmt = $conn->prepare("INSERT IGNORE INTO services (service_date, service_type) VALUES (?, 'Sunday Morning')");
    $stmt->bind_param('s', $lastSunday);
    $stmt->execute();
    $service_id = $stmt->insert_id ?: $conn->query("SELECT id FROM services WHERE service_date='$lastSunday' AND service_type='Sunday Morning'")->fetch_assoc()['id'];

    // Create sample payments
    $stmt = $conn->prepare("INSERT IGNORE INTO instrumentalist_payments (instrumentalist_id, service_id, amount, payment_type, payment_status) VALUES (?, ?, ?, 'Per Service', 'Pending')");

    // Get instrumentalist IDs
    $result = $conn->query("SELECT id FROM instrumentalists LIMIT 3");
    $instrumentalist_ids = [];
    while ($row = $result->fetch_assoc()) {
        $instrumentalist_ids[] = $row['id'];
    }

    foreach ($instrumentalist_ids as $inst_id) {
        $amount = rand(40, 60); // Random amount between $40-60
        $stmt->bind_param('iid', $inst_id, $service_id, $amount);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo "<li>✓ Created sample payment for instrumentalist ID: <strong>$inst_id</strong> - $$amount</li>";
        }
    }

    echo "</ul>";

    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>Your Church Inventory Management System database has been set up successfully.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Access the main application at: <a href='index.html'>index.html</a></li>";
    echo "<li>Default admin login: <strong>admin</strong> / <strong>admin123</strong></li>";
    echo "<li>Test fingerprint registration with the sample members</li>";
    echo "<li>Configure your church settings in the admin panel</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h1, h2 {
    color: #333;
}

ul {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

li {
    margin: 5px 0;
    padding: 5px 0;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
