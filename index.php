<?php
// index.php - Main dashboard
require 'auth.php';
require 'config.php';
require 'classes/RiskEngine.php';
require 'classes/AIAnalyzer.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

// Get latest telemetry
$stmt = $pdo->query(
    "SELECT t.*, n.location_name 
     FROM telemetry_logs t 
     JOIN sensor_nodes n ON t.location_id = n.id 
     ORDER BY t.created_at DESC 
     LIMIT 10"
);
$telemetryList = $stmt->fetchAll();

// If no telemetry, create dummy for demo
if (empty($telemetryList)) {
    $telemetryList = [[
        'location_name' => 'Demo Location',
        'rainfall_mm' => 45.5,
        'humidity' => 85,
        'temperature' => 22,
        'pressure' => 1008,
        'wind_speed' => 8,
        'soil_moisture' => 75,
        'risk_score' => 0,
        'risk_level' => 'Low'
    ]];
}

// Calculate risk for each
foreach ($telemetryList as &$t) {
    $risk = RiskEngine::calculateRisk($t);
    $t['risk_score'] = $risk['score'];
    $t['risk_level'] = $risk['level'];
    if (!isset($aiResult) && $t === reset($telemetryList)) {
        $aiResult = AIAnalyzer::analyze($t);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Smart Slope V2 - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; background: #f8f9fa; }
        .card { margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .risk-low { color: #28a745; }
        .risk-moderate { color: #ffc107; }
        .risk-high { color: #fd7e14; }
        .risk-critical { color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">Smart Slope V2</a>
            <div class="navbar-nav">
                <a class="nav-link" href="import.php">Import CSV</a>
                <a class="nav-link" href="locations.php">Locations</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Landslide Risk Monitoring Dashboard</h1>
        <p class="text-muted">Baguio City, Philippines | Multi-AI Analysis System</p>

        <?php $latest = $telemetryList[0] ?? []; ?>
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5>Latest Readings - <?= htmlspecialchars($latest['location_name'] ?? 'N/A') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center">
                                    <div class="card-body"><h6>Rainfall</h6><p class="mb-0"><strong><?= $latest['rainfall_mm'] ?? 0 ?> mm</strong></p></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center">
                                    <div class="card-body"><h6>Humidity</h6><p class="mb-0"><strong><?= $latest['humidity'] ?? 0 ?>%</strong></p></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center">
                                    <div class="card-body"><h6>Temperature</h6><p class="mb-0"><strong><?= $latest['temperature'] ?? 0 ?>°C</strong></p></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center">
                                    <div class="card-body"><h6>Pressure</h6><p class="mb-0"><strong><?= $latest['pressure'] ?? 1013 ?> hPa</strong></p></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center">
                                    <div class="card-body"><h6>Wind</h6><p class="mb-0"><strong><?= $latest['wind_speed'] ?? 0 ?> km/h</strong></p></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <div class="card text-center">
                                    <div class="card-body"><h6>Soil Moisture</h6><p class="mb-0"><strong><?= $latest['soil_moisture'] ?? 0 ?>%</strong></p></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-header bg-dark text-white"><h5>Risk Level</h5></div>
                    <div class="card-body">
                        <h1 class="risk-<?= strtolower($latest['risk_level'] ?? 'low') ?>">
                            <?= htmlspecialchars($latest['risk_level'] ?? 'Low') ?></h1>
                        <p class="display-6"><?= $latest['risk_score'] ?? 0 ?>/100</p>
                        <div class="progress mt-3" style="height: 20px;">
                            <div class="progress-bar bg-<?= match($latest['risk_level'] ?? 'Low') {
                                'Low' => 'success', 'Moderate' => 'warning', 'High' => 'danger', 'Critical' => 'danger'
                            } ?>" style="width: <?= $latest['risk_score'] ?? 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white"><h5>AI Analysis</h5></div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Multi-provider fallback: Grok → Gemini → Groq → Mistral → Cerebras → Cloudflare → OpenRouter → Hugging Face → Default
                        </p>
                        <div id="ai-result" style="white-space: pre-wrap;">
                            <?= htmlspecialchars($aiResult['analysis'] ?? 'Loading AI analysis...') ?>
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            <strong>Provider:</strong> <?= htmlspecialchars($aiResult['provider'] ?? 'Default Engine') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white"><h5>Recent Telemetry</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>Location</th><th>Rainfall</th><th>Humidity</th><th>Risk Level</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($telemetryList as $t): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['location_name'] ?? 'N/A') ?></td>
                                        <td><?= $t['rainfall_mm'] ?> mm</td>
                                        <td><?= $t['humidity'] ?>%</td>
                                        <td class="risk-<?= strtolower($t['risk_level']) ?>"><strong><?= htmlspecialchars($t['risk_level']) ?></strong></td>
                                        <td><?= date('M d, H:i', strtotime($t['created_at'] ?? 'now')) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh AI every 30 seconds
        setInterval(() => {
            fetch('predict.php?action=ai', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ai&' + new URLSearchParams(<?= json_encode($latest) ?>)
            })
            .then(r => r.json())
            .then(result => {
                document.getElementById('ai-result').innerHTML = 
                    `<div style="white-space: pre-wrap;">${result.analysis}</div>` +
                    `<p class="text-muted small mt-2 mb-0"><strong>Provider:</strong> ${result.provider}</p>`;
            })
            .catch(() => {});
        }, 30000);
    </script>
</body>
</html>