<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paystack Connection Test - Church Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="bi bi-wifi me-2"></i>
                            Paystack Connection Test
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Testing Paystack Integration:</strong> This page will test your Paystack API connection and display account information.
                        </div>

                        <div id="test-results">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Testing connection...</span>
                                </div>
                                <p class="mt-2">Testing Paystack connection...</p>
                            </div>
                        </div>

                        <?php
                        try {
                            require_once 'config/paystack.php';
                            
                            echo '<div class="alert alert-success">';
                            echo '<h5><i class="bi bi-check-circle me-2"></i>Configuration Status</h5>';
                            echo '<ul class="mb-0">';
                            echo '<li><strong>Secret Key:</strong> ' . (defined('PAYSTACK_SECRET_KEY') ? '✅ Configured (' . substr(PAYSTACK_SECRET_KEY, 0, 12) . '...)' : '❌ Not configured') . '</li>';
                            echo '<li><strong>Public Key:</strong> ' . (defined('PAYSTACK_PUBLIC_KEY') ? '✅ Configured (' . substr(PAYSTACK_PUBLIC_KEY, 0, 12) . '...)' : '❌ Not configured') . '</li>';
                            echo '<li><strong>Base URL:</strong> ' . (defined('PAYSTACK_BASE_URL') ? '✅ ' . PAYSTACK_BASE_URL : '❌ Not configured') . '</li>';
                            echo '</ul>';
                            echo '</div>';

                            // Test API connection
                            echo '<div class="alert alert-info">';
                            echo '<h5><i class="bi bi-cloud me-2"></i>API Connection Test</h5>';
                            
                            $balance_result = get_paystack_balance();
                            
                            if ($balance_result['success']) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="bi bi-check-circle me-2"></i>';
                                echo '<strong>✅ Connection Successful!</strong><br>';
                                echo 'Account Balance: <strong>' . format_amount($balance_result['balance']) . '</strong><br>';
                                echo 'Currency: ' . ($balance_result['currency'] ?? 'GHS');
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-danger">';
                                echo '<i class="bi bi-x-circle me-2"></i>';
                                echo '<strong>❌ Connection Failed!</strong><br>';
                                echo 'Error: ' . $balance_result['error'];
                                echo '</div>';
                            }
                            echo '</div>';

                            // Test recipient creation (simulation)
                            echo '<div class="alert alert-info">';
                            echo '<h5><i class="bi bi-person-plus me-2"></i>Recipient Creation Test</h5>';
                            
                            $test_instrumentalist = [
                                'momo_name' => 'Test User',
                                'momo_number' => '0241234567',
                                'momo_provider' => 'MTN'
                            ];
                            
                            echo '<p>Testing recipient creation with sample data:</p>';
                            echo '<ul>';
                            echo '<li><strong>Name:</strong> ' . $test_instrumentalist['momo_name'] . '</li>';
                            echo '<li><strong>Number:</strong> ' . $test_instrumentalist['momo_number'] . '</li>';
                            echo '<li><strong>Provider:</strong> ' . $test_instrumentalist['momo_provider'] . '</li>';
                            echo '</ul>';
                            
                            // Note: We won't actually create a recipient in test mode
                            echo '<div class="alert alert-warning">';
                            echo '<i class="bi bi-info-circle me-2"></i>';
                            echo '<strong>Note:</strong> Recipient creation test skipped to avoid creating test recipients. ';
                            echo 'This will work when processing actual payments.';
                            echo '</div>';
                            echo '</div>';

                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '<h5><i class="bi bi-x-circle me-2"></i>Configuration Error</h5>';
                            echo '<p><strong>Error:</strong> ' . $e->getMessage() . '</p>';
                            echo '<p>Please check your Paystack configuration in <code>config/paystack.php</code></p>';
                            echo '</div>';
                        }
                        ?>

                        <div class="mt-4">
                            <h5><i class="bi bi-list-check me-2"></i>Next Steps</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="bi bi-person-plus me-2"></i>Add Instrumentalists
                                            </h6>
                                            <p class="card-text">Register instrumentalists with complete MoMo details for automatic payments.</p>
                                            <a href="index.php#instrumentalists" class="btn btn-primary btn-sm">
                                                <i class="bi bi-arrow-right me-1"></i>Go to Instrumentalists
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="bi bi-credit-card me-2"></i>Process Payments
                                            </h6>
                                            <p class="card-text">Create and process payments using the new Paystack integration.</p>
                                            <a href="index.php#payments" class="btn btn-success btn-sm">
                                                <i class="bi bi-arrow-right me-1"></i>Go to Payments
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <a href="paystack_setup.html" class="btn btn-outline-primary">
                                <i class="bi bi-gear me-2"></i>Setup Guide
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
