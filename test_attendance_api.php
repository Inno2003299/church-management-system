<?php
echo "<h1>Test Attendance API</h1>";

// Test data
$testData = [
    'date' => '2025-06-13',
    'service' => 'Friday Service',
    'attendees' => [1, 2, 3]
];

echo "<h2>Test Data:</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

// Test the API
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/save_attendance.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "<h2>API Response:</h2>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Headers:</strong></p>";
echo "<pre>$headers</pre>";
echo "<p><strong>Body:</strong></p>";
echo "<pre>$body</pre>";

// Test if it's valid JSON
$jsonResponse = json_decode($body, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<p><strong>Parsed JSON:</strong></p>";
    echo "<pre>" . json_encode($jsonResponse, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p><strong>JSON Parse Error:</strong> " . json_last_error_msg() . "</p>";
}

// Test direct API access
echo "<h2>Direct API Test:</h2>";
echo "<a href='api/save_attendance.php' target='_blank'>Test API directly (GET request)</a>";
?>
