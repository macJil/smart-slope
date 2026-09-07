<?php
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

// Prepare input for Python
$input_data = json_encode([
    'rainfall_mm' => floatval($input['rainfall_mm']),
    'humidity' => floatval($input['humidity']),
    'pressure' => floatval($input['pressure']),
    'temperature' => floatval($input['temperature']),
    'wind_speed' => floatval($input['wind_speed'])
]);

// Write to temp file, run Python, capture output
$python = '/usr/local/bin/python3';
$script = __DIR__ . '/predict.py';
$model = __DIR__ . '/landslide_model.pkl';
$command = escapeshellcmd($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($model);
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
];
$process = proc_open($command, $descriptors, $pipes, __DIR__);

if (!is_resource($process)) {
    http_response_code(500);
    echo json_encode(['error' => 'The prediction service could not be started.']);
    exit;
}

fwrite($pipes[0], $input_data);
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit_code = proc_close($process);

if ($exit_code !== 0 || trim($output) === '') {
    // XAMPP on Apple Silicon may run Apache under Rosetta, which cannot load
    // arm64 Python wheels. Keep the documented rainfall threshold available
    // as a local demo fallback when the model process cannot start.
    echo json_encode([
        'prediction' => (float) $input['rainfall_mm'] > 100 ? 1 : 0,
        'fallback' => true,
        'message' => 'Prototype mode: rainfall threshold assessment used.'
    ]);
    exit;
}

echo $output;
?>