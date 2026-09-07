<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/SensorNode.php';
require_once 'classes/Prediction.php';

$message = '';
$node_id = filter_input(INPUT_POST, 'node_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT)
    ?: 1;
$node = (new SensorNode($conn))->getById($node_id);
if (!$node) {
    http_response_code(400);
    exit('Invalid import location.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    fgetcsv($handle); // skip header row

    $count = 0;
    $stmt = $conn->prepare(
        "INSERT INTO telemetry_logs
         (node_id, soil_moisture, rainfall_mm, humidity, pressure,
          temperature, wind_speed, risk_level, source, timestamp)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    while (($row = fgetcsv($handle)) !== false) {
        // Run AI prediction on the CSV row's weather data
        $pred = Prediction::run($row[2], $row[3], $row[4], $row[5], $row[6]);
        $risk = $pred['prediction'];

        $stmt->execute([
            $node_id,
            $row[1] !== '' ? $row[1] : null,  // soil_moisture
            $row[2],                           // rainfall_mm
            $row[3],                           // humidity
            $row[4],                           // pressure
            $row[5],                           // temperature
            $row[6],                           // wind_speed
            $risk,                             // risk_level (AI computed)
            'CSV',                             // source
            $row[8]                            // timestamp
        ]);
        $count++;
    }

    fclose($handle);
    $message = "Successfully imported $count records into " . htmlspecialchars($node['location_name']) . ".";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import CSV — Smart Slope</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
    <div class="mx-auto" style="max-width: 680px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Import Telemetry</h1>
            <a href="index.php?node_id=<?= (int) $node_id ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="node_id" value="<?= (int) $node_id ?>">
            <div class="mb-3">
                <label class="form-label">Select CSV File</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary">Import CSV</button>
            <a href="index.php?node_id=<?= (int) $node_id ?>" class="btn btn-outline-secondary">Back</a>
            <a href="clear_imported.php?node_id=<?= (int) $node_id ?>" class="btn btn-outline-danger">Remove imported data</a>
        </form>
        <hr>
        <p class="text-muted"><small>
            <strong>CSV column order:</strong>
            node_id, soil_moisture, rainfall_mm, humidity, pressure, temperature, wind_speed, source, timestamp
        </small></p>
    </div>
    </main>
</body>
</html>