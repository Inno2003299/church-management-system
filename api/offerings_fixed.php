<?php
// Fixed Offerings API
ini_set("display_errors", 0);
error_reporting(0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

try {
    require_once "../config/db.php";
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Check if offerings table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'offerings'");
    if ($table_check->num_rows == 0) {
        // Table doesn't exist, create it
        $create_table = "CREATE TABLE IF NOT EXISTS offerings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offering_type_id INT,
            amount DECIMAL(10,2) NOT NULL,
            offering_date DATE NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (offering_type_id) REFERENCES offering_types(id)
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Failed to create offerings table");
        }
    }
    
    // Check if offering_types table exists
    $types_check = $conn->query("SHOW TABLES LIKE 'offering_types'");
    if ($types_check->num_rows == 0) {
        // Create offering_types table
        $create_types = "CREATE TABLE IF NOT EXISTS offering_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_types)) {
            // Insert default offering types
            $default_types = [
                ['Tithe', 'Regular tithe offerings'],
                ['Thanksgiving', 'Thanksgiving offerings'],
                ['Seed Offering', 'Seed/faith offerings'],
                ['Building Fund', 'Church building fund'],
                ['Mission', 'Mission and outreach'],
                ['Special Collection', 'Special collections and events']
            ];
            
            foreach ($default_types as $type) {
                $stmt = $conn->prepare("INSERT IGNORE INTO offering_types (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $type[0], $type[1]);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Build query based on request
    if (isset($_GET['date'])) {
        // Get offerings for specific date
        $date = $_GET['date'];
        $sql = "SELECT o.id, o.amount, o.offering_date, o.notes, o.created_at, 
                       ot.name as offering_type, ot.id as offering_type_id
                FROM offerings o 
                LEFT JOIN offering_types ot ON o.offering_type_id = ot.id 
                WHERE o.offering_date = ? 
                ORDER BY o.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $offerings = [];
        $total = 0;
        
        while ($row = $result->fetch_assoc()) {
            $offerings[] = $row;
            $total += $row['amount'];
        }
        
        echo json_encode([
            "offerings" => $offerings,
            "total" => $total,
            "date" => $date,
            "count" => count($offerings)
        ]);
        
        $stmt->close();
        
    } else {
        // Get all offerings
        $sql = "SELECT o.id, o.amount, o.offering_date, o.notes, o.created_at,
                       ot.name as offering_type, ot.id as offering_type_id
                FROM offerings o 
                LEFT JOIN offering_types ot ON o.offering_type_id = ot.id 
                ORDER BY o.offering_date DESC, o.created_at DESC";
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $offerings = [];
        while ($row = $result->fetch_assoc()) {
            $offerings[] = $row;
        }
        
        echo json_encode($offerings);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server error: " . $e->getMessage(),
        "offerings" => [],
        "total" => 0
    ]);
}
?>