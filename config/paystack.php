<?php
/**
 * Paystack Configuration for Church Management System
 * 
 * This file contains Paystack API configuration and helper functions
 * for processing payments to instrumentalists via Mobile Money
 */

// Paystack Configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_64b8468b3815193458e76fbf3e2a66ca4ee58134'); // Your actual secret key
define('PAYSTACK_PUBLIC_KEY', 'pk_test_d750f30e2326a546471915aa442a256939219f95'); // Your actual public key
define('PAYSTACK_BASE_URL', 'https://api.paystack.co');

// Test mode configuration - set to false for real transfers
define('PAYSTACK_TEST_MODE', false);

/**
 * Make HTTP request to Paystack API
 */
function paystack_request($endpoint, $data = null, $method = 'GET') {
    $url = PAYSTACK_BASE_URL . $endpoint;
    
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
        'Cache-Control: no-cache'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    $decoded_response = json_decode($response, true);
    
    return [
        'status_code' => $http_code,
        'data' => $decoded_response,
        'success' => $http_code >= 200 && $http_code < 300
    ];
}

/**
 * Create a transfer recipient for an instrumentalist
 */
function create_paystack_recipient($instrumentalist) {
    $recipient_data = [
        'type' => 'mobile_money',
        'name' => $instrumentalist['momo_name'],
        'account_number' => $instrumentalist['momo_number'],
        'bank_code' => get_momo_bank_code($instrumentalist['momo_provider']),
        'currency' => 'GHS'
    ];
    
    try {
        $response = paystack_request('/transferrecipient', $recipient_data, 'POST');
        
        if ($response['success'] && $response['data']['status']) {
            return [
                'success' => true,
                'recipient_code' => $response['data']['data']['recipient_code'],
                'message' => 'Recipient created successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['data']['message'] ?? 'Failed to create recipient'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'API Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Get Paystack bank code for MoMo providers
 */
function get_momo_bank_code($provider) {
    $codes = [
        'MTN' => 'MTN',
        'Vodafone' => 'VOD',
        'AirtelTigo' => 'ATL',
        'Other' => 'MTN' // Default to MTN
    ];
    
    return $codes[$provider] ?? 'MTN';
}

/**
 * Initiate transfer to instrumentalist
 */
function initiate_paystack_transfer($payment_data) {
    $transfer_data = [
        'source' => 'balance',
        'amount' => $payment_data['amount'] * 100, // Convert to pesewas
        'recipient' => $payment_data['recipient_code'],
        'reason' => $payment_data['reason'] ?? 'Instrumentalist payment'
    ];
    
    try {
        $response = paystack_request('/transfer', $transfer_data, 'POST');
        
        if ($response['success'] && $response['data']['status']) {
            return [
                'success' => true,
                'transfer_code' => $response['data']['data']['transfer_code'],
                'transfer_id' => $response['data']['data']['id'],
                'status' => $response['data']['data']['status'],
                'message' => 'Transfer initiated successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['data']['message'] ?? 'Failed to initiate transfer'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'API Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Verify transfer status
 */
function verify_paystack_transfer($transfer_code) {
    try {
        $response = paystack_request('/transfer/verify/' . $transfer_code);
        
        if ($response['success'] && $response['data']['status']) {
            return [
                'success' => true,
                'status' => $response['data']['data']['status'],
                'failure_reason' => $response['data']['data']['failure_reason'] ?? null,
                'transfer_code' => $response['data']['data']['transfer_code'],
                'amount' => $response['data']['data']['amount'] / 100, // Convert from pesewas
                'message' => 'Transfer status retrieved successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['data']['message'] ?? 'Failed to verify transfer'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'API Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Get Paystack balance
 */
function get_paystack_balance() {
    // If in test mode, use mock balance that decreases with payments
    if (defined('PAYSTACK_TEST_MODE') && PAYSTACK_TEST_MODE === true) {
        return get_mock_balance();
    }

    try {
        $response = paystack_request('/balance');

        if ($response['success'] && $response['data']['status']) {
            return [
                'success' => true,
                'balance' => $response['data']['data'][0]['balance'] / 100, // Convert from pesewas
                'currency' => $response['data']['data'][0]['currency'],
                'formatted_balance' => 'GH₵' . number_format($response['data']['data'][0]['balance'] / 100, 2)
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['data']['message'] ?? 'Failed to get balance'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'API Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Get mock balance that decreases with test payments
 */
function get_mock_balance() {
    require_once __DIR__ . '/db.php';
    global $conn;

    // Starting mock balance
    $starting_balance = 100.00; // GH₵100

    // Calculate total test payments made
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_paid
        FROM instrumentalist_payments
        WHERE payment_status = 'Paid'
        AND payment_method = 'Paystack Transfer'
    ");

    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result()->fetch_assoc();
        $total_paid = (float)$result['total_paid'];

        // Calculate remaining balance
        $current_balance = $starting_balance - $total_paid;

        // Ensure balance doesn't go negative
        $current_balance = max(0, $current_balance);

        return [
            'success' => true,
            'balance' => $current_balance,
            'formatted_balance' => 'GH₵' . number_format($current_balance, 2),
            'currency' => 'GHS',
            'test_mode' => true,
            'starting_balance' => $starting_balance,
            'total_paid' => $total_paid
        ];
    }

    return [
        'success' => false,
        'error' => 'Failed to calculate mock balance'
    ];
}

/**
 * Format amount for display
 */
function format_amount($amount, $currency = 'GHS') {
    return $currency . ' ' . number_format($amount, 2);
}
?>
