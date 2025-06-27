<?php
// Fix services table structure
header('Content-Type: text/html');

echo "<h1>Fixing Services Table</h1>";

try {
    require_once 'config/db.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Check current services table structure
    $result = $conn->query("DESCRIBE services");
    if ($result) {
        echo "<h2>Current Services Table Structure:</h2>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
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
        
        // Check if service_title column exists
        if (!in_array('service_title', $columns)) {
            echo "<p>❌ Missing 'service_title' column. Adding it...</p>";
            $alter_sql = "ALTER TABLE services ADD COLUMN service_title VARCHAR(255) NULL";
            if ($conn->query($alter_sql)) {
                echo "<p>✅ Added 'service_title' column</p>";
            } else {
                echo "<p>❌ Failed to add 'service_title' column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>✅ 'service_title' column exists</p>";
        }
        
        // Check if start_time column exists
        if (!in_array('start_time', $columns)) {
            echo "<p>❌ Missing 'start_time' column. Adding it...</p>";
            $alter_sql = "ALTER TABLE services ADD COLUMN start_time TIME NULL";
            if ($conn->query($alter_sql)) {
                echo "<p>✅ Added 'start_time' column</p>";
            } else {
                echo "<p>❌ Failed to add 'start_time' column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>✅ 'start_time' column exists</p>";
        }
        
        // Check if end_time column exists
        if (!in_array('end_time', $columns)) {
            echo "<p>❌ Missing 'end_time' column. Adding it...</p>";
            $alter_sql = "ALTER TABLE services ADD COLUMN end_time TIME NULL";
            if ($conn->query($alter_sql)) {
                echo "<p>✅ Added 'end_time' column</p>";
            } else {
                echo "<p>❌ Failed to add 'end_time' column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>✅ 'end_time' column exists</p>";
        }
        
        // Check if description column exists
        if (!in_array('description', $columns)) {
            echo "<p>❌ Missing 'description' column. Adding it...</p>";
            $alter_sql = "ALTER TABLE services ADD COLUMN description TEXT NULL";
            if ($conn->query($alter_sql)) {
                echo "<p>✅ Added 'description' column</p>";
            } else {
                echo "<p>❌ Failed to add 'description' column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>✅ 'description' column exists</p>";
        }
        
    } else {
        echo "<p>❌ Services table does not exist. Creating it...</p>";
        
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
            echo "<p>❌ Failed to create services table: " . $conn->error . "</p>";
        }
    }
    
    // Add sample service if none exist
    $count_result = $conn->query("SELECT COUNT(*) as count FROM services");
    $count = $count_result->fetch_assoc()['count'];
    
    if ($count == 0) {
        echo "<h2>Adding Sample Service</h2>";
        $today = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO services (service_date, service_type, service_title) VALUES (?, 'Sunday Morning', 'Morning Worship')");
        $stmt->bind_param('s', $today);
        if ($stmt->execute()) {
            echo "<p>✅ Added sample service for today</p>";
        } else {
            echo "<p>❌ Failed to add sample service: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>✅ Services table has $count records</p>";
    }
    
    // Test the services query
    echo "<h2>Testing Services Query</h2>";
    $stmt = $conn->prepare("
        SELECT id, service_date, service_type, service_title, created_at
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
        echo "<p>✅ Services query successful - found " . count($services) . " services</p>";
        
        if (count($services) > 0) {
            echo "<h3>Services Data:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Date</th><th>Type</th><th>Title</th></tr>";
            foreach ($services as $service) {
                echo "<tr>";
                echo "<td>{$service['id']}</td>";
                echo "<td>{$service['service_date']}</td>";
                echo "<td>{$service['service_type']}</td>";
                echo "<td>{$service['service_title']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ Services query failed: " . ($stmt ? $stmt->error : $conn->error) . "</p>";
    }
    
    echo "<h2>✅ Services Table Fixed!</h2>";
    echo "<p><a href='api/services.php'>Test Services API</a></p>";
    echo "<p><a href='index.php'>Go back to main page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
