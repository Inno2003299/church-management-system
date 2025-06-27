<?php
// Authentication API
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check':
        check_auth();
        break;
    case 'logout':
        logout();
        break;
    case 'profile':
        get_profile();
        break;
    default:
        respond_error('Invalid action', 400);
}

function check_auth() {
    if (!isset($_SESSION['admin_id'])) {
        respond_json(['authenticated' => false, 'message' => 'Not logged in']);
        return;
    }
    
    global $conn;
    
    try {
        // Verify admin still exists and is active
        $stmt = $conn->prepare("SELECT id, email, full_name, role, is_active, last_login FROM admin_users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if ($admin['is_active']) {
                respond_json([
                    'authenticated' => true,
                    'admin' => [
                        'id' => $admin['id'],
                        'email' => $admin['email'],
                        'name' => $admin['full_name'],
                        'role' => $admin['role'],
                        'last_login' => $admin['last_login']
                    ]
                ]);
            } else {
                // Account deactivated
                session_destroy();
                respond_json(['authenticated' => false, 'message' => 'Account deactivated']);
            }
        } else {
            // Admin not found
            session_destroy();
            respond_json(['authenticated' => false, 'message' => 'Admin not found']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        respond_json(['authenticated' => false, 'message' => 'Authentication error']);
    }
}

function logout() {
    global $conn;
    
    // Remove session token from database
    if (isset($_SESSION['session_token'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE session_token = ?");
            $stmt->bind_param('s', $_SESSION['session_token']);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Log error but continue with logout
            error_log('Error removing session token: ' . $e->getMessage());
        }
    }
    
    // Destroy session
    session_destroy();
    
    respond_json(['success' => true, 'message' => 'Logged out successfully']);
}

function get_profile() {
    if (!isset($_SESSION['admin_id'])) {
        respond_error('Not authenticated', 401);
        return;
    }
    
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT id, email, full_name, role, last_login, created_at,
                   (SELECT COUNT(*) FROM admin_sessions WHERE admin_id = admin_users.id) as active_sessions
            FROM admin_users 
            WHERE id = ?
        ");
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            respond_json($admin);
        } else {
            respond_error('Admin not found', 404);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        respond_error('Error fetching profile', 500);
    }
}

function respond_json($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

function respond_error($message, $status_code = 400) {
    respond_json(['error' => $message], $status_code);
}
?>
