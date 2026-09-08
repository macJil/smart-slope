<?php
// index.php - Main dashboard
require 'auth.php';
require 'config.php';
require 'classes/RiskEngine.php';

// Database connection
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all locations
$locations = $pdo->query("SELECT node_id, location_name, latitude, longitude FROM sensor_nodes WHERE status = 'active' ORDER BY location_name")->fetchAll();

// Get selected location (default to first)
$selectedNodeId = isset($_GET['node_id']) ? (int)$_GET['node_id'] : ($locations[0]['node_id'] ?? 0);

// Get latest telemetry for selected location
$stmt = $pdo->prepare("SELECT t.*, n.location_name, n.latitude, n.longitude 
     FROM telemetry_logs t 
     JOIN sensor_nodes n ON t.node_id = n.node_id 
     WHERE t.node_id = ? 
     ORDER BY t.created_at DESC 
     LIMIT 1");
$stmt->execute([$selectedNodeId]);
$latest = $stmt->fetch();

// If no telemetry, create demo data
if (!$latest) {
    $latest = [
        'node_id' => $selectedNodeId,
        'location_name' => $locations[0]['location_name'] ?? 'Demo Location',
        'latitude' => $locations[0]['latitude'] ?? 16.4173,
        'longitude' => $locations[0]['longitude'] ?? 120.5963,
        'rainfall_mm' => 45.5,
        'humidity' => 85,
        'temperature' => 22,
        'pressure' => 1008,
        'wind_speed' => 8,
        'soil_moisture' => 75,
        'risk_score' => 0,
        'risk_level' => 'Low',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Calculate risk
$risk = RiskEngine::calculate(
    (float)($latest['rainfall_mm'] ?? 0),
    (float)($latest['humidity'] ?? 0),
    ($latest['soil_moisture'] !== null) ? (float)($latest['soil_moisture']) : null,
    (float)($latest['pressure'] ?? 1013),
    (float)($latest['temperature'] ?? 0),
    (float)($latest['wind_speed'] ?? 0)
);
$latest['risk_score'] = $risk['score'];
$latest['risk_level'] = $risk['level'];

$aiResult = [
    'provider' => 'Loading',
    'analysis' => 'Loading AI analysis...'
];

// Get recent telemetry for table
$stmt = $pdo->prepare("SELECT t.*, n.location_name 
     FROM telemetry_logs t 
     JOIN sensor_nodes n ON t.node_id = n.node_id 
     WHERE t.node_id = ? 
     ORDER BY t.created_at DESC 
     LIMIT 10");
$stmt->execute([$selectedNodeId]);
$telemetryList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?= APP_NAME ?></a>
            <div class="navbar-nav">
                <a class="nav-link" href="locations.php">Locations</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-heading mb-4">
            <h1>Landslide Risk Monitoring Dashboard</h1>
            <p class="text-muted mb-0">Baguio City, Philippines</p>
        </div>

        <!-- Location Selector -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5>Select Monitoring Location</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="index.php" class="row g-3">
                    <div class="col-md-4">
                        <select name="node_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc['node_id'] ?>" 
                                    <?= $loc['node_id'] == $selectedNodeId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['location_name']) ?>
                                    (<?= $loc['latitude'] ?>, <?= $loc['longitude'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <p class="mb-0 text-muted">
                            Current: <strong><?= htmlspecialchars($latest['location_name'] ?? 'N/A') ?></strong> | 
                            Lat: <?= $latest['latitude'] ?? 'N/A' ?> | 
                            Lng: <?= $latest['longitude'] ?? 'N/A' ?>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Weather Cards -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5>Latest Weather Readings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body p-2">
                                        <h6 class="mb-1">Rainfall</h6>
                                        <p class="mb-0 fs-5"><strong><?= $latest['rainfall_mm'] ?? 0 ?> mm</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body p-2">
                                        <h6 class="mb-1">Humidity</h6>
                                        <p class="mb-0 fs-5"><strong><?= $latest['humidity'] ?? 0 ?>%</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body p-2">
                                        <h6 class="mb-1">Temperature</h6>
                                        <p class="mb-0 fs-5"><strong><?= $latest['temperature'] ?? 0 ?>°C</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body p-2">
                                        <h6 class="mb-1">Pressure</h6>
                                        <p class="mb-0 fs-5"><strong><?= $latest['pressure'] ?? 1013 ?> hPa</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body p-2">
                                        <h6 class="mb-1">Wind</h6>
                                        <p class="mb-0 fs-5"><strong><?= $latest['wind_speed'] ?? 0 ?> km/h</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center h-100">
                                    <div class="card-body p-2">
                                        <h6 class="mb-1">Soil Moisture</h6>
                                        <p class="mb-0 fs-5"><strong><?= $latest['soil_moisture'] ?? 'N/A' ?>%</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Risk Card -->
            <div class="col-md-4">
                <div class="card text-center h-100">
                    <div class="card-header bg-dark text-white">
                        <h5>Current Risk Level</h5>
                    </div>
                    <div class="card-body">
                        <h1 class="risk-<?= strtolower($latest['risk_level'] ?? 'low') ?> mb-3">
                            <?= htmlspecialchars($latest['risk_level'] ?? 'Low') ?>
                        </h1>
                        <p class="display-6 fw-bold mb-4">
                            <?= $latest['risk_score'] ?? 0 ?>/100
                        </p>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-<?= RiskEngine::levelAlertClass($latest['risk_level'] ?? 'Low') ?>" 
                                 style="width: <?= $latest['risk_score'] ?? 0 ?>%">
                            </div>
                        </div>
                        <p class="text-muted mt-3 mb-0">
                            <?= RiskEngine::levelGuidance($latest['risk_level'] ?? 'Low') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Analysis -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">AI Analysis</h5>
                        <span id="ai-provider" class="badge bg-secondary"><?= htmlspecialchars($aiResult['provider']) ?></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Multi-provider fallback: Grok → Gemini → Groq → Mistral → Cerebras → Cloudflare → OpenRouter → Hugging Face → Default
                        </p>
                        <div id="ai-result" style="white-space: pre-wrap; min-height: 100px;">
                            <?= nl2br(htmlspecialchars($aiResult['analysis'] ?? 'Loading AI analysis...')) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Telemetry Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center gap-2">
                        <h5 class="mb-0">Recent Telemetry for <?= htmlspecialchars($latest['location_name'] ?? 'N/A') ?></h5>
                        <div class="d-flex gap-2">
                                <button type="button" id="refresh-weather" class="btn btn-sm btn-light"
                                    data-node-id="<?= (int) $selectedNodeId ?>">Refresh</button>
                            <form method="POST" action="clear_telemetry.php" class="m-0"
                                  onsubmit="return confirm('Empty telemetry for this location?');">
                                <input type="hidden" name="node_id" value="<?= (int) $selectedNodeId ?>">
                                <button type="submit" class="btn btn-sm btn-outline-light">Empty</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($telemetryList)): ?>
                            <p class="text-muted">No telemetry data yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Rainfall</th>
                                            <th>Humidity</th>
                                            <th>Risk Level</th>
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($telemetryList as $t): ?>
                                            <tr>
                                                <td><?= date('M d, H:i', strtotime($t['created_at'] ?? 'now')) ?></td>
                                                <td><?= $t['rainfall_mm'] ?> mm</td>
                                                <td><?= $t['humidity'] ?>%</td>
                                                <td class="risk-<?= strtolower($t['risk_level'] ?? 'low') ?>">
                                                    <strong><?= htmlspecialchars($t['risk_level'] ?? 'Low') ?></strong>
                                                </td>
                                                <td><?= $t['risk_score'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center text-muted mt-4">
            <p>Smart Slope V2 - Landslide Risk Monitoring Prototype | 
               <?= APP_NAME ?> v<?= APP_VERSION ?> | 
               For educational purposes only</p>
        </footer>
    </div>

    <script type="application/json" id="latest-data"><?= json_encode($latest, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script src="assests/js/script.js"></script>
</body>
</html>