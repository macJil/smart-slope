<?php
// locations.php — CRUD for monitoring locations (sensor_nodes)
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/SensorNode.php';

$sensorNode = new SensorNode($conn);

// ── Handle POST actions ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' && isAdmin()) {
        $name   = trim($_POST['location_name'] ?? '');
        $lat    = (float) ($_POST['latitude'] ?? 0);
        $lon    = (float) ($_POST['longitude'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if ($name !== '') {
            $sensorNode->create($name, $lat, $lon, $status);
        }
        header('Location: locations.php');
        exit;
    }

    if ($action === 'update' && isAdmin()) {
        $id     = (int) ($_POST['node_id'] ?? 0);
        $name   = trim($_POST['location_name'] ?? '');
        $lat    = (float) ($_POST['latitude'] ?? 0);
        $lon    = (float) ($_POST['longitude'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if ($id > 0 && $name !== '') {
            $sensorNode->update($id, $name, $lat, $lon, $status);
        }
        header('Location: locations.php');
        exit;
    }

    if ($action === 'delete' && isAdmin()) {
        $id = (int) ($_POST['node_id'] ?? 0);
        if ($id > 0) {
            $sensorNode->delete($id);
        }
        header('Location: locations.php');
        exit;
    }
}

// ── Load data for display ───────────────────────────────
$nodes       = $sensorNode->getAll();
$editNode    = null;
$deleteNode  = null;

if (isset($_GET['edit'])) {
    $editNode = $sensorNode->getById((int) $_GET['edit']);
}
if (isset($_GET['delete'])) {
    $deleteNode = $sensorNode->getById((int) $_GET['delete']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Locations — Smart Slope V2</title>
    <link href="assests/css/bootstrap.min.css" rel="stylesheet">
    <link href="assests/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="index.php">Smart Slope V2</a>
            <div class="navbar-nav ms-auto align-items-lg-center">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link active" href="locations.php">Locations</a>
                <a class="nav-link" href="logout.php">Sign out</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="h3 mb-4">Monitoring Locations</h1>

        <?php if ($deleteNode): ?>
            <!-- ── Delete confirmation ──────────────────── -->
            <div class="card border-danger mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="text-danger">Confirm deletion</h5>
                    <p>Delete <strong><?= htmlspecialchars($deleteNode['location_name']) ?></strong>?
                    All telemetry records for this location will also be removed (ON DELETE CASCADE).</p>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="node_id" value="<?= (int) $deleteNode['node_id'] ?>">
                        <button type="submit" class="btn btn-danger">Yes, delete</button>
                        <a href="locations.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <!-- ── Add / Edit form ─────────────────────── -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= $editNode ? 'Edit location' : 'Add new location' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="<?= $editNode ? 'update' : 'create' ?>">
                        <?php if ($editNode): ?>
                            <input type="hidden" name="node_id" value="<?= (int) $editNode['node_id'] ?>">
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Location name</label>
                                <input type="text" name="location_name" class="form-control" required
                                       value="<?= $editNode ? htmlspecialchars($editNode['location_name']) : '' ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Latitude</label>
                                <input type="number" step="0.000001" name="latitude" class="form-control" required
                                       value="<?= $editNode ? htmlspecialchars($editNode['latitude']) : '' ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Longitude</label>
                                <input type="number" step="0.000001" name="longitude" class="form-control" required
                                       value="<?= $editNode ? htmlspecialchars($editNode['longitude']) : '' ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['active','inactive','maintenance'] as $s): ?>
                                        <option value="<?= $s ?>" <?= ($editNode && $editNode['status'] === $s) ? 'selected' : '' ?>>
                                            <?= ucfirst($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <?= $editNode ? 'Save' : 'Add' ?>
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php if ($editNode): ?>
                        <a href="locations.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel edit</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── Locations table ────────────────────────── -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Location</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nodes as $n): ?>
                            <tr>
                                <td><?= (int) $n['node_id'] ?></td>
                                <td><?= htmlspecialchars($n['location_name']) ?></td>
                                <td><?= htmlspecialchars($n['latitude']) ?></td>
                                <td><?= htmlspecialchars($n['longitude']) ?></td>
                                <td>
                                    <?php
                                        $badgeClass = match($n['status']) {
                                            'active'      => 'bg-success',
                                            'inactive'    => 'bg-secondary',
                                            'maintenance' => 'bg-warning text-dark',
                                            default       => 'bg-secondary',
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($n['status']) ?></span>
                                </td>
                                <td>
                                    <a href="fetch_weather.php?node_id=<?= (int) $n['node_id'] ?>"
                                       class="btn btn-outline-success btn-sm">Weather</a>
                                    <?php if (isAdmin()): ?>
                                        <a href="locations.php?edit=<?= (int) $n['node_id'] ?>"
                                           class="btn btn-outline-primary btn-sm">Edit</a>
                                        <a href="locations.php?delete=<?= (int) $n['node_id'] ?>"
                                           class="btn btn-outline-danger btn-sm">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="text-muted small mt-3">
            These are demonstration monitoring locations and should not be presented as
            actual official sensor deployments.
        </p>
    </div>

    <script src="assests/js/bootstrap.bundle.min.js"></script>
</body>
</html>