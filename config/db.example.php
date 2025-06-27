<?php
// Church Inventory Management System - Database Configuration Template
// Copy this file to db.php and update with your actual database credentials

// Disable error output for API calls
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Database configuration - UPDATE THESE VALUES
$host = 'localhost';        // Your database host
$port = 3306;              // Your database port
$user = 'your_username';   // Your database username
$pass = 'your_password';   // Your database password
$db   = 'church_db';       // Your database name

try {
    // Connect to MySQL
    $conn = new mysqli($host, $user, $pass, $db, $port);
    
    if ($conn->connect_error) {
        throw new Exception("MySQL connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset('utf8mb4');
    
    // Connection successful
    
} catch (Exception $e) {
    // Log the error
    error_log("Database error: " . $e->getMessage());
    
    // For API responses, return JSON error
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Database connection failed',
            'message' => $e->getMessage(),
            'success' => false
        ]);
        exit;
    } else {
        // For regular pages, show error
        die("Database connection failed: " . $e->getMessage() . "<br><br>Please check your database configuration in config/db.php");
    }
}
?>
