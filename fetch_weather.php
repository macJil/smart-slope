<?php
// fetch_weather.php — Fetches live weather from Open-Meteo API and stores it
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/Telemetry.php';
require_once 'classes/SensorNode.php';
require_once 'classes/Prediction.php';

// Get the selected location
$node_id = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT) ?: 1;
$node = (new SensorNode($conn))->getById($node_id);
if (!$node) {
    http_response_code(400);
    exit('Invalid monitoring location.');
}

// Call Open-Meteo REST API (free, no API key needed)
$url = "https://api.open-meteo.com/v1/forecast?latitude={$node['latitude']}&longitude={$node['longitude']}"
     . "&current=rain,relative_humidity_2m,pressure_msl,temperature_2m,wind_speed_10m";

$response = @file_get_contents($url);
$weather = $response ? json_decode($response, true) : null;
$current = is_array($weather) ? ($weather['current'] ?? null) : null;

if (!is_array($current)) {
    http_response_code(502);
    exit('Weather service is temporarily unavailable.');
}

// Extract weather values
$rainfall = $current['rain'] ?? 0;
$humidity = $current['relative_humidity_2m'];
$pressure = $current['pressure_msl'];
$temperature = $current['temperature_2m'];
$wind_speed = $current['wind_speed_10m'];

// Run AI prediction on the weather data
$pred = Prediction::run($rainfall, $humidity, $pressure, $temperature, $wind_speed);
$risk_level = $pred['prediction'];

// Store in database with the computed risk level
$telemetry = new Telemetry($conn);
$telemetry->insert(
    $node_id, null, $rainfall, $humidity, $pressure,
    $temperature, $wind_speed, 'API', $risk_level
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
            <div class="alert alert-success shadow-sm">
                <h1 class="h4">Weather data updated</h1>
                <p class="mb-3">Open-Meteo data for <?= htmlspecialchars($node['location_name']) ?> was stored successfully.</p>
                <a class="btn btn-success" href="index.php?node_id=<?= (int) $node_id ?>">Back to Dashboard</a>
            </div>
        </div>
    </main>
</body>
</html>
<?php $conn = null; ?>