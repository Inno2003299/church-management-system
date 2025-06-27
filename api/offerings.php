<?php
require 'helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['service_id'])) {
            get_service_offerings((int)$_GET['service_id']);
        } elseif (isset($_GET['date'])) {
            get_offerings_by_date($_GET['date']);
        } else {
            get_offering_types();
        }
        break;
    
    case 'POST':
        save_offering();
        break;
    
    default:
        respond_error('Method not allowed', 405);
}

function get_offering_types() {
    global $conn;

    // Check if offering_types table exists, create if it doesn't
    $table_check = $conn->query("SHOW TABLES LIKE 'offering_types'");
    if ($table_check->num_rows == 0) {
        $create_table = "CREATE TABLE offering_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if (!$conn->query($create_table)) {
            respond_error('Failed to create offering_types table: ' . $conn->error);
            return;
        }

        // Insert default offering types
        $default_types = [
            ['Tithe', 'Regular tithe offerings'],
            ['Thanksgiving', 'Thanksgiving offerings'],
            ['Seed Offering', 'Seed/faith offerings'],
            ['Building Fund', 'Church building fund'],
            ['Mission', 'Mission and outreach'],
            ['Special Collection', 'Special collections and events']
        ];

        foreach ($default_types as $type) {
            $stmt = $conn->prepare("INSERT INTO offering_types (name, description) VALUES (?, ?)");
            $stmt->bind_param('ss', $type[0], $type[1]);
            $stmt->execute();
            $stmt->close();
        }
    }

    $result = $conn->query("SELECT * FROM offering_types WHERE is_active = 1 ORDER BY name");
    if ($result === false) {
        respond_error('Query failed: ' . $conn->error);
        return;
    }

    respond_json($result->fetch_all(MYSQLI_ASSOC));
}

function get_service_offerings($service_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT o.*, ot.name as offering_type_name, s.service_date, s.service_type
        FROM offerings o
        JOIN offering_types ot ON o.offering_type_id = ot.id
        JOIN services s ON o.service_id = s.id
        WHERE o.service_id = ?
        ORDER BY ot.name
    ");
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function get_offerings_by_date($date) {
    global $conn;

    // Check if offerings table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'offerings'");
    if ($table_check->num_rows == 0) {
        // Return empty result for missing table
        respond_json([]);
        return;
    }

    // Check if services table exists
    $services_check = $conn->query("SHOW TABLES LIKE 'services'");
    if ($services_check->num_rows == 0) {
        // Return empty result for missing table
        respond_json([]);
        return;
    }

    $stmt = $conn->prepare("
        SELECT o.*, ot.name as offering_type_name, s.service_type,
               SUM(o.amount) as total_amount
        FROM offerings o
        JOIN offering_types ot ON o.offering_type_id = ot.id
        JOIN services s ON o.service_id = s.id
        WHERE s.service_date = ?
        GROUP BY o.offering_type_id, s.service_type
        ORDER BY s.service_type, ot.name
    ");

    if (!$stmt) {
        respond_error('Failed to prepare statement: ' . $conn->error);
        return;
    }

    $stmt->bind_param('s', $date);
    if (!$stmt->execute()) {
        respond_error('Query execution failed: ' . $stmt->error);
        return;
    }

    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function save_offering() {
    global $conn;
    $data = json_input();
    validate_required_fields($data, ['service_date', 'service_type', 'offering_type_id', 'amount']);
    
    $service_date = sanitize_input($data['service_date']);
    $service_type = sanitize_input($data['service_type']);
    $service_id = find_or_create_service($conn, $service_date, $service_type);
    $offering_type_id = (int)$data['offering_type_id'];
    $amount = (float)$data['amount'];
    $notes = isset($data['notes']) ? sanitize_input($data['notes']) : '';
    $recorded_by = isset($data['recorded_by']) ? sanitize_input($data['recorded_by']) : 'Admin';
    
    // Check if offering already exists for this service and type
    $stmt = $conn->prepare("SELECT id, amount FROM offerings WHERE service_id = ? AND offering_type_id = ?");
    $stmt->bind_param('ii', $service_id, $offering_type_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update existing offering
        $existing_amount = (float)$existing['amount'];
        $existing_id = (int)$existing['id'];
        $new_amount = $existing_amount + $amount;

        $update_note = '';
        if ($notes) {
            $update_note = " | Additional: " . $notes;
        } else {
            $update_note = " | Additional amount added";
        }

        $stmt = $conn->prepare("UPDATE offerings SET amount = ?, notes = CONCAT(IFNULL(notes, ''), ?), updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('dsi', $new_amount, $update_note, $existing_id);
        $stmt->execute();

        respond_json([
            'success' => true,
            'message' => 'Offering updated successfully',
            'total_amount' => $new_amount
        ]);
    } else {
        // Insert new offering
        $stmt = $conn->prepare("INSERT INTO offerings (service_id, offering_type_id, amount, notes, recorded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iidss', $service_id, $offering_type_id, $amount, $notes, $recorded_by);

        if ($stmt->execute()) {
            respond_json([
                'success' => true,
                'message' => 'Offering recorded successfully',
                'offering_id' => $stmt->insert_id
            ]);
        } else {
            respond_error('Failed to record offering');
        }
    }
    
    $stmt->close();
}
?>
