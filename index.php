<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/Telemetry.php';
require_once 'classes/SensorNode.php';

$sensorNode = new SensorNode($conn);
$nodes = $sensorNode->getAll();
$selectedNodeId = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT);
$selectedNode = $selectedNodeId ? $sensorNode->getById($selectedNodeId) : ($nodes[0] ?? null);
if (!$selectedNode) {
    http_response_code(500);
    exit('No monitoring locations are configured.');
}
$selectedNodeId = (int) $selectedNode['node_id'];

$telemetry = new Telemetry($conn);
$latest = $telemetry->getByNode($selectedNodeId, 50);
$latest = array_reverse($latest); // oldest first for charts
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Slope — Barangay Loay Dashboard</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="index.php">Smart Slope</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-navigation" aria-controls="main-navigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-navigation">
                <div class="navbar-nav ms-auto align-items-lg-center">
                <a class="nav-link active" href="index.php">Dashboard</a>
                <a class="nav-link" href="import_csv.php?node_id=<?= $selectedNodeId ?>">Upload records</a>
                <a class="nav-link" href="export_csv.php?node_id=<?= $selectedNodeId ?>">Download report</a>
                <a class="nav-link" href="clear_imported.php?node_id=<?= $selectedNodeId ?>">Clear imported data</a>
                <a class="nav-link" id="update-weather-link" href="fetch_weather.php?node_id=<?= $selectedNodeId ?>">Update weather</a>
                <a class="nav-link" href="logout.php">Sign out</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="dashboard-heading mb-4">
            <div>
                <p class="text-uppercase small fw-semibold text-primary mb-1">Operations dashboard</p>
                <h1 class="mb-1">Landslide Risk Monitoring</h1>
                <p class="text-muted mb-0">Select a monitoring location below</p>
            </div>
            <div class="text-md-end mt-3 mt-md-0">
                <div class="small text-muted">Data status</div>
                <div id="data-status" class="fw-semibold">Checking latest readings...</div>
                <div id="last-updated" class="small text-muted">—</div>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <label for="location-select" class="form-label fw-semibold mb-1">Monitoring location</label>
                    <div class="small text-muted">Choose which location to view and update.</div>
                </div>
                <select id="location-select" class="form-select" style="max-width: 360px;" aria-label="Monitoring location">
                    <?php foreach ($nodes as $node): ?>
                        <option value="<?= (int) $node['node_id'] ?>" <?= (int) $node['node_id'] === $selectedNodeId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($node['location_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="refresh-dashboard" class="btn btn-outline-primary">Refresh data</button>
            </div>
        </div>

        <div id="operational-alert" class="alert alert-secondary border mb-4" role="alert">
            <strong>Current alert:</strong> Waiting for the latest reading.
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Manual presentation mode</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Use this optional form to demonstrate a hypothetical weather scenario. Normal monitoring uses automatic readings.</p>
                <form id="prediction-form" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label" for="rainfall_mm">Rainfall (mm)</label>
                        <input type="number" class="form-control" id="rainfall_mm" step="0.1" value="50" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="humidity">Humidity (%)</label>
                        <input type="number" class="form-control" id="humidity" step="0.1" value="75" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="pressure">Air pressure (hPa)</label>
                        <input type="number" class="form-control" id="pressure" step="0.1" value="1012" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="temperature">Temperature (C)</label>
                        <input type="number" class="form-control" id="temperature" step="0.1" value="21" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="wind_speed">Wind speed (m/s)</label>
                        <input type="number" class="form-control" id="wind_speed" step="0.1" value="3" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Assess risk</button>
                    </div>
                </form>
                <div id="prediction-result" class="mt-3"></div>
            </div>
        </div>

        <!-- Risk Gauge -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Current risk level</h5>
                    </div>
                    <div class="card-body text-center">
                        <canvas id="risk-gauge" style="max-width:250px;"></canvas>
                        <h3 id="risk-level" class="mt-2">Waiting for data</h3>
                        <p id="risk-guidance" class="text-muted mb-0">The latest reading will appear here.</p>
                    </div>
                </div>
            </div>
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
        </div>

        <!-- Weather Charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4 shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Rainfall over time</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="rainfall-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4 shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Temperature and humidity</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="temp-humidity-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Latest readings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Date and time</th>
                                <th>Soil moisture</th>
                                <th>Rainfall (mm)</th>
                                <th>Humidity (%)</th>
                                <th>Pressure</th>
                                <th>Temperature (°C)</th>
                                <th>Source</th>
                                <th>Risk</th>
                            </tr>
                        </thead>
                        <tbody id="data-table">
                            <!-- Populated by AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-4">
        <p class="mb-0">Smart Slope · Barangay Loay, Baguio City</p>
    </footer>

    <script src="assests/js/jquery-3.7.1.min.js"></script>
    <script src="assests/js/chart.umd.min.js"></script>
    <script>
        window.smartSlopeNodeId = <?= $selectedNodeId ?>;
    </script>
    <script src="assests/js/script.js"></script>
    <script src="assests/js/bootstrap.bundle.min.js"></script>
</body>
</html>