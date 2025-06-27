<?php
// Fixed Instrumentalists API
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
    
    // Check if instrumentalists table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'instrumentalists'");
    if ($table_check->num_rows == 0) {
        // Table doesn't exist, create it
        $create_table = "CREATE TABLE IF NOT EXISTS instrumentalists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            instrument VARCHAR(100),
            skill_level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Failed to create instrumentalists table");
        }
    }
    
    // Build query
    $sql = "SELECT id, full_name, phone, email, instrument, skill_level, is_active, created_at FROM instrumentalists";
    
    // Add active filter if requested
    if (isset($_GET['active_only']) && $_GET['active_only'] === 'true') {
        $sql .= " WHERE is_active = 1";
    }
    
    $sql .= " ORDER BY full_name";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $instrumentalists = [];
    while ($row = $result->fetch_assoc()) {
        $instrumentalists[] = $row;
    }
    
    echo json_encode($instrumentalists);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server error: " . $e->getMessage(),
        "instrumentalists" => []
    ]);
}
?>