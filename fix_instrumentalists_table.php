<?php
// Fix instrumentalists table structure
header('Content-Type: text/html');

echo "<h1>Fixing Instrumentalists Table</h1>";

try {
    require_once 'config/db.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Check if instrumentalists table exists
    $result = $conn->query("SHOW TABLES LIKE 'instrumentalists'");
    if ($result->num_rows == 0) {
        echo "<p>❌ Instrumentalists table does not exist. Creating it...</p>";
        
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
            echo "<p>❌ Failed to create instrumentalists table: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ Instrumentalists table exists</p>";
        
        // Check current structure
        $desc = $conn->query("DESCRIBE instrumentalists");
        echo "<h2>Current Instrumentalists Table Structure:</h2>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $columns = [];
        while ($row = $desc->fetch_assoc()) {
            $columns[] = $row['Field'];
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for required columns and add if missing
        $required_columns = [
            'per_service_rate' => 'DECIMAL(8,2)',
            'hourly_rate' => 'DECIMAL(8,2)',
            'instrument' => 'VARCHAR(100)'
        ];
        
        foreach ($required_columns as $column => $type) {
            if (!in_array($column, $columns)) {
                echo "<p>❌ Missing '$column' column. Adding it...</p>";
                $alter_sql = "ALTER TABLE instrumentalists ADD COLUMN $column $type";
                if ($conn->query($alter_sql)) {
                    echo "<p>✅ Added '$column' column</p>";
                } else {
                    echo "<p>❌ Failed to add '$column' column: " . $conn->error . "</p>";
                }
            } else {
                echo "<p>✅ '$column' column exists</p>";
            }
        }
    }
    
    // Add sample instrumentalists if none exist
    $count_result = $conn->query("SELECT COUNT(*) as count FROM instrumentalists");
    $count = $count_result->fetch_assoc()['count'];
    
    if ($count == 0) {
        echo "<h2>Adding Sample Instrumentalists</h2>";
        
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
            } else {
                echo "<p>❌ Failed to add {$inst[0]}: " . $stmt->error . "</p>";
            }
        }
    } else {
        echo "<p>✅ Instrumentalists table has $count records</p>";
    }
    
    // Test instrumentalists query
    echo "<h2>Testing Instrumentalists Query</h2>";
    $stmt = $conn->prepare("SELECT id, full_name, instrument, per_service_rate, hourly_rate FROM instrumentalists WHERE is_active = 1");
    
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        $instrumentalists = [];
        while ($row = $result->fetch_assoc()) {
            $instrumentalists[] = $row;
        }
        echo "<p>✅ Instrumentalists query successful - found " . count($instrumentalists) . " instrumentalists</p>";
        
        if (count($instrumentalists) > 0) {
            echo "<h3>Instrumentalists Data:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Instrument</th><th>Per Service Rate</th><th>Hourly Rate</th></tr>";
            foreach ($instrumentalists as $inst) {
                echo "<tr>";
                echo "<td>{$inst['id']}</td>";
                echo "<td>{$inst['full_name']}</td>";
                echo "<td>{$inst['instrument']}</td>";
                echo "<td>\${$inst['per_service_rate']}</td>";
                echo "<td>\${$inst['hourly_rate']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ Instrumentalists query failed: " . ($stmt ? $stmt->error : $conn->error) . "</p>";
    }
    
    echo "<h2>✅ Instrumentalists Table Fixed!</h2>";
    echo "<p><a href='api/instrumentalists.php'>Test Instrumentalists API</a></p>";
    echo "<p><a href='index.php'>Go back to main page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
