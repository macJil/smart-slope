<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/Telemetry.php';
require_once 'classes/SensorNode.php';

$sensorNode      = new SensorNode($conn);
$nodes           = $sensorNode->getAll();
$selectedNodeId  = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT);
$selectedNode    = $selectedNodeId ? $sensorNode->getById($selectedNodeId) : ($nodes[0] ?? null);

if (!$selectedNode) {
    http_response_code(500);
    exit('No monitoring locations are configured. Add one in Locations.');
}
$selectedNodeId = (int) $selectedNode['node_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Slope V2 — Dashboard</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="index.php">Smart Slope V2</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <div class="navbar-nav ms-auto align-items-lg-center">
                    <a class="nav-link active" href="index.php">Dashboard</a>
                    <a class="nav-link" href="locations.php">Locations</a>
                    <?php if (isStaff()): ?>
                        <a class="nav-link" href="import_csv.php?node_id=<?= $selectedNodeId ?>">Import</a>
                    <?php endif; ?>
                    <a class="nav-link" href="export_csv.php?node_id=<?= $selectedNodeId ?>">Export</a>
                    <?php if (isAdmin()): ?>
                        <a class="nav-link text-warning" href="clear_imported.php?node_id=<?= $selectedNodeId ?>">Clear</a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">Sign out</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Heading -->
        <div class="dashboard-heading mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-1">Operations dashboard</p>
                <h1 class="mb-1">Landslide Risk Monitoring</h1>
                <p class="text-muted mb-0" id="location-display"><?= htmlspecialchars($selectedNode['location_name']) ?></p>
            </div>
            <div class="text-md-end mt-3 mt-md-0">
                <div class="small text-muted">Data status</div>
                <div id="data-status" class="fw-semibold">Checking…</div>
                <div id="last-updated" class="small text-muted">—</div>
            </div>
        </div>

        <!-- Location selector + buttons -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <label for="location-select" class="form-label fw-semibold mb-1">Monitoring location</label>
                    <div class="small text-muted">Choose which location to view and update.</div>
                </div>
                <select id="location-select" class="form-select" style="max-width: 320px;">
                    <?php foreach ($nodes as $n): ?>
                        <option value="<?= (int) $n['node_id'] ?>" <?= (int) $n['node_id'] === $selectedNodeId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($n['location_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="d-flex gap-2">
                    <button type="button" id="refresh-dashboard" class="btn btn-outline-primary">
                        Refresh data
                    </button>
                    <button type="button" id="update-weather-btn" class="btn btn-success">
                        Update weather
                    </button>
                </div>
            </div>
        </div>

        <!-- Alert banner -->
        <div id="operational-alert" class="alert alert-secondary border mb-4" role="alert">
            <strong>Current alert:</strong> Waiting for the latest reading.
        </div>

        <!-- Weather cards -->
        <div class="row mb-4" id="weather-cards">
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-muted">Rainfall</div>
                        <h4 id="card-rainfall" class="mb-0">—</h4>
                        <small class="text-muted">mm</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-muted">Humidity</div>
                        <h4 id="card-humidity" class="mb-0">—</h4>
                        <small class="text-muted">%</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-muted">Temperature</div>
                        <h4 id="card-temperature" class="mb-0">—</h4>
                        <small class="text-muted">°C</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-muted">Pressure</div>
                        <h4 id="card-pressure" class="mb-0">—</h4>
                        <small class="text-muted">hPa</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-muted">Wind Speed</div>
                        <h4 id="card-wind" class="mb-0">—</h4>
                        <small class="text-muted">km/h</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-muted">Soil Moisture</div>
                        <h4 id="card-soil" class="mb-0">—</h4>
                        <small class="text-muted">%</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Risk gauge + AI analysis -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Risk Assessment</h5>
                    </div>
                    <div class="card-body text-center">
                        <canvas id="risk-gauge" style="max-width:200px;"></canvas>
                        <h3 id="risk-score-text" class="mt-2 mb-0">—</h3>
                        <h4 id="risk-level-text" class="mb-2">Waiting</h4>
                        <p id="risk-guidance" class="text-muted small mb-0">
                            The latest reading will appear here.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">AI Analysis <small class="text-light fs-6">Multi-provider</small></h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">
                            Automatically generated from the latest readings using an 8-provider
                            AI fallback system: Grok → Gemini → Groq → Mistral → Cerebras →
                            Cloudflare → OpenRouter → Hugging Face → Default Engine.
                            The first available provider responds; if it fails, the next takes over.
                        </p>
                        <div id="ai-result">
                            <p class="text-muted mb-0">Waiting for data…</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual prediction form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Manual presentation mode</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Use this form to demonstrate a hypothetical weather scenario.
                    The system calculates the risk score using the same RiskEngine.
                </p>
                <form id="prediction-form" class="row g-3">
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label" for="rainfall_mm">Rainfall (mm)</label>
                        <input type="number" class="form-control" id="rainfall_mm" step="0.1" value="50" required>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label" for="humidity">Humidity (%)</label>
                        <input type="number" class="form-control" id="humidity" step="0.1" value="75" required>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label" for="pressure">Pressure (hPa)</label>
                        <input type="number" class="form-control" id="pressure" step="0.1" value="1012" required>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label" for="temperature">Temp (°C)</label>
                        <input type="number" class="form-control" id="temperature" step="0.1" value="21" required>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label" for="wind_speed">Wind (km/h)</label>
                        <input type="number" class="form-control" id="wind_speed" step="0.1" value="5" required>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label class="form-label" for="soil_moisture">Soil (%)</label>
                        <input type="number" class="form-control" id="soil_moisture" step="0.1" value="0">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Assess risk</button>
                    </div>
                </form>
                <div id="prediction-result" class="mt-3"></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Risk history</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="risk-history-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Rainfall over time</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="rainfall-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Temperature and humidity</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="temp-humidity-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest readings table -->
        <div class="card shadow mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Latest readings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Time</th>
                                <th>Rainfall</th>
                                <th>Humidity</th>
                                <th>Temp</th>
                                <th>Pressure</th>
                                <th>Wind</th>
                                <th>Soil</th>
                                <th>Score</th>
                                <th>Level</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody id="data-table"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="text-muted small">
            Smart Slope V2 is an educational prototype. The risk score is a simplified
            environmental model, not a scientifically validated landslide prediction.
        </p>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-4">
        <p class="mb-0">Smart Slope V2 · Baguio City</p>
    </footer>

    <script src="assests/js/jquery-3.7.1.min.js"></script>
    <script src="assests/js/chart.umd.min.js"></script>
    <script>
        window.smartSlopeNodeId = <?= $selectedNodeId ?>;
        window.csrfToken = '<?= csrfToken() ?>';
    </script>
    <script src="assests/js/script.js"></script>
    <script src="assests/js/bootstrap.bundle.min.js"></script>
</body>
</html>