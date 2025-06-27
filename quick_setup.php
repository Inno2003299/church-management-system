<?php
// Quick Setup - Minimal database setup for testing
echo "<h1>Church Management System - Quick Setup</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Step 1: Testing Database Connection</h2>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'church_db';

try {
    // Test basic MySQL connection
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<div style='color: green;'>✅ MySQL connection successful!</div>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $db";
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green;'>✅ Database '$db' created/verified</div>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($db);
    
    echo "<h2>Step 2: Creating Essential Tables</h2>";
    
    // Create members table (minimal)
    $sql = "CREATE TABLE IF NOT EXISTS members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        gender ENUM('Male', 'Female') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green;'>✅ Members table created</div>";
    } else {
        echo "<div style='color: red;'>❌ Error creating members table: " . $conn->error . "</div>";
    }
    
    // Create services table (minimal)
    $sql = "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_date DATE NOT NULL,
        service_type VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green;'>✅ Services table created</div>";
    } else {
        echo "<div style='color: red;'>❌ Error creating services table: " . $conn->error . "</div>";
    }
    
    // Create offering_types table with data
    $sql = "CREATE TABLE IF NOT EXISTS offering_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green;'>✅ Offering types table created</div>";
        
        // Insert default offering types
        $offering_types = [
            ['Tithe', 'Regular tithe offerings'],
            ['Thanksgiving', 'Thanksgiving offerings'],
            ['Seed Offering', 'Seed/faith offerings'],
            ['Building Fund', 'Church building fund'],
            ['Mission', 'Mission support'],
            ['Special Collection', 'Special collections']
        ];
        
        foreach ($offering_types as $type) {
            $stmt = $conn->prepare("INSERT IGNORE INTO offering_types (name, description) VALUES (?, ?)");
            $stmt->bind_param('ss', $type[0], $type[1]);
            $stmt->execute();
        }
        echo "<div style='color: green;'>✅ Default offering types added</div>";
    }
    
    // Create instrumentalists table (minimal)
    $sql = "CREATE TABLE IF NOT EXISTS instrumentalists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        instrument VARCHAR(100) NOT NULL,
        per_service_rate DECIMAL(8,2),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green;'>✅ Instrumentalists table created</div>";
    } else {
        echo "<div style='color: red;'>❌ Error creating instrumentalists table: " . $conn->error . "</div>";
    }
    
    echo "<h2>Step 3: Adding Sample Data</h2>";
    
    // Add sample members
    $sample_members = [
        ['John Doe', '555-0101', 'Male'],
        ['Jane Smith', '555-0102', 'Female'],
        ['Mike Johnson', '555-0103', 'Male']
    ];
    
    foreach ($sample_members as $member) {
        $stmt = $conn->prepare("INSERT IGNORE INTO members (full_name, phone, gender) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $member[0], $member[1], $member[2]);
        if ($stmt->execute()) {
            echo "<div style='color: green;'>✅ Added member: {$member[0]}</div>";
        }
    }
    
    // Add sample instrumentalists
    $sample_instrumentalists = [
        ['David Brown', 'Piano', 50.00],
        ['Lisa Davis', 'Guitar', 40.00],
        ['Mark Taylor', 'Drums', 45.00]
    ];
    
    foreach ($sample_instrumentalists as $inst) {
        $stmt = $conn->prepare("INSERT IGNORE INTO instrumentalists (full_name, instrument, per_service_rate) VALUES (?, ?, ?)");
        $stmt->bind_param('ssd', $inst[0], $inst[1], $inst[2]);
        if ($stmt->execute()) {
            echo "<div style='color: green;'>✅ Added instrumentalist: {$inst[0]} ({$inst[1]})</div>";
        }
    }
    
    echo "<h2>Step 4: Testing API Endpoints</h2>";
    
    // Test members API
    $result = $conn->query("SELECT COUNT(*) as count FROM members");
    $row = $result->fetch_assoc();
    echo "<div style='color: green;'>✅ Members table has {$row['count']} records</div>";
    
    // Test offering types API
    $result = $conn->query("SELECT COUNT(*) as count FROM offering_types");
    $row = $result->fetch_assoc();
    echo "<div style='color: green;'>✅ Offering types table has {$row['count']} records</div>";
    
    // Test instrumentalists API
    $result = $conn->query("SELECT COUNT(*) as count FROM instrumentalists");
    $row = $result->fetch_assoc();
    echo "<div style='color: green;'>✅ Instrumentalists table has {$row['count']} records</div>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Quick Setup Complete!</h3>";
    echo "<p>Your basic database is now set up and ready to use.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='test_connection.html'>Test the connection</a> to verify everything is working</li>";
    echo "<li><a href='index.html'>Open the main application</a> and try adding a member</li>";
    echo "<li>If you need more features, run the <a href='setup.php'>full setup</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP/MAMP MySQL service is running</li>";
    echo "<li>Check if MySQL is using default port 3306</li>";
    echo "<li>Try using username 'root' with empty password</li>";
    echo "<li>Check the <a href='troubleshoot.html'>troubleshooting guide</a></li>";
    echo "</ul>";
    echo "</div>";
}

if (isset($conn)) {
    $conn->close();
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h1, h2, h3 {
    color: #333;
}

div {
    margin: 5px 0;
    padding: 5px;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
