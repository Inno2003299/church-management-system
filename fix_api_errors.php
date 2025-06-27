<?php
// Fix API Errors
echo "<h1>🔧 Fix API Errors</h1>";

echo "<h2>1. Testing Problematic APIs</h2>";

// Test instrumentalists.php
echo "<h3>Testing instrumentalists.php</h3>";
$instrumentalists_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/instrumentalists.php?active_only=true";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $instrumentalists_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

echo "<div style='color: blue;'>HTTP Status: $http_code</div>";
echo "<div style='color: blue;'>Response Headers:</div>";
echo "<pre style='background: #f8f9fa; padding: 10px; font-size: 12px;'>" . htmlspecialchars($headers) . "</pre>";
echo "<div style='color: blue;'>Response Body:</div>";
echo "<pre style='background: #f8f9fa; padding: 10px;'>" . htmlspecialchars($body) . "</pre>";

// Test offerings.php
echo "<h3>Testing offerings.php</h3>";
$offerings_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/offerings.php?date=2025-06-24";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $offerings_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

echo "<div style='color: blue;'>HTTP Status: $http_code</div>";
echo "<div style='color: blue;'>Response Headers:</div>";
echo "<pre style='background: #f8f9fa; padding: 10px; font-size: 12px;'>" . htmlspecialchars($headers) . "</pre>";
echo "<div style='color: blue;'>Response Body:</div>";
echo "<pre style='background: #f8f9fa; padding: 10px;'>" . htmlspecialchars($body) . "</pre>";

echo "<h2>2. Creating Fixed API Versions</h2>";

// Create fixed instrumentalists.php
echo "<h3>Creating Fixed instrumentalists.php</h3>";

$fixed_instrumentalists = '<?php
// Fixed Instrumentalists API
ini_set("display_errors", 0);
error_reporting(0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

try {
    require_once "../config/db.php";
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Check if instrumentalists table exists
    $table_check = $conn->query("SHOW TABLES LIKE \'instrumentalists\'");
    if ($table_check->num_rows == 0) {
        // Table doesn\'t exist, create it
        $create_table = "CREATE TABLE IF NOT EXISTS instrumentalists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(255),
            instrument VARCHAR(100),
            skill_level ENUM(\'Beginner\', \'Intermediate\', \'Advanced\') DEFAULT \'Beginner\',
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Failed to create instrumentalists table");
        }
    }
    
    // Build query
    $sql = "SELECT id, full_name, phone, email, instrument, skill_level, is_active, created_at FROM instrumentalists";
    
    // Add active filter if requested
    if (isset($_GET[\'active_only\']) && $_GET[\'active_only\'] === \'true\') {
        $sql .= " WHERE is_active = 1";
    }
    
    $sql .= " ORDER BY full_name";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $instrumentalists = [];
    while ($row = $result->fetch_assoc()) {
        $instrumentalists[] = $row;
    }
    
    echo json_encode($instrumentalists);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server error: " . $e->getMessage(),
        "instrumentalists" => []
    ]);
}
?>';

if (file_put_contents('api/instrumentalists_fixed.php', $fixed_instrumentalists)) {
    echo "<div style='color: green;'>✅ Created fixed instrumentalists.php</div>";
} else {
    echo "<div style='color: red;'>❌ Failed to create fixed instrumentalists.php</div>";
}

// Create fixed offerings.php
echo "<h3>Creating Fixed offerings.php</h3>";

$fixed_offerings = '<?php
// Fixed Offerings API
ini_set("display_errors", 0);
error_reporting(0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

try {
    require_once "../config/db.php";
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Check if offerings table exists
    $table_check = $conn->query("SHOW TABLES LIKE \'offerings\'");
    if ($table_check->num_rows == 0) {
        // Table doesn\'t exist, create it
        $create_table = "CREATE TABLE IF NOT EXISTS offerings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offering_type_id INT,
            amount DECIMAL(10,2) NOT NULL,
            offering_date DATE NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (offering_type_id) REFERENCES offering_types(id)
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Failed to create offerings table");
        }
    }
    
    // Check if offering_types table exists
    $types_check = $conn->query("SHOW TABLES LIKE \'offering_types\'");
    if ($types_check->num_rows == 0) {
        // Create offering_types table
        $create_types = "CREATE TABLE IF NOT EXISTS offering_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_types)) {
            // Insert default offering types
            $default_types = [
                [\'Tithe\', \'Regular tithe offerings\'],
                [\'Thanksgiving\', \'Thanksgiving offerings\'],
                [\'Seed Offering\', \'Seed/faith offerings\'],
                [\'Building Fund\', \'Church building fund\'],
                [\'Mission\', \'Mission and outreach\'],
                [\'Special Collection\', \'Special collections and events\']
            ];
            
            foreach ($default_types as $type) {
                $stmt = $conn->prepare("INSERT IGNORE INTO offering_types (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $type[0], $type[1]);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Build query based on request
    if (isset($_GET[\'date\'])) {
        // Get offerings for specific date
        $date = $_GET[\'date\'];
        $sql = "SELECT o.id, o.amount, o.offering_date, o.notes, o.created_at, 
                       ot.name as offering_type, ot.id as offering_type_id
                FROM offerings o 
                LEFT JOIN offering_types ot ON o.offering_type_id = ot.id 
                WHERE o.offering_date = ? 
                ORDER BY o.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $offerings = [];
        $total = 0;
        
        while ($row = $result->fetch_assoc()) {
            $offerings[] = $row;
            $total += $row[\'amount\'];
        }
        
        echo json_encode([
            "offerings" => $offerings,
            "total" => $total,
            "date" => $date,
            "count" => count($offerings)
        ]);
        
        $stmt->close();
        
    } else {
        // Get all offerings
        $sql = "SELECT o.id, o.amount, o.offering_date, o.notes, o.created_at,
                       ot.name as offering_type, ot.id as offering_type_id
                FROM offerings o 
                LEFT JOIN offering_types ot ON o.offering_type_id = ot.id 
                ORDER BY o.offering_date DESC, o.created_at DESC";
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $offerings = [];
        while ($row = $result->fetch_assoc()) {
            $offerings[] = $row;
        }
        
        echo json_encode($offerings);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server error: " . $e->getMessage(),
        "offerings" => [],
        "total" => 0
    ]);
}
?>';

if (file_put_contents('api/offerings_fixed.php', $fixed_offerings)) {
    echo "<div style='color: green;'>✅ Created fixed offerings.php</div>";
} else {
    echo "<div style='color: red;'>❌ Failed to create fixed offerings.php</div>";
}

echo "<h2>3. Testing Fixed APIs</h2>";

// Test fixed instrumentalists
echo "<h3>Testing Fixed instrumentalists.php</h3>";
$fixed_inst_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/instrumentalists_fixed.php?active_only=true";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fixed_inst_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<div style='color: blue;'>Fixed Instrumentalists HTTP Status: $http_code</div>";
echo "<div style='color: blue;'>Fixed Response:</div>";
echo "<pre style='background: #f8f9fa; padding: 10px;'>" . htmlspecialchars($response) . "</pre>";

if ($http_code == 200) {
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<div style='color: green;'>✅ Fixed instrumentalists API working!</div>";
    } else {
        echo "<div style='color: red;'>❌ Invalid JSON in fixed API</div>";
    }
} else {
    echo "<div style='color: red;'>❌ Fixed instrumentalists API still failing</div>";
}

// Test fixed offerings
echo "<h3>Testing Fixed offerings.php</h3>";
$fixed_off_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api/offerings_fixed.php?date=2025-06-24";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fixed_off_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<div style='color: blue;'>Fixed Offerings HTTP Status: $http_code</div>";
echo "<div style='color: blue;'>Fixed Response:</div>";
echo "<pre style='background: #f8f9fa; padding: 10px;'>" . htmlspecialchars($response) . "</pre>";

if ($http_code == 200) {
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<div style='color: green;'>✅ Fixed offerings API working!</div>";
    } else {
        echo "<div style='color: red;'>❌ Invalid JSON in fixed API</div>";
    }
} else {
    echo "<div style='color: red;'>❌ Fixed offerings API still failing</div>";
}

echo "<h2>4. Replace Original Files</h2>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='?replace_instrumentalists=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Replace instrumentalists.php</a>";
echo "<a href='?replace_offerings=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Replace offerings.php</a>";
echo "</div>";

// Handle replacements
if (isset($_GET['replace_instrumentalists']) && $_GET['replace_instrumentalists'] == '1') {
    if (copy('api/instrumentalists_fixed.php', 'api/instrumentalists.php')) {
        echo "<div style='color: green; font-weight: bold; margin: 20px 0; padding: 15px; background: #d4edda; border-radius: 4px;'>✅ instrumentalists.php replaced successfully!</div>";
    } else {
        echo "<div style='color: red; font-weight: bold; margin: 20px 0;'>❌ Failed to replace instrumentalists.php</div>";
    }
}

if (isset($_GET['replace_offerings']) && $_GET['replace_offerings'] == '1') {
    if (copy('api/offerings_fixed.php', 'api/offerings.php')) {
        echo "<div style='color: green; font-weight: bold; margin: 20px 0; padding: 15px; background: #d4edda; border-radius: 4px;'>✅ offerings.php replaced successfully!</div>";
    } else {
        echo "<div style='color: red; font-weight: bold; margin: 20px 0;'>❌ Failed to replace offerings.php</div>";
    }
}

echo "<h2>5. Summary</h2>";
echo "<p>This tool identifies and fixes the API errors causing dashboard issues.</p>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Replace the original API files with the fixed versions</li>";
echo "<li><a href='index.php'>Test the main application</a></li>";
echo "<li><a href='cross_check_connection.php'>Run cross-check to verify fixes</a></li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>🏠 Main App</a> | ";
echo "<a href='cross_check_connection.php'>🔍 Cross-Check</a> | ";
echo "<a href='api/instrumentalists_fixed.php?active_only=true' target='_blank'>📡 Fixed Instrumentalists</a> | ";
echo "<a href='api/offerings_fixed.php?date=2025-06-24' target='_blank'>📡 Fixed Offerings</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h1, h2, h3 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

pre {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
}

div {
    margin: 5px 0;
    padding: 3px;
}

ol {
    background: white;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

a {
    color: #007bff;
    text-decoration: none;
    margin: 0 5px;
}

a:hover {
    text-decoration: underline;
}
</style>
