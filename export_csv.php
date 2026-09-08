<?php
// export_csv.php — Export telemetry to CSV download
require_once 'auth.php';
require_once 'config.php';
require_once 'classes/SensorNode.php';

$node_id  = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT);
$node     = $node_id ? (new SensorNode($conn))->getById($node_id) : null;
$filename = $node
    ? 'telemetry_' . preg_replace('/[^a-z0-9]+/i', '_', $node['location_name']) . '_' . date('Y-m-d_His') . '.csv'
    : 'telemetry_all_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'Log ID', 'Node ID', 'Location', 'Soil Moisture', 'Rainfall (mm)',
    'Humidity (%)', 'Pressure (hPa)', 'Temperature (C)', 'Wind Speed (km/h)',
    'Risk Score', 'Risk Level', 'Source', 'Timestamp'
]);

$sql = "SELECT t.*, s.location_name
        FROM telemetry_logs t
        JOIN sensor_nodes s ON t.node_id = s.node_id";

if ($node) {
    $stmt = $conn->prepare($sql . " WHERE t.node_id = ? ORDER BY t.timestamp DESC");
    $stmt->execute([$node_id]);
} else {
    $stmt = $conn->query($sql . " ORDER BY t.timestamp DESC");
}

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['log_id'], $row['node_id'], $row['location_name'],
        $row['soil_moisture'], $row['rainfall_mm'], $row['humidity'],
        $row['pressure'], $row['temperature'], $row['wind_speed'],
        $row['risk_score'], $row['risk_level'], $row['source'],
        $row['timestamp']
    ]);
}
fclose($output);