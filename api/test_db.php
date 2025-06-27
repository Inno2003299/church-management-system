<?php
// Test database connection and table structure
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    echo "Testing database connection...\n";
    require_once '../config/db.php';

    if (!$conn) {
        throw new Exception("Database connection is null");
    }

    echo "Database connection OK\n";

    // Check if required tables exist and show their structure
    $tables = ['instrumentalist_payments', 'instrumentalists', 'services'];

    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            echo "❌ Table '$table' does not exist\n";
        } else {
            echo "✅ Table '$table' exists\n";

            // Show table structure
            $desc = $conn->query("DESCRIBE $table");
            echo "  Columns in $table:\n";
            while ($col = $desc->fetch_assoc()) {
                echo "    - {$col['Field']} ({$col['Type']})\n";
            }
        }
    }

    // Test the exact query from get_pending_payments
    echo "\nTesting payments query...\n";
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument, i.per_service_rate, i.hourly_rate,
               s.service_date, s.service_type, s.service_title
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE ip.payment_status IN ('Pending', 'Approved')
        ORDER BY s.service_date DESC, i.full_name
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $payments = [];

    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }

    echo "Query executed successfully\n";
    echo "Found " . count($payments) . " payments\n";

    if (count($payments) > 0) {
        echo "\nFirst payment record:\n";
        print_r($payments[0]);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
