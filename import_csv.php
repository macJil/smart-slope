<?php
// import.php - CSV import for telemetry data
require 'auth.php';
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    // Skip header row if present
    fgetcsv($handle);
    
    $imported = 0;
    while (($row = fgetcsv($handle)) !== false) {
        // CSV format: location_name,rainfall_mm,humidity,temperature,pressure,wind_speed,soil_moisture
        $stmt = $pdo->prepare(
            "INSERT INTO telemetry_logs 
            (location_name, rainfall_mm, humidity, temperature, pressure, wind_speed, soil_moisture, risk_score, risk_level, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'Low', NOW())"
        );
        $stmt->execute([
            $row[0] ?? 'Unknown',
            (float)($row[1] ?? 0),
            (float)($row[2] ?? 0),
            (float)($row[3] ?? 0),
            (float)($row[4] ?? 1013),
            (float)($row[5] ?? 0),
            (float)($row[6] ?? 0)
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
    <p class="text-muted">Upload CSV file with columns: location_name,rainfall_mm,humidity,temperature,pressure,wind_speed,soil_moisture</p>
    
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