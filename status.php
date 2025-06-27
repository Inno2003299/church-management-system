<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Management System - Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Church Management System - Status</h1>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>System Status</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            require_once 'config/db.php';
                            echo "<div class='alert alert-success'>✅ Database connection: OK</div>";
                            
                            // Check tables and data
                            $tables = [
                                'services' => 'Services',
                                'instrumentalists' => 'Instrumentalists', 
                                'instrumentalist_payments' => 'Payments',
                                'members' => 'Members'
                            ];
                            
                            echo "<h6>Database Tables:</h6>";
                            foreach ($tables as $table => $name) {
                                $result = $conn->query("SELECT COUNT(*) as count FROM $table");
                                if ($result) {
                                    $count = $result->fetch_assoc()['count'];
                                    $badge = $count > 0 ? 'bg-success' : 'bg-warning';
                                    echo "<span class='badge $badge me-2'>$name: $count</span>";
                                } else {
                                    echo "<span class='badge bg-danger me-2'>$name: Error</span>";
                                }
                            }
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>❌ Database error: " . $e->getMessage() . "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Services API</h6>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary btn-sm" onclick="testAPI('services')">Test</button>
                        <div id="services-status" class="mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Payments API</h6>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary btn-sm" onclick="testAPI('payments')">Test</button>
                        <div id="payments-status" class="mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Instrumentalists API</h6>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary btn-sm" onclick="testAPI('instrumentalists')">Test</button>
                        <div id="instrumentalists-status" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <a href="create_test_data.php" class="btn btn-success me-2">Create Test Data</a>
                        <a href="index.php" class="btn btn-primary me-2">Go to Main App</a>
                        <button class="btn btn-info" onclick="testAllAPIs()">Test All APIs</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function testAPI(type) {
            const statusDiv = document.getElementById(type + '-status');
            statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
            
            let url;
            switch(type) {
                case 'services':
                    url = 'api/services.php';
                    break;
                case 'payments':
                    url = 'api/payment_processing.php?action=pending_payments';
                    break;
                case 'instrumentalists':
                    url = 'api/instrumentalists.php';
                    break;
            }
            
            try {
                const response = await fetch(url);
                const text = await response.text();
                
                if (response.ok) {
                    try {
                        const data = JSON.parse(text);
                        if (data.error) {
                            statusDiv.innerHTML = `<small class="text-danger">Error: ${data.error}</small>`;
                        } else {
                            const count = Array.isArray(data) ? data.length : 'N/A';
                            statusDiv.innerHTML = `<small class="text-success">✅ OK (${count} items)</small>`;
                        }
                    } catch (e) {
                        statusDiv.innerHTML = `<small class="text-warning">⚠️ Invalid JSON</small>`;
                    }
                } else {
                    statusDiv.innerHTML = `<small class="text-danger">❌ HTTP ${response.status}</small>`;
                }
            } catch (error) {
                statusDiv.innerHTML = `<small class="text-danger">❌ ${error.message}</small>`;
            }
        }
        
        async function testAllAPIs() {
            await testAPI('services');
            await testAPI('payments');
            await testAPI('instrumentalists');
        }
        
        // Test all APIs on page load
        window.addEventListener('load', testAllAPIs);
    </script>
</body>
</html>
