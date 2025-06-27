<?php
// Simple Database Connection for Church Management System
// This is a minimal, working database connection

// Disable error output for API calls to prevent HTML in JSON
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Database configuration - try multiple common setups
$db_configs = [
    // MAMP Pro / Standard MySQL
    [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'root',
        'db' => 'church_db'
    ],
    // MAMP Default
    [
        'host' => 'localhost',
        'port' => 8889,
        'user' => 'root',
        'pass' => 'root',
        'db' => 'church_db'
    ],
    // XAMPP / Standard
    [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'db' => 'church_db'
    ]
];

$conn = null;
$connection_error = '';

// Try each configuration until one works
foreach ($db_configs as $config) {
    try {
        $conn = new mysqli($config['host'], $config['user'], $config['pass'], '', $config['port']);
        
        if (!$conn->connect_error) {
            // Connection successful, now check/create database
            $result = $conn->query("SHOW DATABASES LIKE '{$config['db']}'");
            if ($result->num_rows == 0) {
                // Database doesn't exist, create it
                if ($conn->query("CREATE DATABASE {$config['db']}")) {
                    error_log("Database '{$config['db']}' created successfully");
                }
            }
            
            // Select the database
            $conn->select_db($config['db']);
            $conn->set_charset('utf8mb4');
            
            // Success! Break out of the loop
            break;
        } else {
            $connection_error = $conn->connect_error;
            $conn = null;
        }
    } catch (Exception $e) {
        $connection_error = $e->getMessage();
        $conn = null;
        continue;
    }
}

// If no connection worked, handle the error
if (!$conn) {
    $error_message = "Database connection failed. Last error: " . $connection_error;
    error_log($error_message);
    
    // For API calls, return JSON error
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Database connection failed',
            'message' => 'Please check that MySQL is running',
            'success' => false
        ]);
        exit;
    } else {
        // For regular pages, show user-friendly error
        die("
        <div style='font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
            <h2 style='color: #d32f2f;'>Database Connection Error</h2>
            <p><strong>The Church Management System cannot connect to the database.</strong></p>
            <p>Please check:</p>
            <ul>
                <li>MAMP or XAMPP is running</li>
                <li>MySQL service is started</li>
                <li>MySQL is running on port 3306 or 8889</li>
            </ul>
            <p><a href='debug_connection.php' style='color: #1976d2;'>Run Database Debug Tool</a></p>
        </div>
        ");
    }
}

// Connection successful - $conn is now available for use
?>
