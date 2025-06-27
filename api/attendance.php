<?php
require 'helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['date'])) {
            get_attendance_by_date($_GET['date']);
        } elseif (isset($_GET['service_id'])) {
            get_service_attendance((int)$_GET['service_id']);
        } elseif (isset($_GET['member_id'])) {
            get_member_attendance((int)$_GET['member_id']);
        } else {
            get_recent_attendance();
        }
        break;
    
    case 'POST':
        save_attendance();
        break;
    
    default:
        respond_error('Method not allowed', 405);
}

function get_attendance_by_date($date) {
    global $conn;

    try {
        // Create tables if they don't exist
        $conn->query("CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_date DATE NOT NULL,
            service_type VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $conn->query("CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            service_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Build query with optional service type filter
        $sql = "
            SELECT a.*, m.full_name as member_name, s.service_type, s.service_date
            FROM attendance a
            JOIN members m ON a.member_id = m.id
            JOIN services s ON a.service_id = s.id
            WHERE s.service_date = ?
        ";

        $params = [$date];
        $types = 's';

        // Add service type filter if provided
        if (isset($_GET['service_type']) && !empty($_GET['service_type'])) {
            $sql .= " AND s.service_type = ?";
            $params[] = $_GET['service_type'];
            $types .= 's';
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        respond_json($result->fetch_all(MYSQLI_ASSOC));

    } catch (Exception $e) {
        respond_error('Error loading attendance: ' . $e->getMessage());
    }
}

function get_service_attendance($service_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT a.*, m.full_name as member_name, m.phone
        FROM attendance a
        JOIN members m ON a.member_id = m.id
        WHERE a.service_id = ?
        ORDER BY m.full_name
    ");
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function get_member_attendance($member_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT a.*, s.service_date, s.service_type
        FROM attendance a
        JOIN services s ON a.service_id = s.id
        WHERE a.member_id = ?
        ORDER BY s.service_date DESC
        LIMIT 50
    ");
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function get_recent_attendance() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT a.*, m.full_name as member_name, s.service_date, s.service_type
        FROM attendance a
        JOIN members m ON a.member_id = m.id
        JOIN services s ON a.service_id = s.id
        ORDER BY a.check_in_time DESC
        LIMIT 100
    ");
    $stmt->execute();
    
    respond_json($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function save_attendance() {
    global $conn;
    $data = json_input();
    validate_required_fields($data, ['service_date', 'service_type', 'attendees']);
    
    $service_date = sanitize_input($data['service_date']);
    $service_type = sanitize_input($data['service_type']);
    $attendees = $data['attendees'];
    $check_in_method = isset($data['check_in_method']) ? sanitize_input($data['check_in_method']) : 'Manual';
    
    if (!is_array($attendees) || empty($attendees)) {
        respond_error('No attendees provided');
    }
    
    $service_id = find_or_create_service($conn, $service_date, $service_type);
    
    $success_count = 0;
    $error_count = 0;
    
    $stmt = $conn->prepare("
        INSERT INTO attendance (member_id, service_id, present, check_in_method) 
        VALUES (?, ?, 1, ?)
        ON DUPLICATE KEY UPDATE 
            present = 1, 
            check_in_method = VALUES(check_in_method),
            check_in_time = CURRENT_TIMESTAMP
    ");
    
    foreach ($attendees as $member_id) {
        $member_id = (int)$member_id;
        
        // Verify member exists
        $check_stmt = $conn->prepare("SELECT id FROM members WHERE id = ? AND is_active = 1");
        $check_stmt->bind_param('i', $member_id);
        $check_stmt->execute();
        
        if (!$check_stmt->get_result()->fetch_assoc()) {
            $error_count++;
            continue;
        }
        
        $stmt->bind_param('iis', $member_id, $service_id, $check_in_method);
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $stmt->close();
    
    respond_json([
        'success' => true,
        'message' => "Attendance recorded for $success_count members",
        'success_count' => $success_count,
        'error_count' => $error_count,
        'service_id' => $service_id
    ]);
}

// Get attendance statistics
function get_attendance_stats() {
    global $conn;
    
    $today = date('Y-m-d');
    $this_week = date('Y-m-d', strtotime('monday this week'));
    $this_month = date('Y-m-01');
    
    // Today's attendance
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN services s ON a.service_id = s.id
        WHERE s.service_date = ?
    ");
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $today_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // This week's attendance
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN services s ON a.service_id = s.id
        WHERE s.service_date >= ?
    ");
    $stmt->bind_param('s', $this_week);
    $stmt->execute();
    $week_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // This month's attendance
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN services s ON a.service_id = s.id
        WHERE s.service_date >= ?
    ");
    $stmt->bind_param('s', $this_month);
    $stmt->execute();
    $month_count = $stmt->get_result()->fetch_assoc()['count'];
    
    respond_json([
        'today' => $today_count,
        'this_week' => $week_count,
        'this_month' => $month_count
    ]);
}

// Handle stats request
if (isset($_GET['stats'])) {
    get_attendance_stats();
    exit;
}
?>
