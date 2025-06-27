<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Helper functions
function respond_json($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

function respond_error($message, $status_code = 400) {
    respond_json(['error' => $message], $status_code);
}

function validate_required_fields($data, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            respond_error("Missing required field: $field");
        }
    }
}

function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function json_input() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function find_or_create_service(mysqli $conn, string $date, string $type): int {
    $stmt = $conn->prepare("SELECT id FROM services WHERE service_date=? AND service_type=?");
    $stmt->bind_param('ss', $date, $type);
    $stmt->execute();
    $stmt->bind_result($id);
    if ($stmt->fetch()) {
        $stmt->close();
        return $id;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO services (service_date, service_type) VALUES (?,?)");
    $stmt->bind_param('ss', $date, $type);
    $stmt->execute();
    return $stmt->insert_id;
}

try {
    require_once '../config/db.php';

    $method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'pending_payments':
                    get_pending_payments();
                    break;
                case 'payment_summary':
                    get_payment_summary();
                    break;
                case 'calculate_payments':
                    calculate_service_payments();
                    break;
                case 'payment_batches':
                    get_payment_batches();
                    break;
                case 'batch_details':
                    get_batch_details((int)$_GET['batch_id']);
                    break;
                default:
                    respond_error('Invalid action');
            }
        } else {
            get_pending_payments();
        }
        break;
    
    case 'POST':
        if (isset($_POST['action']) || isset($_GET['action'])) {
            $action = $_POST['action'] ?? $_GET['action'];
            switch ($action) {
                case 'calculate_payment':
                    calculate_individual_payment();
                    break;
                case 'create_payment':
                    create_payment();
                    break;
                case 'approve_payment':
                    approve_payment();
                    break;
                case 'process_payment':
                    process_payment();
                    break;
                case 'create_batch':
                    create_payment_batch();
                    break;
                case 'approve_batch':
                    approve_payment_batch();
                    break;
                case 'process_batch':
                    process_payment_batch();
                    break;
                case 'bulk_calculate':
                    bulk_calculate_payments();
                    break;
                case 'bulk_process_payments':
                    bulk_process_payments();
                    break;
                default:
                    respond_error('Invalid action');
            }
        } else {
            respond_error('Action required');
        }
        break;
    
    default:
        respond_error('Method not allowed', 405);
}

function get_pending_payments() {
    global $conn;

    $stmt = $conn->prepare("
        SELECT ip.id, ip.instrumentalist_id, ip.service_id, ip.amount,
               ip.payment_type, ip.payment_status, ip.payment_date, ip.created_at,
               i.full_name, i.instrument,
               COALESCE(i.per_service_rate, 0) as per_service_rate,
               COALESCE(i.hourly_rate, 0) as hourly_rate,
               s.service_date, s.service_type
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE ip.payment_status IN ('Pending', 'Approved')
        ORDER BY s.service_date DESC, i.full_name
    ");

    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        respond_json($payments);
    } else {
        respond_error('Query failed: ' . ($stmt ? $stmt->error : $conn->error));
    }
}

function get_payment_summary() {
    global $conn;
    
    $date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m');
    
    $stmt = $conn->prepare("
        SELECT 
            payment_status,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM instrumentalist_payments ip
        JOIN services s ON ip.service_id = s.id
        WHERE DATE_FORMAT(s.service_date, '%Y-%m') = ?
        GROUP BY payment_status
    ");
    $stmt->bind_param('s', $date_filter);
    $stmt->execute();
    
    $summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get monthly totals
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(s.service_date, '%Y-%m') as month,
            COUNT(*) as total_payments,
            SUM(amount) as total_amount
        FROM instrumentalist_payments ip
        JOIN services s ON ip.service_id = s.id
        WHERE DATE_FORMAT(s.service_date, '%Y-%m') = ?
        GROUP BY DATE_FORMAT(s.service_date, '%Y-%m')
    ");
    $stmt->bind_param('s', $date_filter);
    $stmt->execute();
    $monthly = $stmt->get_result()->fetch_assoc();
    
    respond_json([
        'summary' => $summary,
        'monthly_total' => $monthly,
        'filter_date' => $date_filter
    ]);
}

function calculate_service_payments() {
    global $conn;
    
    $service_date = isset($_GET['service_date']) ? $_GET['service_date'] : date('Y-m-d');
    $service_type = isset($_GET['service_type']) ? $_GET['service_type'] : 'Sunday Morning';
    
    // Get or create service
    $service_id = find_or_create_service($conn, $service_date, $service_type);
    
    // Get all active instrumentalists
    $stmt = $conn->prepare("
        SELECT id, full_name, instrument, per_service_rate, hourly_rate
        FROM instrumentalists
        WHERE is_active = TRUE
        ORDER BY full_name
    ");
    $stmt->execute();
    $instrumentalists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $calculations = [];
    
    foreach ($instrumentalists as $instrumentalist) {
        // Check if payment already exists
        $stmt = $conn->prepare("
            SELECT id, amount, payment_status 
            FROM instrumentalist_payments 
            WHERE instrumentalist_id = ? AND service_id = ?
        ");
        $stmt->bind_param('ii', $instrumentalist['id'], $service_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        $calculated_amount = $instrumentalist['per_service_rate'] ?: 0;
        
        $calculations[] = [
            'instrumentalist_id' => $instrumentalist['id'],
            'full_name' => $instrumentalist['full_name'],
            'instrument' => $instrumentalist['instrument'],
            'per_service_rate' => $instrumentalist['per_service_rate'],
            'calculated_amount' => $calculated_amount,
            'existing_payment' => $existing,
            'can_create' => !$existing
        ];
    }
    
    respond_json([
        'service_date' => $service_date,
        'service_type' => $service_type,
        'service_id' => $service_id,
        'calculations' => $calculations
    ]);
}

function calculate_individual_payment() {
    $data = json_input();
    validate_required_fields($data, ['instrumentalist_id', 'service_id']);
    
    global $conn;
    
    $instrumentalist_id = (int)$data['instrumentalist_id'];
    $service_id = (int)$data['service_id'];
    $hours_worked = isset($data['hours_worked']) ? (float)$data['hours_worked'] : null;
    $payment_type = isset($data['payment_type']) ? $data['payment_type'] : 'Per Service';
    
    // Get instrumentalist rates
    $stmt = $conn->prepare("
        SELECT per_service_rate, hourly_rate, full_name, instrument
        FROM instrumentalists 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $instrumentalist_id);
    $stmt->execute();
    $instrumentalist = $stmt->get_result()->fetch_assoc();
    
    if (!$instrumentalist) {
        respond_error('Instrumentalist not found');
    }
    
    $calculated_amount = 0;
    
    switch ($payment_type) {
        case 'Per Service':
            $calculated_amount = $instrumentalist['per_service_rate'] ?: 0;
            break;
        case 'Hourly':
            if ($hours_worked && $instrumentalist['hourly_rate']) {
                $calculated_amount = $hours_worked * $instrumentalist['hourly_rate'];
            }
            break;
        case 'Fixed Amount':
            $calculated_amount = isset($data['fixed_amount']) ? (float)$data['fixed_amount'] : 0;
            break;
    }
    
    respond_json([
        'instrumentalist' => $instrumentalist,
        'payment_type' => $payment_type,
        'hours_worked' => $hours_worked,
        'calculated_amount' => $calculated_amount
    ]);
}

function create_payment() {
    $data = json_input();

    if (empty($data)) {
        respond_error('No data provided');
        return;
    }

    validate_required_fields($data, ['instrumentalist_id', 'service_id', 'amount']);

    global $conn;

    $instrumentalist_id = (int)$data['instrumentalist_id'];
    $service_id = (int)$data['service_id'];
    $amount = (float)$data['amount'];
    $payment_type = isset($data['payment_type']) ? sanitize_input($data['payment_type']) : 'Per Service';
    $hours_worked = isset($data['hours_worked']) ? (float)$data['hours_worked'] : null;
    $notes = isset($data['notes']) ? sanitize_input($data['notes']) : '';

    // Check if payment already exists for this instrumentalist and service
    $stmt = $conn->prepare("
        SELECT id FROM instrumentalist_payments
        WHERE instrumentalist_id = ? AND service_id = ?
    ");
    $stmt->bind_param('ii', $instrumentalist_id, $service_id);
    $stmt->execute();

    if ($stmt->get_result()->fetch_assoc()) {
        respond_error('Payment already exists for this instrumentalist and service');
    }

    // Create the payment
    $stmt = $conn->prepare("
        INSERT INTO instrumentalist_payments
        (instrumentalist_id, service_id, amount, payment_type, hours_worked, notes, payment_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->bind_param('iidsds', $instrumentalist_id, $service_id, $amount, $payment_type, $hours_worked, $notes);

    if ($stmt->execute()) {
        respond_json([
            'success' => true,
            'message' => 'Payment created successfully',
            'payment_id' => $stmt->insert_id
        ]);
    } else {
        respond_error('Failed to create payment');
    }
}

function approve_payment() {
    $data = json_input();
    validate_required_fields($data, ['payment_id']);
    
    global $conn;
    
    $payment_id = (int)$data['payment_id'];
    $approved_by = isset($data['approved_by']) ? sanitize_input($data['approved_by']) : 'Admin';
    $notes = isset($data['notes']) ? sanitize_input($data['notes']) : '';
    
    $stmt = $conn->prepare("
        UPDATE instrumentalist_payments 
        SET payment_status = 'Approved', 
            approved_by = ?, 
            approved_at = NOW(),
            notes = CONCAT(IFNULL(notes, ''), ?)
        WHERE id = ? AND payment_status = 'Pending'
    ");
    $note_addition = $notes ? " | Approved: $notes" : " | Approved";
    $stmt->bind_param('ssi', $approved_by, $note_addition, $payment_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        respond_json([
            'success' => true,
            'message' => 'Payment approved successfully'
        ]);
    } else {
        respond_error('Failed to approve payment or payment not found');
    }
}

function process_payment() {
    $data = json_input();
    validate_required_fields($data, ['payment_id', 'payment_method']);
    
    global $conn;
    
    $payment_id = (int)$data['payment_id'];
    $payment_method = sanitize_input($data['payment_method']);
    $reference_number = isset($data['reference_number']) ? sanitize_input($data['reference_number']) : '';
    $paid_by = isset($data['paid_by']) ? sanitize_input($data['paid_by']) : 'Admin';
    $payment_date = isset($data['payment_date']) ? sanitize_input($data['payment_date']) : date('Y-m-d');
    
    $stmt = $conn->prepare("
        UPDATE instrumentalist_payments 
        SET payment_status = 'Paid',
            payment_method = ?,
            reference_number = ?,
            payment_date = ?,
            paid_by = ?,
            paid_at = NOW()
        WHERE id = ? AND payment_status = 'Approved'
    ");
    $stmt->bind_param('ssssi', $payment_method, $reference_number, $payment_date, $paid_by, $payment_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        respond_json([
            'success' => true,
            'message' => 'Payment processed successfully'
        ]);
    } else {
        respond_error('Failed to process payment or payment not approved');
    }
}

function bulk_calculate_payments() {
    $data = json_input();
    validate_required_fields($data, ['service_date', 'service_type', 'instrumentalists']);
    
    global $conn;
    
    $service_date = sanitize_input($data['service_date']);
    $service_type = sanitize_input($data['service_type']);
    $instrumentalists = $data['instrumentalists'];
    
    $service_id = find_or_create_service($conn, $service_date, $service_type);
    
    $created_count = 0;
    $errors = [];
    
    $stmt = $conn->prepare("
        INSERT INTO instrumentalist_payments 
        (instrumentalist_id, service_id, amount, payment_type, hours_worked, notes) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($instrumentalists as $inst) {
        $instrumentalist_id = (int)$inst['instrumentalist_id'];
        $amount = (float)$inst['amount'];
        $payment_type = sanitize_input($inst['payment_type']);
        $hours_worked = isset($inst['hours_worked']) ? (float)$inst['hours_worked'] : null;
        $notes = isset($inst['notes']) ? sanitize_input($inst['notes']) : '';
        
        // Check if payment already exists
        $check_stmt = $conn->prepare("
            SELECT id FROM instrumentalist_payments 
            WHERE instrumentalist_id = ? AND service_id = ?
        ");
        $check_stmt->bind_param('ii', $instrumentalist_id, $service_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->fetch_assoc()) {
            $errors[] = "Payment already exists for instrumentalist ID: $instrumentalist_id";
            continue;
        }
        
        $stmt->bind_param('iidsds', $instrumentalist_id, $service_id, $amount, $payment_type, $hours_worked, $notes);
        
        if ($stmt->execute()) {
            $created_count++;
        } else {
            $errors[] = "Failed to create payment for instrumentalist ID: $instrumentalist_id";
        }
    }
    
    respond_json([
        'success' => true,
        'message' => "Created $created_count payments",
        'created_count' => $created_count,
        'errors' => $errors,
        'service_id' => $service_id
    ]);
}

function get_payment_batches() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT pb.*,
               COUNT(pbi.id) as payment_count,
               SUM(pbi.amount) as total_amount
        FROM payment_batches pb
        LEFT JOIN payment_batch_items pbi ON pb.id = pbi.batch_id
        GROUP BY pb.id
        ORDER BY pb.created_at DESC
    ");
    $stmt->execute();
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function get_batch_details($batch_id) {
    global $conn;

    // Get batch info
    $stmt = $conn->prepare("SELECT * FROM payment_batches WHERE id = ?");
    $stmt->bind_param('i', $batch_id);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();

    if (!$batch) {
        respond_error('Batch not found');
    }

    // Get batch items
    $stmt = $conn->prepare("
        SELECT pbi.*, ip.amount, ip.payment_status,
               i.full_name, i.instrument,
               s.service_date, s.service_type
        FROM payment_batch_items pbi
        JOIN instrumentalist_payments ip ON pbi.payment_id = ip.id
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE pbi.batch_id = ?
        ORDER BY i.full_name
    ");
    $stmt->bind_param('i', $batch_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    respond_json([
        'batch' => $batch,
        'items' => $items
    ]);
}

function create_payment_batch() {
    $data = json_input();
    validate_required_fields($data, ['batch_name', 'payment_ids']);

    global $conn;

    $batch_name = sanitize_input($data['batch_name']);
    $payment_ids = $data['payment_ids'];
    $created_by = isset($data['created_by']) ? sanitize_input($data['created_by']) : 'Admin';
    $notes = isset($data['notes']) ? sanitize_input($data['notes']) : '';

    if (!is_array($payment_ids) || empty($payment_ids)) {
        respond_error('No payments selected');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Calculate total amount
        $placeholders = str_repeat('?,', count($payment_ids) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT SUM(amount) as total_amount, COUNT(*) as payment_count
            FROM instrumentalist_payments
            WHERE id IN ($placeholders) AND payment_status = 'Approved'
        ");
        $stmt->bind_param(str_repeat('i', count($payment_ids)), ...$payment_ids);
        $stmt->execute();
        $totals = $stmt->get_result()->fetch_assoc();

        if ($totals['payment_count'] != count($payment_ids)) {
            throw new Exception('Some payments are not approved or do not exist');
        }

        // Create batch
        $stmt = $conn->prepare("
            INSERT INTO payment_batches (batch_name, batch_date, total_amount, payment_count, created_by, notes)
            VALUES (?, CURDATE(), ?, ?, ?, ?)
        ");
        $stmt->bind_param('sdiss', $batch_name, $totals['total_amount'], $totals['payment_count'], $created_by, $notes);
        $stmt->execute();
        $batch_id = $stmt->insert_id;

        // Add payments to batch
        $stmt = $conn->prepare("
            INSERT INTO payment_batch_items (batch_id, payment_id, amount)
            SELECT ?, id, amount FROM instrumentalist_payments WHERE id = ?
        ");

        foreach ($payment_ids as $payment_id) {
            $stmt->bind_param('ii', $batch_id, $payment_id);
            $stmt->execute();
        }

        $conn->commit();

        respond_json([
            'success' => true,
            'message' => 'Payment batch created successfully',
            'batch_id' => $batch_id,
            'total_amount' => $totals['total_amount'],
            'payment_count' => $totals['payment_count']
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        respond_error('Failed to create batch: ' . $e->getMessage());
    }
}

function approve_payment_batch() {
    $data = json_input();
    validate_required_fields($data, ['batch_id']);

    global $conn;

    $batch_id = (int)$data['batch_id'];
    $approved_by = isset($data['approved_by']) ? sanitize_input($data['approved_by']) : 'Admin';

    $stmt = $conn->prepare("
        UPDATE payment_batches
        SET batch_status = 'Approved', approved_by = ?, approved_at = NOW()
        WHERE id = ? AND batch_status = 'Draft'
    ");
    $stmt->bind_param('si', $approved_by, $batch_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        respond_json([
            'success' => true,
            'message' => 'Payment batch approved successfully'
        ]);
    } else {
        respond_error('Failed to approve batch or batch not found');
    }
}

function process_payment_batch() {
    $data = json_input();
    validate_required_fields($data, ['batch_id', 'payment_method']);

    global $conn;

    $batch_id = (int)$data['batch_id'];
    $payment_method = sanitize_input($data['payment_method']);
    $processed_by = isset($data['processed_by']) ? sanitize_input($data['processed_by']) : 'Admin';
    $payment_date = isset($data['payment_date']) ? sanitize_input($data['payment_date']) : date('Y-m-d');

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update batch status
        $stmt = $conn->prepare("
            UPDATE payment_batches
            SET batch_status = 'Completed', processed_by = ?, processed_at = NOW()
            WHERE id = ? AND batch_status = 'Approved'
        ");
        $stmt->bind_param('si', $processed_by, $batch_id);
        $stmt->execute();

        if ($stmt->affected_rows == 0) {
            throw new Exception('Batch not found or not approved');
        }

        // Update all payments in the batch
        $stmt = $conn->prepare("
            UPDATE instrumentalist_payments ip
            JOIN payment_batch_items pbi ON ip.id = pbi.payment_id
            SET ip.payment_status = 'Paid',
                ip.payment_method = ?,
                ip.payment_date = ?,
                ip.paid_by = ?,
                ip.paid_at = NOW()
            WHERE pbi.batch_id = ?
        ");
        $stmt->bind_param('sssi', $payment_method, $payment_date, $processed_by, $batch_id);
        $stmt->execute();

        $conn->commit();

        respond_json([
            'success' => true,
            'message' => 'Payment batch processed successfully',
            'payments_updated' => $stmt->affected_rows
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        respond_error('Failed to process batch: ' . $e->getMessage());
    }
}

function bulk_process_payments() {
    $data = json_input();
    validate_required_fields($data, ['payment_ids', 'payment_method']);

    global $conn;

    $payment_ids = $data['payment_ids'];
    $payment_method = sanitize_input($data['payment_method']);
    $processed_by = isset($data['processed_by']) ? sanitize_input($data['processed_by']) : 'Admin';
    $payment_date = isset($data['payment_date']) ? sanitize_input($data['payment_date']) : date('Y-m-d');
    $reference_number = isset($data['reference_number']) ? sanitize_input($data['reference_number']) : '';

    if (!is_array($payment_ids) || empty($payment_ids)) {
        respond_error('No payments selected');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        $processed_count = 0;
        $errors = [];

        $stmt = $conn->prepare("
            UPDATE instrumentalist_payments
            SET payment_status = 'Paid',
                payment_method = ?,
                reference_number = ?,
                payment_date = ?,
                paid_by = ?,
                paid_at = NOW()
            WHERE id = ? AND payment_status = 'Approved'
        ");

        foreach ($payment_ids as $payment_id) {
            $payment_id = (int)$payment_id;
            $stmt->bind_param('ssssi', $payment_method, $reference_number, $payment_date, $processed_by, $payment_id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $processed_count++;
            } else {
                $errors[] = "Failed to process payment ID: $payment_id";
            }
        }

        $conn->commit();

        respond_json([
            'success' => true,
            'message' => "Processed $processed_count payments successfully",
            'processed_count' => $processed_count,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        respond_error('Failed to process payments: ' . $e->getMessage());
    }
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
