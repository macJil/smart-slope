<?php
// predict.php — JSON endpoint for manual risk calculation and AI analysis
// Called by the dashboard via AJAX.

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/RiskEngine.php';
require_once __DIR__ . '/classes/AIAnalyzer.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// ── AI analysis mode ────────────────────────────────────
if (($input['action'] ?? '') === 'ai') {
    $result = AIAnalyzer::analyze($input);
    echo json_encode($result);
    exit;
}

// ── Risk calculation mode (default) ─────────────────────
$required = ['rainfall_mm', 'humidity', 'pressure', 'temperature', 'wind_speed'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit;
    }
}

$soil = isset($input['soil_moisture']) && $input['soil_moisture'] !== ''
    ? (float) $input['soil_moisture']
    : null;

$risk = RiskEngine::calculate(
    (float) $input['rainfall_mm'],
    (float) $input['humidity'],
    $soil,
    (float) $input['pressure'],
    (float) $input['temperature'],
    (float) $input['wind_speed']
);

echo json_encode([
    'success'    => true,
    'risk_score' => $risk['score'],
    'risk_level' => $risk['level'],
]);