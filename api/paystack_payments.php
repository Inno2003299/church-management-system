<?php
/**
 * Paystack Payment Processing API
 * Handles MoMo payments to instrumentalists via Paystack
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);

try {
    require __DIR__ . '/helpers.php';
    require __DIR__ . '/../config/paystack.php';

    // Ensure we have database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'balance':
                    get_balance();
                    break;
                case 'verify_transfer':
                    verify_transfer($_GET['transfer_code'] ?? '');
                    break;
                case 'recipient_status':
                    check_recipient_status((int)$_GET['instrumentalist_id']);
                    break;
                default:
                    respond_error('Invalid action');
            }
        } else {
            respond_error('Action required');
        }
        break;
    
    case 'POST':
        if (isset($_POST['action']) || isset($_GET['action'])) {
            $action = $_POST['action'] ?? $_GET['action'];
            switch ($action) {
                case 'create_recipient':
                    create_recipient();
                    break;
                case 'process_payment':
                    process_payment();
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

} catch (Exception $e) {
    error_log("Paystack API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} catch (Error $e) {
    error_log("Paystack API Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Fatal error: ' . $e->getMessage()]);
}

/**
 * Get Paystack account balance
 */
function get_balance() {
    $balance_result = get_paystack_balance();
    
    if ($balance_result['success']) {
        respond_json([
            'success' => true,
            'balance' => $balance_result['balance'],
            'currency' => $balance_result['currency'] ?? 'GHS',
            'formatted_balance' => format_amount($balance_result['balance'])
        ]);
    } else {
        respond_error($balance_result['error']);
    }
}

/**
 * Create Paystack recipient for instrumentalist
 */
function create_recipient() {
    global $conn;
    $data = json_input();
    validate_required_fields($data, ['instrumentalist_id']);
    
    $instrumentalist_id = (int)$data['instrumentalist_id'];
    
    // Get instrumentalist details
    $stmt = $conn->prepare("
        SELECT id, full_name, momo_provider, momo_number, momo_name, paystack_recipient_code
        FROM instrumentalists 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->bind_param('i', $instrumentalist_id);
    $stmt->execute();
    $instrumentalist = $stmt->get_result()->fetch_assoc();
    
    if (!$instrumentalist) {
        respond_error('Instrumentalist not found');
        return;
    }
    
    // Check if recipient already exists
    if ($instrumentalist['paystack_recipient_code']) {
        respond_json([
            'success' => true,
            'message' => 'Recipient already exists',
            'recipient_code' => $instrumentalist['paystack_recipient_code']
        ]);
        return;
    }
    
    // Validate MoMo details
    if (!$instrumentalist['momo_provider'] || !$instrumentalist['momo_number'] || !$instrumentalist['momo_name']) {
        respond_error('Incomplete MoMo details. Please update instrumentalist profile.');
        return;
    }
    
    // Create recipient via Paystack
    $recipient_result = create_paystack_recipient($instrumentalist);
    
    if ($recipient_result['success']) {
        // Save recipient code to database
        $update_stmt = $conn->prepare("
            UPDATE instrumentalists 
            SET paystack_recipient_code = ? 
            WHERE id = ?
        ");
        $update_stmt->bind_param('si', $recipient_result['recipient_code'], $instrumentalist_id);
        
        if ($update_stmt->execute()) {
            respond_json([
                'success' => true,
                'message' => 'Recipient created successfully',
                'recipient_code' => $recipient_result['recipient_code']
            ]);
        } else {
            respond_error('Failed to save recipient code to database');
        }
    } else {
        respond_error($recipient_result['error']);
    }
}

/**
 * Process single payment via Paystack
 */
function process_payment() {
    global $conn;
    $data = json_input();

    // Log the incoming request for debugging
    error_log("Paystack process_payment called with data: " . json_encode($data));

    validate_required_fields($data, ['payment_id']);
    
    $payment_id = (int)$data['payment_id'];
    
    // Get payment and instrumentalist details
    $query = "
        SELECT ip.*, i.full_name, i.momo_provider, i.momo_number, i.momo_name,
               i.paystack_recipient_code, s.service_date, s.service_type
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE ip.id = ? AND ip.payment_status = 'Approved'
    ";

    error_log("Executing payment query for ID: $payment_id");
    error_log("Query: " . str_replace('?', $payment_id, $query));

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        respond_error('Database prepare error: ' . $conn->error);
        return;
    }

    $stmt->bind_param('i', $payment_id);
    if (!$stmt->execute()) {
        error_log("Failed to execute statement: " . $stmt->error);
        respond_error('Database execute error: ' . $stmt->error);
        return;
    }

    $payment = $stmt->get_result()->fetch_assoc();
    error_log("Payment query result: " . json_encode($payment));
    
    if (!$payment) {
        error_log("Payment not found - ID: $payment_id");
        respond_error('Payment not found or not approved');
        return;
    }

    error_log("Payment found: " . json_encode($payment));
    
    // Check if recipient exists, create if not
    if (!$payment['paystack_recipient_code']) {
        $recipient_result = create_paystack_recipient($payment);
        
        if (!$recipient_result['success']) {
            respond_error('Failed to create recipient: ' . $recipient_result['error']);
            return;
        }
        
        // Update instrumentalist with recipient code
        $update_stmt = $conn->prepare("
            UPDATE instrumentalists 
            SET paystack_recipient_code = ? 
            WHERE id = ?
        ");
        $update_stmt->bind_param('si', $recipient_result['recipient_code'], $payment['instrumentalist_id']);
        $update_stmt->execute();
        
        $payment['paystack_recipient_code'] = $recipient_result['recipient_code'];
    }
    
    // Initiate transfer
    $transfer_data = [
        'amount' => $payment['amount'],
        'recipient_code' => $payment['paystack_recipient_code'],
        'reason' => "Payment for {$payment['service_type']} on {$payment['service_date']}"
    ];
    
    $transfer_result = initiate_paystack_transfer($transfer_data);
    
    if ($transfer_result['success']) {
        // Update payment record
        $update_payment_stmt = $conn->prepare("
            UPDATE instrumentalist_payments 
            SET payment_status = 'Paid',
                payment_method = 'Paystack Transfer',
                paystack_transfer_code = ?,
                paystack_transfer_id = ?,
                paystack_status = ?,
                paystack_recipient_code = ?,
                payment_date = CURDATE(),
                paid_at = NOW(),
                paid_by = 'Paystack System'
            WHERE id = ?
        ");
        $update_payment_stmt->bind_param('ssssi', 
            $transfer_result['transfer_code'],
            $transfer_result['transfer_id'],
            $transfer_result['status'],
            $payment['paystack_recipient_code'],
            $payment_id
        );
        
        if ($update_payment_stmt->execute()) {
            respond_json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'transfer_code' => $transfer_result['transfer_code'],
                'amount' => format_amount($payment['amount'])
            ]);
        } else {
            respond_error('Transfer initiated but failed to update database');
        }
    } else {
        // Update payment status to failed
        $fail_stmt = $conn->prepare("
            UPDATE instrumentalist_payments 
            SET payment_status = 'Failed',
                paystack_failure_reason = ?
            WHERE id = ?
        ");
        $fail_stmt->bind_param('si', $transfer_result['error'], $payment_id);
        $fail_stmt->execute();
        
        respond_error('Transfer failed: ' . $transfer_result['error']);
    }
}

/**
 * Verify transfer status
 */
function verify_transfer($transfer_code) {
    if (!$transfer_code) {
        respond_error('Transfer code required');
        return;
    }
    
    $verify_result = verify_paystack_transfer($transfer_code);
    
    if ($verify_result['success']) {
        respond_json([
            'success' => true,
            'status' => $verify_result['status'],
            'failure_reason' => $verify_result['failure_reason'],
            'amount' => format_amount($verify_result['amount'])
        ]);
    } else {
        respond_error($verify_result['error']);
    }
}

/**
 * Check recipient status for instrumentalist
 */
function check_recipient_status($instrumentalist_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT paystack_recipient_code, momo_provider, momo_number, momo_name
        FROM instrumentalists 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $instrumentalist_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        respond_error('Instrumentalist not found');
        return;
    }
    
    $has_momo_details = $result['momo_provider'] && $result['momo_number'] && $result['momo_name'];
    $has_recipient = !empty($result['paystack_recipient_code']);
    
    respond_json([
        'success' => true,
        'has_momo_details' => $has_momo_details,
        'has_recipient' => $has_recipient,
        'recipient_code' => $result['paystack_recipient_code'],
        'ready_for_payment' => $has_momo_details && $has_recipient
    ]);
}
?>
