<?php
// locations.php - Full CRUD for sensor locations
require 'auth.php';
require 'config.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

// ADD location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $stmt = $pdo->prepare("INSERT INTO sensor_nodes (location_name) VALUES (?)");
    $stmt->execute([$_POST['location_name']]);
    header('Location: locations.php');
    exit;
}

// UPDATE location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_location'])) {
    $stmt = $pdo->prepare("UPDATE sensor_nodes SET location_name = ? WHERE id = ?");
    $stmt->execute([$_POST['location_name'], $_POST['location_id']]);
    header('Location: locations.php');
    exit;
}

// DELETE location
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM sensor_nodes WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: locations.php');
    exit;
}

// EDIT mode - get location to edit
$editLoc = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM sensor_nodes WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editLoc = $stmt->fetch();
}

$locations = $pdo->query("SELECT * FROM sensor_nodes ORDER BY location_name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Locations - Smart Slope</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Sensor Locations</h2>
    
    <!-- ADD FORM -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><?= $editLoc ? 'Edit Location' : 'Add New Location' ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="location_id" value="<?= $editLoc['id'] ?? '' ?>">
                <div class="mb-3">
                    <label class="form-label">Location Name</label>
                    <input type="text" name="location_name" class="form-control" 
                           value="<?= htmlspecialchars($editLoc['location_name'] ?? '') ?>" 
                           placeholder="Location name" required>
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
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Location Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                        <tr>
                            <td><?= $loc['id'] ?></td>
                            <td><?= htmlspecialchars($loc['location_name']) ?></td>
                            <td>
                                <a href="?edit=<?= $loc['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="?delete=<?= $loc['id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Delete this location? All its telemetry data will be deleted.')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>