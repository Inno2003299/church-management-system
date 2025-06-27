<?php
// Fixed Save Members API
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
    require "helpers.php";

    // Get JSON input
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    // If JSON decode failed, try to get from POST
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data["full_name"]) || empty($data["gender"])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Full name and gender are required",
            "received_data" => $data,
            "raw_input" => $raw_input
        ]);
        exit;
    }

    $full_name = htmlspecialchars(strip_tags(trim($data["full_name"])));
    $phone = isset($data["phone"]) ? htmlspecialchars(strip_tags(trim($data["phone"]))) : "";
    $email = isset($data["email"]) ? htmlspecialchars(strip_tags(trim($data["email"]))) : "";
    $gender = htmlspecialchars(strip_tags(trim($data["gender"])));
    $date_of_birth = isset($data["date_of_birth"]) ? htmlspecialchars(strip_tags(trim($data["date_of_birth"]))) : null;
    $address = isset($data["address"]) ? htmlspecialchars(strip_tags(trim($data["address"]))) : "";

    $stmt = $conn->prepare("INSERT INTO members (full_name, phone, email, gender, date_of_birth, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $full_name, $phone, $email, $gender, $date_of_birth, $address);

    if ($stmt->execute()) {
        $member_id = $stmt->insert_id;
        echo json_encode([
            "id" => $member_id,
            "message" => "Member added successfully",
            "success" => true
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "error" => "Failed to add member: " . $stmt->error
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>