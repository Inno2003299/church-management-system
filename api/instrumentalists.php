<?php
require 'helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            get_instrumentalist((int)$_GET['id']);
        } else {
            get_instrumentalists();
        }
        break;
    
    case 'POST':
        save_instrumentalist();
        break;
    
    case 'PUT':
        update_instrumentalist();
        break;

    case 'DELETE':
        delete_instrumentalist();
        break;

    default:
        respond_error('Method not allowed', 405);
}

function get_instrumentalists() {
    global $conn;

    // Check if table exists, create if it doesn't
    $table_check = $conn->query("SHOW TABLES LIKE 'instrumentalists'");
    if ($table_check->num_rows == 0) {
        $create_table = "CREATE TABLE instrumentalists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            instrument VARCHAR(100),
            skill_level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Intermediate',
            hourly_rate DECIMAL(10,2),
            per_service_rate DECIMAL(10,2),
            notes TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if (!$conn->query($create_table)) {
            respond_error('Failed to create instrumentalists table: ' . $conn->error);
            return;
        }
    }

    // Check what columns exist
    $columns_result = $conn->query("SHOW COLUMNS FROM instrumentalists");
    $existing_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    $active_only = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : false;

    $sql = "SELECT * FROM instrumentalists";

    // Only add WHERE clause if is_active column exists
    if ($active_only && in_array('is_active', $existing_columns)) {
        $sql .= " WHERE is_active = 1";
    }

    $sql .= " ORDER BY " . (in_array('full_name', $existing_columns) ? 'full_name' : 'id');

    $result = $conn->query($sql);
    if ($result === false) {
        respond_error('Query failed: ' . $conn->error);
        return;
    }

    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Add debug info
    error_log("Instrumentalists query returned " . count($data) . " rows");

    respond_json($data);
}

function get_instrumentalist($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM instrumentalists WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        respond_json($result);
    } else {
        respond_error('Instrumentalist not found', 404);
    }
}

function save_instrumentalist() {
    global $conn;

    // Ensure table exists first
    $table_check = $conn->query("SHOW TABLES LIKE 'instrumentalists'");
    if ($table_check->num_rows == 0) {
        $create_table = "CREATE TABLE instrumentalists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            instrument VARCHAR(100),
            skill_level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Intermediate',
            hourly_rate DECIMAL(10,2),
            per_service_rate DECIMAL(10,2),
            notes TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if (!$conn->query($create_table)) {
            respond_error('Failed to create instrumentalists table: ' . $conn->error);
            return;
        }
    }

    $data = json_input();
    if (!$data) {
        respond_error('Invalid JSON data');
        return;
    }

    validate_required_fields($data, ['full_name', 'instrument']);

    $full_name = sanitize_input($data['full_name']);
    $phone = isset($data['phone']) ? sanitize_input($data['phone']) : '';
    $email = isset($data['email']) ? sanitize_input($data['email']) : '';
    $instrument = sanitize_input($data['instrument']);
    $skill_level = isset($data['skill_level']) ? sanitize_input($data['skill_level']) : 'Intermediate';
    $hourly_rate = isset($data['hourly_rate']) ? (float)$data['hourly_rate'] : null;
    $per_service_rate = isset($data['per_service_rate']) ? (float)$data['per_service_rate'] : null;
    $notes = isset($data['notes']) ? sanitize_input($data['notes']) : '';

    // MoMo and payment fields
    $preferred_payment_method = isset($data['preferred_payment_method']) ? sanitize_input($data['preferred_payment_method']) : 'Mobile Money';
    $momo_provider = isset($data['momo_provider']) ? sanitize_input($data['momo_provider']) : null;
    $momo_number = isset($data['momo_number']) ? sanitize_input($data['momo_number']) : null;
    $momo_name = isset($data['momo_name']) ? sanitize_input($data['momo_name']) : null;
    $bank_name = isset($data['bank_name']) ? sanitize_input($data['bank_name']) : null;
    $bank_account_number = isset($data['bank_account_number']) ? sanitize_input($data['bank_account_number']) : null;
    $bank_account_name = isset($data['bank_account_name']) ? sanitize_input($data['bank_account_name']) : null;

    // Check what columns actually exist in the table
    $columns_result = $conn->query("SHOW COLUMNS FROM instrumentalists");
    $existing_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    // Build INSERT statement based on existing columns
    $insert_columns = ['full_name', 'instrument']; // Required columns
    $insert_values = [$full_name, $instrument];
    $param_types = 'ss';

    // Add optional columns if they exist
    if (in_array('phone', $existing_columns) && $phone) {
        $insert_columns[] = 'phone';
        $insert_values[] = $phone;
        $param_types .= 's';
    }

    if (in_array('email', $existing_columns) && $email) {
        $insert_columns[] = 'email';
        $insert_values[] = $email;
        $param_types .= 's';
    }

    if (in_array('skill_level', $existing_columns)) {
        $insert_columns[] = 'skill_level';
        $insert_values[] = $skill_level;
        $param_types .= 's';
    }

    if (in_array('hourly_rate', $existing_columns) && $hourly_rate !== null) {
        $insert_columns[] = 'hourly_rate';
        $insert_values[] = $hourly_rate;
        $param_types .= 'd';
    }

    if (in_array('per_service_rate', $existing_columns) && $per_service_rate !== null) {
        $insert_columns[] = 'per_service_rate';
        $insert_values[] = $per_service_rate;
        $param_types .= 'd';
    }

    if (in_array('notes', $existing_columns) && $notes) {
        $insert_columns[] = 'notes';
        $insert_values[] = $notes;
        $param_types .= 's';
    }

    // Add MoMo and payment fields if they exist in the table
    if (in_array('preferred_payment_method', $existing_columns) && $preferred_payment_method) {
        $insert_columns[] = 'preferred_payment_method';
        $insert_values[] = $preferred_payment_method;
        $param_types .= 's';
    }

    if (in_array('momo_provider', $existing_columns) && $momo_provider) {
        $insert_columns[] = 'momo_provider';
        $insert_values[] = $momo_provider;
        $param_types .= 's';
    }

    if (in_array('momo_number', $existing_columns) && $momo_number) {
        $insert_columns[] = 'momo_number';
        $insert_values[] = $momo_number;
        $param_types .= 's';
    }

    if (in_array('momo_name', $existing_columns) && $momo_name) {
        $insert_columns[] = 'momo_name';
        $insert_values[] = $momo_name;
        $param_types .= 's';
    }

    if (in_array('bank_name', $existing_columns) && $bank_name) {
        $insert_columns[] = 'bank_name';
        $insert_values[] = $bank_name;
        $param_types .= 's';
    }

    if (in_array('bank_account_number', $existing_columns) && $bank_account_number) {
        $insert_columns[] = 'bank_account_number';
        $insert_values[] = $bank_account_number;
        $param_types .= 's';
    }

    if (in_array('bank_account_name', $existing_columns) && $bank_account_name) {
        $insert_columns[] = 'bank_account_name';
        $insert_values[] = $bank_account_name;
        $param_types .= 's';
    }

    $sql = "INSERT INTO instrumentalists (" . implode(', ', $insert_columns) . ") VALUES (" . str_repeat('?,', count($insert_columns) - 1) . "?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        respond_error('Failed to prepare statement: ' . $conn->error);
        return;
    }

    $stmt->bind_param($param_types, ...$insert_values);

    if ($stmt->execute()) {
        respond_json([
            'success' => true,
            'message' => 'Instrumentalist added successfully',
            'instrumentalist_id' => $stmt->insert_id
        ]);
    } else {
        respond_error('Failed to add instrumentalist: ' . $stmt->error);
    }

    $stmt->close();
}

function update_instrumentalist() {
    global $conn;
    $data = json_input();

    // Custom validation for ID (don't use validate_required_fields for ID)
    if (!isset($data['id']) || $data['id'] === null || $data['id'] === '') {
        respond_error("Missing required field: id");
        return;
    }

    $id = (int)$data['id'];

    // Check what columns actually exist in the table
    $columns_result = $conn->query("SHOW COLUMNS FROM instrumentalists");
    $existing_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    $fields = [];
    $values = [];
    $types = '';

    $allowed_fields = ['full_name', 'phone', 'email', 'instrument', 'skill_level', 'hourly_rate', 'per_service_rate', 'notes', 'is_active'];

    foreach ($allowed_fields as $field) {
        // Only process fields that exist in the table AND are provided in the data
        if (in_array($field, $existing_columns) && array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            if (in_array($field, ['hourly_rate', 'per_service_rate'])) {
                // Handle empty rate fields as NULL
                $value = $data[$field];
                if ($value === '' || $value === null) {
                    $values[] = null;
                } else {
                    $values[] = (float)$value;
                }
                $types .= 'd';
            } elseif ($field === 'is_active') {
                $values[] = (bool)$data[$field];
                $types .= 'i';
            } else {
                $values[] = sanitize_input($data[$field]);
                $types .= 's';
            }
        }
    }

    if (empty($fields)) {
        respond_error('No fields to update');
        return;
    }

    $values[] = $id;
    $types .= 'i';

    $sql = "UPDATE instrumentalists SET " . implode(', ', $fields) . " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond_error('Failed to prepare statement: ' . $conn->error);
        return;
    }

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            respond_json(['success' => true, 'message' => 'Instrumentalist updated successfully']);
        } else {
            respond_error('No instrumentalist found with that ID or no changes made');
        }
    } else {
        respond_error('Failed to update instrumentalist: ' . $stmt->error);
    }
    
    $stmt->close();
}

function delete_instrumentalist() {
    global $conn;

    // Get ID from query parameter for DELETE requests
    if (!isset($_GET['id'])) {
        respond_error('Instrumentalist ID is required');
        return;
    }

    $id = (int)$_GET['id'];

    // Check if instrumentalist exists
    $check_stmt = $conn->prepare("SELECT full_name FROM instrumentalists WHERE id = ?");
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        respond_error('Instrumentalist not found', 404);
        return;
    }

    $instrumentalist = $result->fetch_assoc();
    $check_stmt->close();

    // Delete the instrumentalist
    $delete_stmt = $conn->prepare("DELETE FROM instrumentalists WHERE id = ?");
    $delete_stmt->bind_param('i', $id);

    if ($delete_stmt->execute()) {
        respond_json([
            'success' => true,
            'message' => "Instrumentalist '{$instrumentalist['full_name']}' deleted successfully"
        ]);
    } else {
        respond_error('Failed to delete instrumentalist: ' . $delete_stmt->error);
    }

    $delete_stmt->close();
}
?>
