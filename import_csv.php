<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/SensorNode.php';

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
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    // Skip the header row
    fgetcsv($handle);

    $count = 0;
    // Prepared statement (prevents SQL Injection)
    $stmt = $conn->prepare(
        "INSERT INTO telemetry_logs
         (node_id, soil_moisture, rainfall_mm, humidity, pressure, temperature, wind_speed, source, timestamp)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    while (($row = fgetcsv($handle)) !== false) {
        $stmt->execute([
            $node_id,
            $row[1] !== '' ? $row[1] : null,
            $row[2],
            $row[3],
            $row[4],
            $row[5],
            $row[6],
            'CSV',
            $row[8]
        ]);
        $count++;
    }

    fclose($handle);
    $message = "Successfully imported $count records into " . $node['location_name'] . ".";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="node_id" value="<?= (int) $node_id ?>">
            <div class="mb-3">
                <label class="form-label">Select CSV File</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary">Import CSV</button>
            <a href="index.php?node_id=<?= (int) $node_id ?>" class="btn btn-outline-secondary">Refresh dashboard</a>
            <a href="clear_imported.php?node_id=<?= (int) $node_id ?>" class="btn btn-outline-danger">Remove imported data</a>
        </form>
        <hr>
        <p class="text-muted"><small>
            <strong>CSV column order:</strong>
            node_id, soil_moisture, rainfall_mm, humidity, pressure, temperature, wind_speed, source, timestamp<br>
            Uploaded rows are assigned to the selected monitoring location: <strong><?= htmlspecialchars($node['location_name']) ?></strong>.<br>
            Leave soil_moisture empty for Phase 1 (API data only).
        </small></p>
    </div>
    </main>
</body>
</html>