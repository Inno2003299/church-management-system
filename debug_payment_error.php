<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

try {
    require_once 'config/db.php';
    
    echo "=== DEBUGGING PAYMENT ERROR ===\n\n";
    
    // Check services table structure
    echo "Services table columns:\n";
    $result = $conn->query("DESCRIBE services");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nInstrumentalists table columns:\n";
    $result = $conn->query("DESCRIBE instrumentalists");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nPayments table columns:\n";
    $result = $conn->query("DESCRIBE instrumentalist_payments");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    // Test simple query first
    echo "\n=== TESTING SIMPLE QUERIES ===\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM services");
    $count = $result->fetch_assoc()['count'];
    echo "Services count: $count\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM instrumentalists");
    $count = $result->fetch_assoc()['count'];
    echo "Instrumentalists count: $count\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM instrumentalist_payments");
    $count = $result->fetch_assoc()['count'];
    echo "Payments count: $count\n";
    
    // Test the exact query with minimal columns
    echo "\n=== TESTING PAYMENT QUERY ===\n";
    $stmt = $conn->prepare("
        SELECT ip.id, ip.amount, ip.payment_status,
               i.full_name, i.instrument,
               s.service_date, s.service_type
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE ip.payment_status IN ('Pending', 'Approved')
        ORDER BY s.service_date DESC, i.full_name
    ");
    
    if (!$stmt) {
        echo "PREPARE ERROR: " . $conn->error . "\n";
    } else {
        if (!$stmt->execute()) {
            echo "EXECUTE ERROR: " . $stmt->error . "\n";
        } else {
            $result = $stmt->get_result();
            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
            echo "SUCCESS: Found " . count($payments) . " payments\n";
            if (count($payments) > 0) {
                echo "First payment: " . json_encode($payments[0]) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
