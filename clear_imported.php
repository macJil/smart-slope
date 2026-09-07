<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/Telemetry.php';
require_once 'classes/SensorNode.php';

$node_id = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT) ?: 1;
$node = (new SensorNode($conn))->getById($node_id);
if (!$node) {
    http_response_code(400);
    exit('Invalid location.');
}

$deleted = false;
if (filter_input(INPUT_GET, 'confirm', FILTER_VALIDATE_INT) === 1) {
    $deleted = (new Telemetry($conn))->deleteImportedByNode($node_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clear Imported Data — Smart Slope</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="mx-auto" style="max-width: 620px;">
            <h1 class="h3 mb-3">Clear Imported Data</h1>
            <p>Location: <strong><?= htmlspecialchars($node['location_name']) ?></strong></p>
            <?php if ($deleted): ?>
                <div class="alert alert-success">Imported (CSV) records removed. Live API readings are preserved.</div>
                <a href="index.php?node_id=<?= (int) $node_id ?>" class="btn btn-success">Back to Dashboard</a>
            <?php else: ?>
                <div class="alert alert-warning">
                    This removes only CSV-imported records for this location. Live API data will remain.
                </div>
                <a href="clear_imported.php?node_id=<?= (int) $node_id ?>&confirm=1"
                   class="btn btn-danger">Yes, remove imported data</a>
                <a href="index.php?node_id=<?= (int) $node_id ?>" class="btn btn-outline-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>