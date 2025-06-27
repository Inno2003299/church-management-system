<?php
// Disable error output to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__.'/../config/db.php';
header('Content-Type: application/json');

function json_input() {
  return json_decode(file_get_contents('php://input'), true) ?? [];
}

function find_or_create_service(mysqli $conn, string $date, string $type): int {
  $stmt = $conn->prepare("SELECT id FROM services WHERE service_date=? AND service_type=?");
  $stmt->bind_param('ss', $date, $type);
  $stmt->execute(); $stmt->bind_result($id);
  if ($stmt->fetch()) { $stmt->close(); return $id; }
  $stmt->close();

  $stmt = $conn->prepare("INSERT INTO services (service_date, service_type) VALUES (?,?)");
  $stmt->bind_param('ss', $date, $type);
  $stmt->execute();
  return $stmt->insert_id;
}

function respond_json($data, $status_code = 200) {
  http_response_code($status_code);
  echo json_encode($data);
  exit;
}

function respond_error($message, $status_code = 400) {
  respond_json(['error' => $message], $status_code);
}

function validate_required_fields($data, $required_fields) {
  foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
      respond_error("Missing required field: $field");
    }
  }
}

function sanitize_input($input) {
  return htmlspecialchars(strip_tags(trim($input)));
}

function generate_random_string($length = 32) {
  return bin2hex(random_bytes($length / 2));
}

