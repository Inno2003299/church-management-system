<?php
// Force enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../config/db.php';
    
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Get input data
    $input = file_get_contents('php://input');
    error_log("Raw input received: " . $input);

    $data = json_decode($input, true);
    error_log("Decoded data: " . print_r($data, true));

    if (!$data) {
        // Try to get data from POST if JSON failed
        $data = $_POST;
        error_log("Fallback to POST data: " . print_r($data, true));
    }

    if (!$data) {
        throw new Exception('No data received at all');
    }
    
    // Create tables if they don't exist (simplified)
    $result1 = $conn->query("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_date DATE NOT NULL,
        service_type VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    if (!$result1) {
        throw new Exception('Failed to create services table: ' . $conn->error);
    }

    // Simple table creation without dropping
    $conn->query("CREATE TABLE IF NOT EXISTS offerings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_date DATE,
        service_type VARCHAR(255),
        offering_type VARCHAR(255),
        amount DECIMAL(10,2),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Get data fields with multiple possible field names
    $offering_date = $data['offering_date'] ?? $data['service_date'] ?? $data['date'] ?? null;
    $service_type = $data['service_type'] ?? null;
    $offering_type = $data['offering_type'] ?? $data['offering_type_id'] ?? null;
    $amount = $data['amount'] ?? null;
    $notes = $data['notes'] ?? '';

    // Convert amount to float if it's a string
    if ($amount) {
        $amount = floatval($amount);
    }

    // If still missing data, provide detailed error
    $missing = [];
    if (!$offering_date) $missing[] = 'offering_date/service_date/date';
    if (!$service_type) $missing[] = 'service_type';
    if (!$offering_type) $missing[] = 'offering_type/offering_type_id';
    if (!$amount || $amount <= 0) $missing[] = 'amount (must be > 0)';

    if (!empty($missing)) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing),
            'received_fields' => array_keys($data),
            'received_data' => $data
        ]);
        exit;
    }
    
    // Check if table exists first
    $tableCheck = $conn->query("SHOW TABLES LIKE 'offerings'");
    if ($tableCheck->num_rows == 0) {
        throw new Exception('Offerings table does not exist');
    }

    // Check table structure
    $columnsCheck = $conn->query("SHOW COLUMNS FROM offerings");
    $columns = [];
    while ($row = $columnsCheck->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    // Record offering directly (simplified approach)
    $stmt = $conn->prepare("INSERT INTO offerings (service_date, service_type, offering_type, amount, notes) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Failed to prepare offering statement. SQL Error: ' . $conn->error . '. Available columns: ' . implode(', ', $columns));
    }

    $stmt->bind_param('sssds', $offering_date, $service_type, $offering_type, $amount, $notes);

    if ($stmt->execute()) {
        $offeringId = $conn->insert_id;

        echo json_encode([
            'success' => true,
            'message' => 'Offering recorded successfully',
            'offering_id' => $offeringId,
            'amount' => $amount,
            'date' => $offering_date,
            'service' => $service_type,
            'offering_type' => $offering_type
        ]);
    } else {
        throw new Exception('Failed to record offering: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
