<?php
require_once 'config.php';
require_once 'classes/Telemetry.php';
require_once 'classes/SensorNode.php';

$node_id = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT) ?: 1;
$node = (new SensorNode($conn))->getById($node_id);
if (!$node) {
    http_response_code(400);
    exit('Invalid monitoring location.');
}

$lat = (float) $node['latitude'];
$lon = (float) $node['longitude'];
$url = "https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lon"
     . "&current=rain,relative_humidity_2m,pressure_msl,temperature_2m,wind_speed_10m";

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'ignore_errors' => true
    ]
]);
$response = @file_get_contents($url, false, $context);
$weatherData = $response ? json_decode($response, true) : null;
$current = is_array($weatherData) ? ($weatherData['current'] ?? null) : null;
if (!is_array($current)) {
    http_response_code(502);
    exit('Weather service is temporarily unavailable. Please try again.');
}

$rainfall = $current['rain'] ?? 0;
$humidity = $current['relative_humidity_2m'];
$pressure = $current['pressure_msl'];
$temperature = $current['temperature_2m'];
$wind_speed = $current['wind_speed_10m'];

// Use OOP class to insert
$telemetry = new Telemetry($conn);

// node_id = 1 (the Loay mock node), soil_moisture = null (Phase 1, no sensor)
$telemetry->insert(
    $node_id,
    null,     // soil_moisture (NULL in Phase 1)
    $rainfall,
    $humidity,
    $pressure,
    $temperature,
    $wind_speed,
    'API'     // source
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weather Fetch — Smart Slope</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="mx-auto" style="max-width: 620px;">
            <div class="alert alert-success shadow-sm" role="alert">
                <h1 class="h4">Weather data updated</h1>
                <p class="mb-3">Open-Meteo data for <?= htmlspecialchars($node['location_name']) ?> was stored successfully.</p>
                <a class="btn btn-success" href="index.php?node_id=<?= (int) $node_id ?>">Back to Dashboard</a>
            </div>
        </div>
    </main>
</body>
</html>
<?php
$conn = null;
?>