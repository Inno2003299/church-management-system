<?php
// Database Connection Debug Tool
echo "<h1>Database Connection Debug</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>1. Testing Direct Connection</h2>";

// Test direct connection with different configurations
$configs = [
    'Current Config' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'root',
        'db' => 'church_db'
    ],
    'MAMP Default' => [
        'host' => 'localhost',
        'port' => 8889,
        'user' => 'root',
        'pass' => 'root',
        'db' => 'church_db'
    ],
    'Standard MySQL' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'db' => 'church_db'
    ]
];

$working_config = null;

foreach ($configs as $name => $config) {
    echo "<h3>Testing: $name</h3>";
    echo "<div style='margin-left: 20px;'>";
    
    try {
        $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db'], $config['port']);
        
        if ($conn->connect_error) {
            echo "<div style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</div>";
        } else {
            echo "<div style='color: green;'>✅ Connection successful!</div>";
            echo "<div style='color: green;'>Server: " . $conn->server_info . "</div>";
            echo "<div style='color: green;'>Database: " . $config['db'] . "</div>";
            
            // Test a simple query
            $result = $conn->query("SELECT COUNT(*) as count FROM members");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<div style='color: green;'>✅ Members table accessible: {$row['count']} records</div>";
            } else {
                echo "<div style='color: orange;'>⚠️ Members table query failed: " . $conn->error . "</div>";
            }
            
            $working_config = $config;
            $working_config['name'] = $name;
            $conn->close();
            break;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Exception: " . $e->getMessage() . "</div>";
    }
    
    echo "</div><br>";
}

echo "<h2>2. Testing Current Config File</h2>";

try {
    echo "<div style='color: blue;'>Loading config/db.php...</div>";
    require_once 'config/db.php';
    echo "<div style='color: green;'>✅ Config file loaded successfully</div>";
    
    if (isset($conn)) {
        echo "<div style='color: green;'>✅ Connection variable exists</div>";
        
        if ($conn->ping()) {
            echo "<div style='color: green;'>✅ Database connection is alive</div>";
            
            // Test members table
            $result = $conn->query("SELECT COUNT(*) as count FROM members");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<div style='color: green;'>✅ Members table query successful: {$row['count']} members</div>";
            } else {
                echo "<div style='color: red;'>❌ Members table query failed: " . $conn->error . "</div>";
            }
            
        } else {
            echo "<div style='color: red;'>❌ Database connection is not responding</div>";
        }
    } else {
        echo "<div style='color: red;'>❌ Connection variable not found</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Config file error: " . $e->getMessage() . "</div>";
}

echo "<h2>3. Testing API Helpers</h2>";

try {
    echo "<div style='color: blue;'>Loading api/helpers.php...</div>";
    require_once 'api/helpers.php';
    echo "<div style='color: green;'>✅ Helpers loaded successfully</div>";
    
    // Test helper functions
    if (function_exists('respond_json')) {
        echo "<div style='color: green;'>✅ respond_json function exists</div>";
    } else {
        echo "<div style='color: red;'>❌ respond_json function missing</div>";
    }
    
    if (function_exists('sanitize_input')) {
        echo "<div style='color: green;'>✅ sanitize_input function exists</div>";
    } else {
        echo "<div style='color: red;'>❌ sanitize_input function missing</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Helpers error: " . $e->getMessage() . "</div>";
}

echo "<h2>4. Testing API Endpoints</h2>";

$api_tests = [
    'get_members.php' => 'GET',
    'save_members.php' => 'POST'
];

foreach ($api_tests as $endpoint => $method) {
    echo "<h4>Testing: $endpoint ($method)</h4>";
    
    try {
        $url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/$endpoint";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'data']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "<div style='color: red;'>❌ cURL Error: $error</div>";
        } else {
            echo "<div style='color: blue;'>HTTP Code: $http_code</div>";
            echo "<div style='color: blue;'>Response Body: " . htmlspecialchars(substr($body, 0, 200)) . "...</div>";
            
            if ($http_code == 200) {
                echo "<div style='color: green;'>✅ Endpoint accessible</div>";
                
                // Check if response is JSON
                $json = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "<div style='color: green;'>✅ Valid JSON response</div>";
                } else {
                    echo "<div style='color: orange;'>⚠️ Non-JSON response: " . json_last_error_msg() . "</div>";
                }
            } else {
                echo "<div style='color: red;'>❌ HTTP Error $http_code</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Test error: " . $e->getMessage() . "</div>";
    }
    
    echo "<br>";
}

if ($working_config) {
    echo "<h2>5. Fix Configuration</h2>";
    echo "<div style='color: green;'>✅ Working configuration found: {$working_config['name']}</div>";
    
    $new_config = "<?php
// Church Inventory Management System - Database Configuration
// Auto-generated configuration

// Disable error output for API calls
if (isset(\$_SERVER['REQUEST_URI']) && strpos(\$_SERVER['REQUEST_URI'], '/api/') !== false) {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Database configuration - {$working_config['name']}
\$host = '{$working_config['host']}';
\$port = {$working_config['port']};
\$user = '{$working_config['user']}';
\$pass = '{$working_config['pass']}';
\$db   = '{$working_config['db']}';

try {
    // Connect to MySQL
    \$conn = new mysqli(\$host, \$user, \$pass, \$db, \$port);
    
    if (\$conn->connect_error) {
        throw new Exception(\"MySQL connection failed: \" . \$conn->connect_error);
    }
    
    // Set charset
    \$conn->set_charset('utf8mb4');
    
    // Connection successful
    
} catch (Exception \$e) {
    // Log the error
    error_log(\"Database error: \" . \$e->getMessage());
    
    // For API responses, return JSON error
    if (isset(\$_SERVER['REQUEST_URI']) && strpos(\$_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Database connection failed',
            'message' => \$e->getMessage(),
            'success' => false
        ]);
        exit;
    } else {
        // For regular pages, show error
        die(\"Database connection failed: \" . \$e->getMessage() . \"<br><br>Please check MAMP MySQL service is running.\");
    }
}";

    if (file_put_contents('config/db.php', $new_config)) {
        echo "<div style='color: green;'>✅ Configuration file updated with working settings</div>";
        echo "<div style='color: blue;'>Please test the application again</div>";
    } else {
        echo "<div style='color: red;'>❌ Failed to update configuration file</div>";
    }
    
} else {
    echo "<h2>5. No Working Configuration Found</h2>";
    echo "<div style='color: red;'>❌ Please check MAMP MySQL service is running</div>";
}

echo "<hr>";
echo "<p><a href='test_add_member_form.html'>Test Add Member</a> | ";
echo "<a href='index.html'>Main Application</a> | ";
echo "<a href='api/get_members.php'>Test API</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h1, h2, h3, h4 {
    color: #333;
    border-bottom: 1px solid #007bff;
    padding-bottom: 5px;
}

div {
    margin: 5px 0;
    padding: 3px;
}

a {
    color: #007bff;
    text-decoration: none;
    margin: 0 10px;
}

a:hover {
    text-decoration: underline;
}
</style>
