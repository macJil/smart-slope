<?php
// import_csv.php — Import telemetry from CSV with RiskEngine risk calculation
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/SensorNode.php';
require_once 'classes/Telemetry.php';
require_once 'classes/RiskEngine.php';

$message  = '';
$errorMsg = '';
$node_id  = filter_input(INPUT_POST, 'node_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT)
    ?: 1;
$node     = (new SensorNode($conn))->getById($node_id);

if (!$node) {
    http_response_code(400);
    exit('Invalid import location.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    verifyCsrf();

    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'File upload failed. Please try again.';
    } elseif ($file['size'] > 2_000_000) {
        $errorMsg = 'File is too large. Maximum 2 MB.';
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        fgetcsv($handle); // skip header row

        $count    = 0;
        $telemetry = new Telemetry($conn);

        while (($row = fgetcsv($handle)) !== false) {
            // Columns: node_id, soil_moisture, rainfall_mm, humidity,
            //          pressure, temperature, wind_speed, source, timestamp
            $soil = ($row[1] ?? '') !== '' ? (float) $row[1] : null;

            $risk = RiskEngine::calculate(
                (float) $row[2],   // rainfall
                (float) $row[3],   // humidity
                $soil,
                (float) $row[4],   // pressure
                (float) $row[5],   // temperature
                (float) $row[6]    // wind_speed
            );

            $telemetry->insert(
                $node_id,
                $soil,
                (float) $row[2],   // rainfall
                (float) $row[3],   // humidity
                (float) $row[4],   // pressure
                (float) $row[5],   // temperature
                (float) $row[6],   // wind_speed
                $risk['score'],
                $risk['level'],
                'CSV',
                $row[8] ?? null     // timestamp
            );
            $count++;
        }
        fclose($handle);
        $message = "Successfully imported $count records into "
                 . htmlspecialchars($node['location_name']) . ".";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import CSV — Smart Slope V2</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="index.php">Smart Slope V2</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="locations.php">Locations</a>
                <a class="nav-link active" href="#">Import</a>
                <a class="nav-link" href="logout.php">Sign out</a>
            </div>
        </div>
    </nav>
    <main class="container py-4">
    <div class="mx-auto" style="max-width: 680px;">
        <h1 class="h3 mb-4">Import Telemetry</h1>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="node_id" value="<?= (int) $node_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <div class="form-control-plaintext">
                            <strong><?= htmlspecialchars($node['location_name']) ?></strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select CSV file</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Import CSV</button>
                    <a href="index.php?node_id=<?= (int) $node_id ?>" class="btn btn-outline-secondary">Back</a>
                </form>
            </div>
        </div>
        <hr>
        <p class="text-muted"><small>
            <strong>CSV column order:</strong><br>
            node_id, soil_moisture, rainfall_mm, humidity, pressure,
            temperature, wind_speed, source, timestamp
        </small></p>
        <p class="text-muted"><small>
            Risk score and level are calculated automatically by the RiskEngine.
            Do not include risk values in the CSV.
        </small></p>
    </div>
    </main>
</body>
</html>