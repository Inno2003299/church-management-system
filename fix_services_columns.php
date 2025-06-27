<?php
// Fix services table columns
header('Content-Type: text/html');

try {
    require_once 'config/db.php';
    echo "<h1>Fixing Services Table Columns</h1>";
    
    // Check current services table structure
    $result = $conn->query("DESCRIBE services");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo "<p>Current columns: " . implode(', ', $columns) . "</p>";
    
    // Add missing columns
    if (!in_array('created_at', $columns)) {
        $sql = "ALTER TABLE services ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        if ($conn->query($sql)) {
            echo "<p>✅ Added created_at column</p>";
        } else {
            echo "<p>❌ Failed to add created_at: " . $conn->error . "</p>";
        }
    }
    
    if (!in_array('service_title', $columns)) {
        $sql = "ALTER TABLE services ADD COLUMN service_title VARCHAR(255)";
        if ($conn->query($sql)) {
            echo "<p>✅ Added service_title column</p>";
        } else {
            echo "<p>❌ Failed to add service_title: " . $conn->error . "</p>";
        }
    }
    
    echo "<h2>✅ Services Table Fixed!</h2>";
    echo "<p><a href='api/services.php'>Test Services API</a></p>";
    echo "<p><a href='index.php'>Go to Main Application</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
