<?php
// Working Save Members API
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed. Use POST."]);
    exit;
}

try {
    // Get database connection
    require_once "../config/db.php";
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get JSON input
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    // Debug info (remove in production)
    error_log("Save Members API - Raw input: " . $raw_input);
    error_log("Save Members API - Parsed data: " . json_encode($data));
    
    // Validate input
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }
    
    if (empty($data["full_name"]) || empty($data["gender"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Full name and gender are required",
            "received" => $data
        ]);
        exit;
    }
    
    // Sanitize input
    $full_name = htmlspecialchars(strip_tags(trim($data["full_name"])));
    $phone = isset($data["phone"]) ? htmlspecialchars(strip_tags(trim($data["phone"]))) : "";
    $email = isset($data["email"]) ? htmlspecialchars(strip_tags(trim($data["email"]))) : "";
    $gender = htmlspecialchars(strip_tags(trim($data["gender"])));
    $date_of_birth = isset($data["date_of_birth"]) && !empty($data["date_of_birth"]) ? $data["date_of_birth"] : null;
    $address = isset($data["address"]) ? htmlspecialchars(strip_tags(trim($data["address"]))) : "";
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO members (full_name, phone, email, gender, date_of_birth, address) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssssss", $full_name, $phone, $email, $gender, $date_of_birth, $address);
    
    if ($stmt->execute()) {
        $member_id = $stmt->insert_id;
        echo json_encode([
            "id" => $member_id,
            "message" => "Member added successfully",
            "success" => true
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server error: " . $e->getMessage()
    ]);
}
?>