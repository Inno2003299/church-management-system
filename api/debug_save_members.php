<?php
// Debug version of save_members.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$debug_info = [
    "request_method" => $_SERVER["REQUEST_METHOD"],
    "content_type" => $_SERVER["CONTENT_TYPE"] ?? "not set",
    "raw_input" => file_get_contents("php://input"),
    "post_data" => $_POST,
    "get_data" => $_GET,
    "headers" => getallheaders()
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>