<?php
require_once __DIR__ . '/classes/Prediction.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Request body must be valid JSON.']);
    exit;
}

// Validate all required fields
$required = ['rainfall_mm', 'humidity', 'pressure', 'temperature', 'wind_speed'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

$result = Prediction::run(
    floatval($input['rainfall_mm']),
    floatval($input['humidity']),
    floatval($input['pressure']),
    floatval($input['temperature']),
    floatval($input['wind_speed'])
);

echo json_encode($result);