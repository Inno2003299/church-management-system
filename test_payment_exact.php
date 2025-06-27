<?php
// Test exact payment API call
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/db.php';
    
    // Simulate the exact same call as payment_processing.php
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['action'] = 'pending_payments';
    
    // Include the actual payment processing file
    ob_start();
    include 'api/payment_processing.php';
    $output = ob_get_clean();
    
    echo $output;
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
