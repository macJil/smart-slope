<?php
// fetch_weather.php — Fetch live weather from Open-Meteo and store with risk score.
// Returns JSON. Called by the dashboard "Update Weather" button via AJAX.

require_once 'auth.php';
require_once 'config.php';
require_once 'classes/RiskEngine.php';

header('Content-Type: application/json');

$node_id = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'node_id', FILTER_VALIDATE_INT)
    ?: 1;

$stmt = $conn->prepare('SELECT * FROM sensor_nodes WHERE node_id = ? AND status = ?');
$stmt->execute([$node_id, 'active']);
$node = $stmt->fetch();
if (!$node) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid monitoring location.']);
    exit;
}

// ── Call Open-Meteo REST API (free, no key needed) ──────
$url = 'https://api.open-meteo.com/v1/forecast?latitude=' . $node['latitude']
     . '&longitude=' . $node['longitude']
     . '&current=rain,relative_humidity_2m,pressure_msl,temperature_2m,wind_speed_10m';

$response = @file_get_contents($url);
$weather  = $response ? json_decode($response, true) : null;
$current  = is_array($weather) ? ($weather['current'] ?? null) : null;

if (!is_array($current)) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Weather service is temporarily unavailable.']);
    exit;
}

// ── Extract weather values ─────────────────────────────
$rainfall     = (float) ($current['rain'] ?? 0);
$humidity     = (float) ($current['relative_humidity_2m'] ?? 0);
$pressure     = (float) ($current['pressure_msl'] ?? 1013);
$temperature  = (float) ($current['temperature_2m'] ?? 20);
$wind_speed   = (float) ($current['wind_speed_10m'] ?? 0);

// ── Calculate risk with RiskEngine ──────────────────────
$risk = RiskEngine::calculate($rainfall, $humidity, null, $pressure, $temperature, $wind_speed);

// ── Store in database ───────────────────────────────────
$stmt = $conn->prepare(
    'INSERT INTO telemetry_logs
        (node_id, soil_moisture, rainfall_mm, humidity, pressure, temperature,
         wind_speed, risk_score, risk_level, source, timestamp, created_at)
     VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
);
$stmt->execute([
    $node_id, $rainfall, $humidity, $pressure, $temperature, $wind_speed,
    $risk['score'], $risk['level'], 'API'
]);

// ── Return JSON ─────────────────────────────────────────
echo json_encode([
    'success'    => true,
    'message'    => 'Weather updated for ' . $node['location_name'],
    'location'   => $node['location_name'],
    'risk_score' => $risk['score'],
    'risk_level' => $risk['level'],
    'rainfall_mm'  => $rainfall,
    'humidity'     => $humidity,
    'pressure'     => $pressure,
    'temperature'  => $temperature,
    'wind_speed'   => $wind_speed,
]);