<?php
require 'helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['instrumentalist_id'])) {
            get_instrumentalist_payments((int)$_GET['instrumentalist_id']);
        } elseif (isset($_GET['service_id'])) {
            get_service_payments((int)$_GET['service_id']);
        } else {
            get_all_payments();
        }
        break;
    
    case 'POST':
        save_payment();
        break;
    
    case 'PUT':
        update_payment();
        break;
    
    default:
        respond_error('Method not allowed', 405);
}

function get_all_payments() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument, s.service_date, s.service_type
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        ORDER BY s.service_date DESC, i.full_name
    ");
    $stmt->execute();
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function get_instrumentalist_payments($instrumentalist_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT ip.*, s.service_date, s.service_type
        FROM instrumentalist_payments ip
        JOIN services s ON ip.service_id = s.id
        WHERE ip.instrumentalist_id = ?
        ORDER BY s.service_date DESC
    ");
    $stmt->bind_param('i', $instrumentalist_id);
    $stmt->execute();
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function get_service_payments($service_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        WHERE ip.service_id = ?
        ORDER BY i.full_name
    ");
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function save_payment() {
    global $conn;
    $data = json_input();
    validate_required_fields($data, ['instrumentalist_id', 'service_date', 'service_type', 'amount']);
    
    $instrumentalist_id = (int)$data['instrumentalist_id'];
    $service_date = sanitize_input($data['service_date']);
    $service_type = sanitize_input($data['service_type']);
    $amount = (float)$data['amount'];
    $payment_type = isset($data['payment_type']) ? sanitize_input($data['payment_type']) : 'Per Service';
    $hours_worked = isset($data['hours_worked']) ? (float)$data['hours_worked'] : null;
    $payment_status = isset($data['payment_status']) ? sanitize_input($data['payment_status']) : 'Pending';
    $payment_date = isset($data['payment_date']) ? sanitize_input($data['payment_date']) : null;
    $payment_method = isset($data['payment_method']) ? sanitize_input($data['payment_method']) : '';
    $notes = isset($data['notes']) ? sanitize_input($data['notes']) : '';
    
    $service_id = find_or_create_service($conn, $service_date, $service_type);
    
    // Check if payment already exists
    $stmt = $conn->prepare("SELECT id FROM instrumentalist_payments WHERE instrumentalist_id = ? AND service_id = ?");
    $stmt->bind_param('ii', $instrumentalist_id, $service_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        respond_error('Payment already exists for this instrumentalist and service');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO instrumentalist_payments 
        (instrumentalist_id, service_id, amount, payment_type, hours_worked, payment_status, payment_date, payment_method, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('iidsdsss', $instrumentalist_id, $service_id, $amount, $payment_type, $hours_worked, $payment_status, $payment_date, $payment_method, $notes);
    
    if ($stmt->execute()) {
        respond_json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'payment_id' => $stmt->insert_id
        ]);
    } else {
        respond_error('Failed to record payment');
    }
    
    $stmt->close();
}

function update_payment() {
    global $conn;
    $data = json_input();
    validate_required_fields($data, ['id']);
    
    $id = (int)$data['id'];
    $fields = [];
    $values = [];
    $types = '';
    
    $allowed_fields = ['amount', 'payment_type', 'hours_worked', 'payment_status', 'payment_date', 'payment_method', 'notes'];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            if (in_array($field, ['amount', 'hours_worked'])) {
                $values[] = (float)$data[$field];
                $types .= 'd';
            } else {
                $values[] = sanitize_input($data[$field]);
                $types .= 's';
            }
        }
    }
    
    if (empty($fields)) {
        respond_error('No fields to update');
    }
    
    $values[] = $id;
    $types .= 'i';
    
    $sql = "UPDATE instrumentalist_payments SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'message' => 'Payment updated successfully']);
    } else {
        respond_error('Failed to update payment');
    }
    
    $stmt->close();
}
?>
