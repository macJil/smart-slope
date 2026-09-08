<?php
// locations.php - Full CRUD for sensor locations
require 'auth.php';
require 'config.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ADD location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $stmt = $pdo->prepare("INSERT INTO sensor_nodes (location_name, latitude, longitude, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['location_name'],
        (float)$_POST['latitude'],
        (float)$_POST['longitude'],
        $_POST['status'] ?? 'active'
    ]);
    header('Location: locations.php');
    exit;
}

// UPDATE location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_location'])) {
    $stmt = $pdo->prepare("UPDATE sensor_nodes SET location_name = ?, latitude = ?, longitude = ?, status = ? WHERE node_id = ?");
    $stmt->execute([
        $_POST['location_name'],
        (float)$_POST['latitude'],
        (float)$_POST['longitude'],
        $_POST['status'],
        $_POST['location_id']
    ]);
    header('Location: locations.php');
    exit;
}

// DELETE location
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM sensor_nodes WHERE node_id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: locations.php');
    exit;
}

// EDIT mode - get location to edit
$editLoc = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM sensor_nodes WHERE node_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editLoc = $stmt->fetch();
}

$locations = $pdo->query("SELECT * FROM sensor_nodes ORDER BY location_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locations - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?= APP_NAME ?></a>
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Sensor Locations Management</h2>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <!-- ADD/EDIT FORM -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><?= $editLoc ? 'Edit Location' : 'Add New Location' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="location_id" value="<?= $editLoc['node_id'] ?? '' ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Location Name *</label>
                        <input type="text" name="location_name" class="form-control" 
                               value="<?= htmlspecialchars($editLoc['location_name'] ?? '') ?>" 
                               placeholder="e.g., Barangay Loay, Baguio City" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Latitude *</label>
                            <input type="number" step="0.000001" name="latitude" class="form-control" 
                                   value="<?= $editLoc['latitude'] ?? '' ?>" 
                                   placeholder="e.g., 16.4173" required>
                            <small class="text-muted">Decimal degrees (e.g., 16.4173)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitude *</label>
                            <input type="number" step="0.000001" name="longitude" class="form-control" 
                                   value="<?= $editLoc['longitude'] ?? '' ?>" 
                                   placeholder="e.g., 120.5963" required>
                            <small class="text-muted">Decimal degrees (e.g., 120.5963)</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($editLoc['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editLoc['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="maintenance" <?= ($editLoc['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="<?= $editLoc ? 'update_location' : 'add_location' ?>" 
                            class="btn btn-primary">
                        <?= $editLoc ? 'Update' : 'Add' ?> Location
                    </button>
                    <?php if ($editLoc): ?>
                        <a href="locations.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- LIST TABLE -->
        <div class="card">
            <div class="card-header">
                <h5>All Locations (<?= count($locations) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($locations)): ?>
                    <p class="text-muted">No locations yet. Add one above.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Location Name</th>
                                    <th>Coordinates</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($locations as $loc): ?>
                                <tr>
                                    <td><?= $loc['node_id'] ?></td>
                                    <td><?= htmlspecialchars($loc['location_name']) ?></td>
                                    <td>
                                        <?= $loc['latitude'] ?>, <?= $loc['longitude'] ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= match($loc['status']) {
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'maintenance' => 'warning'
                                        } ?>">
                                            <?= htmlspecialchars($loc['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $loc['node_id'] ?>" class="btn btn-sm btn-warning">
                                            Edit
                                        </a>
                                        <a href="?delete=<?= $loc['node_id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this location? All its telemetry data will be deleted due to CASCADE.')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>