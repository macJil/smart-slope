<?php
// predict.php — JSON endpoint for landslide risk prediction
// Called by the dashboard's manual prediction form via AJAX
require_once __DIR__ . '/classes/Prediction.php';
header('Content-Type: application/json');

// Read JSON from request body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON.']);
    exit;
}

// Validate required fields
$required = ['rainfall_mm', 'humidity', 'pressure', 'temperature', 'wind_speed'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

// Run the AI prediction (or fallback)
$result = Prediction::run(
    $input['rainfall_mm'],
    $input['humidity'],
    $input['pressure'],
    $input['temperature'],
    $input['wind_speed']
);

echo json_encode($result);