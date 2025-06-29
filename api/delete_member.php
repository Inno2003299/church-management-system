<?php
/**
 * Delete Member API
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once __DIR__ . '/../config/db.php';

    // Check database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (!isset($input['id'])) {
        throw new Exception('Member ID is required');
    }
    
    $id = (int)$input['id'];
    
    if ($id <= 0) {
        throw new Exception('Invalid member ID');
    }
    
    // Check if member exists and get their name
    $check_stmt = $conn->prepare("SELECT full_name FROM members WHERE id = ?");
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $member = $check_stmt->get_result()->fetch_assoc();
    
    if (!$member) {
        throw new Exception('Member not found');
    }
    
    // Check if member has attendance records (if table exists)
    $attendance_count = 0;
    $attendance_check = $conn->query("SHOW TABLES LIKE 'attendance'");
    if ($attendance_check && $attendance_check->num_rows > 0) {
        $attendance_stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE member_id = ?");
        if ($attendance_stmt) {
            $attendance_stmt->bind_param('i', $id);
            $attendance_stmt->execute();
            $attendance_count = $attendance_stmt->get_result()->fetch_assoc()['count'];
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete member's fingerprint data if table exists
        $fingerprint_check = $conn->query("SHOW TABLES LIKE 'fingerprint_data'");
        if ($fingerprint_check && $fingerprint_check->num_rows > 0) {
            $fingerprint_stmt = $conn->prepare("DELETE FROM fingerprint_data WHERE member_id = ?");
            if ($fingerprint_stmt) {
                $fingerprint_stmt->bind_param('i', $id);
                $fingerprint_stmt->execute();
            }
        }

        // Delete attendance records if table exists (optional - you might want to keep these)
        if ($attendance_check && $attendance_check->num_rows > 0) {
            $delete_attendance_stmt = $conn->prepare("DELETE FROM attendance WHERE member_id = ?");
            if ($delete_attendance_stmt) {
                $delete_attendance_stmt->bind_param('i', $id);
                $delete_attendance_stmt->execute();
            }
        }
        
        // Delete the member
        $delete_stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
        if (!$delete_stmt) {
            throw new Exception('Failed to prepare delete statement: ' . $conn->error);
        }

        $delete_stmt->bind_param('i', $id);

        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                // Commit transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Member '{$member['full_name']}' deleted successfully",
                    'deleted_records' => [
                        'member' => 1,
                        'attendance_records' => $attendance_count,
                        'fingerprint_data' => $fingerprint_stmt->affected_rows
                    ]
                ]);
            } else {
                throw new Exception('No member was deleted');
            }
        } else {
            throw new Exception('Failed to delete member: ' . $delete_stmt->error);
        }
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete Member API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("Delete Member API Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage()
    ]);
}
?>
