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

try {
    require_once '../config/db.php';

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                get_service((int)$_GET['id']);
            } elseif (isset($_GET['date'])) {
                get_services_by_date($_GET['date']);
            } else {
                get_all_services();
            }
            break;

        case 'POST':
            create_service();
            break;

        case 'PUT':
            update_service();
            break;

        case 'DELETE':
            delete_service();
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function get_all_services() {
    global $conn;

    try {
        // Get services from the last 30 days and future services
        $stmt = $conn->prepare("
            SELECT id, service_date, service_type, created_at
            FROM services
            WHERE service_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY service_date DESC, service_type
            LIMIT 50
        ");

        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            return;
        }

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['error' => 'Database execute error: ' . $stmt->error]);
            return;
        }

        $result = $stmt->get_result();
        $services = [];

        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }

        // If no services found, create a default service for today
        if (empty($services)) {
            $today = date('Y-m-d');
            $create_stmt = $conn->prepare("
                INSERT IGNORE INTO services (service_date, service_type, created_at)
                VALUES (?, 'Sunday Morning', NOW())
            ");
            if ($create_stmt) {
                $create_stmt->bind_param('s', $today);
                $create_stmt->execute();

                // Try to get services again
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $services[] = $row;
                }
            }
        }

        echo json_encode($services);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error loading services: ' . $e->getMessage()]);
    }
}

function get_service($id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, service_date, service_type, created_at
        FROM services 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($service = $result->fetch_assoc()) {
        respond_json($service);
    } else {
        respond_error('Service not found', 404);
    }
}

function get_services_by_date($date) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, service_date, service_type, created_at
        FROM services 
        WHERE service_date = ?
        ORDER BY service_type
    ");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    respond_json($services);
}

function create_service() {
    global $conn;
    $data = json_input();

    if (empty($data)) {
        respond_error('No data provided');
        return;
    }

    validate_required_fields($data, ['service_date', 'service_type']);
    
    $service_date = sanitize_input($data['service_date']);
    $service_type = sanitize_input($data['service_type']);
    
    // Check if service already exists
    $stmt = $conn->prepare("
        SELECT id FROM services 
        WHERE service_date = ? AND service_type = ?
    ");
    $stmt->bind_param('ss', $service_date, $service_type);
    $stmt->execute();
    
    if ($stmt->get_result()->fetch_assoc()) {
        respond_error('Service already exists for this date and type');
    }
    
    // Create new service
    $stmt = $conn->prepare("
        INSERT INTO services (service_date, service_type, created_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param('ss', $service_date, $service_type);
    
    if ($stmt->execute()) {
        respond_json([
            'success' => true,
            'message' => 'Service created successfully',
            'service_id' => $stmt->insert_id
        ]);
    } else {
        respond_error('Failed to create service');
    }
}

function update_service() {
    global $conn;
    $data = json_input();
    
    validate_required_fields($data, ['id']);
    
    $id = (int)$data['id'];
    $fields = [];
    $values = [];
    $types = '';
    
    $allowed_fields = ['service_date', 'service_type'];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = sanitize_input($data[$field]);
            $types .= 's';
        }
    }
    
    if (empty($fields)) {
        respond_error('No fields to update');
    }
    
    $values[] = $id;
    $types .= 'i';
    
    $sql = "UPDATE services SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'message' => 'Service updated successfully']);
    } else {
        respond_error('Failed to update service');
    }
}

function delete_service() {
    global $conn;
    $data = json_input();
    
    validate_required_fields($data, ['id']);
    
    $id = (int)$data['id'];
    
    // Check if service has associated data
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM attendance WHERE service_id = ?) as attendance_count,
            (SELECT COUNT(*) FROM offerings WHERE service_id = ?) as offerings_count,
            (SELECT COUNT(*) FROM instrumentalist_payments WHERE service_id = ?) as payments_count
    ");
    $stmt->bind_param('iii', $id, $id, $id);
    $stmt->execute();
    $counts = $stmt->get_result()->fetch_assoc();
    
    if ($counts['attendance_count'] > 0 || $counts['offerings_count'] > 0 || $counts['payments_count'] > 0) {
        respond_error('Cannot delete service with associated attendance, offerings, or payments');
    }
    
    // Delete service
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'message' => 'Service deleted successfully']);
    } else {
        respond_error('Failed to delete service');
    }
}
?>
