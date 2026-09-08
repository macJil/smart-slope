<?php
// import.php - CSV import for telemetry data
require 'auth.php';
require 'config.php';
require 'classes/RiskEngine.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    // Skip header row if present
    fgetcsv($handle);
    
    $imported = 0;
    while (($row = fgetcsv($handle)) !== false) {
        // CSV format: node_id,soil_moisture,rainfall_mm,humidity,pressure,temperature,wind_speed,source,timestamp
        $nodeId = (int)($row[0] ?? 0);
        $soil = ($row[1] ?? '') !== '' ? (float)$row[1] : null;
        $rainfall = (float)($row[2] ?? 0);
        $humidity = (float)($row[3] ?? 0);
        $pressure = (float)($row[4] ?? 1013);
        $temperature = (float)($row[5] ?? 0);
        $wind = (float)($row[6] ?? 0);
        $source = in_array(($row[7] ?? 'CSV'), ['API', 'ESP32', 'CSV'], true) ? $row[7] : 'CSV';
        $timestamp = $row[8] ?? date('Y-m-d H:i:s');
        $risk = RiskEngine::calculate($rainfall, $humidity, $soil, $pressure, $temperature, $wind);

        $stmt = $conn->prepare(
            "INSERT INTO telemetry_logs 
            (node_id, soil_moisture, rainfall_mm, humidity, pressure, temperature, wind_speed, risk_score, risk_level, source, timestamp, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $nodeId, $soil, $rainfall, $humidity, $pressure, $temperature,
            $wind, $risk['score'], $risk['level'], $source, $timestamp
        ]);
        $imported++;
    }
    fclose($handle);
    $message = "Successfully imported $imported records!";
}
?>
<?php require 'auth.php'; // This will redirect if not logged in - but we already have it at top ?>
<!DOCTYPE html>
<html>
<head>
    <title>Import CSV - Smart Slope</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Import CSV Data</h2>
    <p class="text-muted">Upload CSV with columns: node_id,soil_moisture,rainfall_mm,humidity,pressure,temperature,wind_speed,source,timestamp</p>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <button type="submit" class="btn btn-primary">Import CSV</button>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>
</body>
</html>