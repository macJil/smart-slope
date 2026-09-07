<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/SensorNode.php';
require_once 'classes/Telemetry.php';

$node_id = filter_input(INPUT_POST, 'node_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT)
    ?: 1;
$node = (new SensorNode($conn))->getById($node_id);
if (!$node) {
    http_response_code(400);
    exit('Invalid location.');
}

$deleted = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telemetry = new Telemetry($conn);
    $before = $conn->prepare("SELECT COUNT(*) FROM telemetry_logs WHERE node_id = ? AND source = 'CSV'");
    $before->execute([$node_id]);
    $deleted = (int) $before->fetchColumn();
    $telemetry->deleteImportedByNode($node_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Remove Imported Data — Smart Slope</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="mx-auto" style="max-width: 620px;">
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="alert alert-success shadow-sm">
                    <h1 class="h4">Imported data removed</h1>
                    <p><?= $deleted ?> imported record(s) were removed from <?= htmlspecialchars($node['location_name']) ?>.</p>
                    <a class="btn btn-success" href="index.php?node_id=<?= (int) $node_id ?>">Refresh dashboard</a>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h4">Remove imported data</h1>
                        <p>This removes only CSV-imported records for <strong><?= htmlspecialchars($node['location_name']) ?></strong>. Live API weather records will not be deleted.</p>
                        <form method="POST">
                            <input type="hidden" name="node_id" value="<?= (int) $node_id ?>">
                            <button type="submit" class="btn btn-danger">Remove imported data</button>
                            <a class="btn btn-outline-secondary" href="index.php?node_id=<?= (int) $node_id ?>">Cancel</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
