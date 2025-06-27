<?php
require 'helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'receipt':
                    generate_payment_receipt((int)$_GET['payment_id']);
                    break;
                case 'batch_receipt':
                    generate_batch_receipt((int)$_GET['batch_id']);
                    break;
                case 'monthly_report':
                    generate_monthly_report($_GET['month'] ?? date('Y-m'));
                    break;
                case 'instrumentalist_report':
                    generate_instrumentalist_report((int)$_GET['instrumentalist_id'], $_GET['period'] ?? 'month');
                    break;
                case 'export_payments':
                    export_payments_csv($_GET);
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

function generate_payment_receipt($payment_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument, i.phone, i.email,
               s.service_date, s.service_type, s.service_title
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE ip.id = ?
    ");
    $stmt->bind_param('i', $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        respond_error('Payment not found', 404);
    }
    
    // Get church settings
    $settings = get_church_settings();
    
    $receipt_html = generate_receipt_html($payment, $settings);
    
    header('Content-Type: text/html');
    echo $receipt_html;
}

function generate_batch_receipt($batch_id) {
    global $conn;
    
    // Get batch info
    $stmt = $conn->prepare("SELECT * FROM payment_batches WHERE id = ?");
    $stmt->bind_param('i', $batch_id);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    
    if (!$batch) {
        respond_error('Batch not found', 404);
    }
    
    // Get batch payments
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument,
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
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $settings = get_church_settings();
    $receipt_html = generate_batch_receipt_html($batch, $payments, $settings);
    
    header('Content-Type: text/html');
    echo $receipt_html;
}

function generate_monthly_report($month) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT ip.*, i.full_name, i.instrument,
               s.service_date, s.service_type,
               COUNT(*) OVER() as total_payments,
               SUM(ip.amount) OVER() as total_amount
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE DATE_FORMAT(s.service_date, '%Y-%m') = ?
        ORDER BY s.service_date DESC, i.full_name
    ");
    $stmt->bind_param('s', $month);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    respond_json([
        'month' => $month,
        'payments' => $payments,
        'summary' => [
            'total_payments' => $payments[0]['total_payments'] ?? 0,
            'total_amount' => $payments[0]['total_amount'] ?? 0
        ]
    ]);
}

function generate_instrumentalist_report($instrumentalist_id, $period) {
    global $conn;
    
    $date_condition = '';
    switch ($period) {
        case 'week':
            $date_condition = "AND s.service_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $date_condition = "AND s.service_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $date_condition = "AND s.service_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
    }
    
    $stmt = $conn->prepare("
        SELECT ip.*, s.service_date, s.service_type,
               i.full_name, i.instrument,
               COUNT(*) OVER() as total_services,
               SUM(ip.amount) OVER() as total_earned
        FROM instrumentalist_payments ip
        JOIN services s ON ip.service_id = s.id
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        WHERE ip.instrumentalist_id = ? $date_condition
        ORDER BY s.service_date DESC
    ");
    $stmt->bind_param('i', $instrumentalist_id);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    respond_json([
        'instrumentalist_id' => $instrumentalist_id,
        'period' => $period,
        'payments' => $payments,
        'summary' => [
            'total_services' => $payments[0]['total_services'] ?? 0,
            'total_earned' => $payments[0]['total_earned'] ?? 0,
            'instrumentalist_name' => $payments[0]['full_name'] ?? '',
            'instrument' => $payments[0]['instrument'] ?? ''
        ]
    ]);
}

function export_payments_csv($params) {
    global $conn;
    
    $month = $params['month'] ?? date('Y-m');
    $status = $params['status'] ?? '';
    
    $where_conditions = ["DATE_FORMAT(s.service_date, '%Y-%m') = ?"];
    $bind_params = [$month];
    $bind_types = 's';
    
    if ($status) {
        $where_conditions[] = "ip.payment_status = ?";
        $bind_params[] = $status;
        $bind_types .= 's';
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $stmt = $conn->prepare("
        SELECT ip.id, i.full_name, i.instrument, s.service_date, s.service_type,
               ip.amount, ip.payment_type, ip.payment_status, ip.payment_method,
               ip.payment_date, ip.reference_number, ip.notes
        FROM instrumentalist_payments ip
        JOIN instrumentalists i ON ip.instrumentalist_id = i.id
        JOIN services s ON ip.service_id = s.id
        WHERE $where_clause
        ORDER BY s.service_date DESC, i.full_name
    ");
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payments_' . $month . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Payment ID', 'Instrumentalist', 'Instrument', 'Service Date', 'Service Type',
        'Amount', 'Payment Type', 'Status', 'Payment Method', 'Payment Date',
        'Reference Number', 'Notes'
    ]);
    
    // CSV data
    foreach ($payments as $payment) {
        fputcsv($output, [
            $payment['id'],
            $payment['full_name'],
            $payment['instrument'],
            $payment['service_date'],
            $payment['service_type'],
            $payment['amount'],
            $payment['payment_type'],
            $payment['payment_status'],
            $payment['payment_method'],
            $payment['payment_date'],
            $payment['reference_number'],
            $payment['notes']
        ]);
    }
    
    fclose($output);
}

function get_church_settings() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

function generate_receipt_html($payment, $settings) {
    $church_name = $settings['church_name'] ?? 'Church Name';
    $church_address = $settings['church_address'] ?? '';
    $church_phone = $settings['church_phone'] ?? '';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Receipt</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
            .receipt-info { margin: 20px 0; }
            .amount { font-size: 24px; font-weight: bold; color: #28a745; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 8px; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; width: 150px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>$church_name</h1>
            <p>$church_address</p>
            <p>$church_phone</p>
            <h2>PAYMENT RECEIPT</h2>
        </div>
        
        <div class='receipt-info'>
            <table>
                <tr><td class='label'>Receipt #:</td><td>PAY-" . str_pad($payment['id'], 6, '0', STR_PAD_LEFT) . "</td></tr>
                <tr><td class='label'>Date:</td><td>" . date('F j, Y') . "</td></tr>
                <tr><td class='label'>Instrumentalist:</td><td>{$payment['full_name']}</td></tr>
                <tr><td class='label'>Instrument:</td><td>{$payment['instrument']}</td></tr>
                <tr><td class='label'>Service:</td><td>{$payment['service_type']} - " . date('M j, Y', strtotime($payment['service_date'])) . "</td></tr>
                <tr><td class='label'>Payment Type:</td><td>{$payment['payment_type']}</td></tr>
                <tr><td class='label'>Payment Method:</td><td>{$payment['payment_method']}</td></tr>
                <tr><td class='label'>Reference:</td><td>{$payment['reference_number']}</td></tr>
                <tr><td class='label'>Amount:</td><td class='amount'>$" . number_format($payment['amount'], 2) . "</td></tr>
            </table>
        </div>
        
        <div class='footer'>
            <p>Thank you for your service!</p>
            <p>Generated on " . date('F j, Y g:i A') . "</p>
        </div>
        
        <script>window.print();</script>
    </body>
    </html>";
}

function generate_batch_receipt_html($batch, $payments, $settings) {
    $church_name = $settings['church_name'] ?? 'Church Name';
    
    $payments_html = '';
    foreach ($payments as $payment) {
        $payments_html .= "
            <tr>
                <td>{$payment['full_name']}</td>
                <td>{$payment['instrument']}</td>
                <td>" . date('M j', strtotime($payment['service_date'])) . "</td>
                <td>{$payment['service_type']}</td>
                <td>$" . number_format($payment['amount'], 2) . "</td>
            </tr>";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Batch Payment Receipt</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
            th { background-color: #f8f9fa; }
            .total { font-weight: bold; background-color: #e9ecef; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>$church_name</h1>
            <h2>BATCH PAYMENT RECEIPT</h2>
            <p>Batch: {$batch['batch_name']} | Date: " . date('F j, Y', strtotime($batch['batch_date'])) . "</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Instrumentalist</th>
                    <th>Instrument</th>
                    <th>Service Date</th>
                    <th>Service Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                $payments_html
                <tr class='total'>
                    <td colspan='4'><strong>Total Amount</strong></td>
                    <td><strong>$" . number_format($batch['total_amount'], 2) . "</strong></td>
                </tr>
            </tbody>
        </table>
        
        <div style='margin-top: 30px; text-align: center;'>
            <p>Processed by: {$batch['processed_by']} on " . date('F j, Y', strtotime($batch['processed_at'])) . "</p>
        </div>
        
        <script>window.print();</script>
    </body>
    </html>";
}
