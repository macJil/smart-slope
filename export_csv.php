<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/SensorNode.php';

$node_id = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT);
$node = $node_id ? (new SensorNode($conn))->getById($node_id) : null;
$filename = $node
    ? 'telemetry_' . preg_replace('/[^a-z0-9]+/i', '_', $node['location_name']) . '_' . date('Y-m-d_His') . '.csv'
    : 'telemetry_all_locations_' . date('Y-m-d_His') . '.csv';

// Set headers to trigger a file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// CSV header row
fputcsv($output, ['Log ID', 'Node ID', 'Soil Moisture', 'Rainfall (mm)', 'Humidity (%)', 'Pressure (hPa)', 'Temperature (C)', 'Wind Speed (m/s)', 'Source', 'Timestamp']);

// Fetch all telemetry data
$sql = "SELECT * FROM telemetry_logs";
if ($node) {
    $stmt = $conn->prepare($sql . " WHERE node_id = ? ORDER BY timestamp DESC");
    $stmt->execute([$node_id]);
} else {
    $stmt = $conn->query($sql . " ORDER BY timestamp DESC");
}
while ($row = $stmt->fetch()) {
    fputcsv($output, $row);
}

fclose($output);
?>