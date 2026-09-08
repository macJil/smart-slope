<?php
// data.php — JSON telemetry endpoint (called by AJAX on the dashboard)

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

require_once 'config.php';
require_once 'classes/Telemetry.php';
require_once 'classes/SensorNode.php';

header('Content-Type: application/json');

$node_id = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT);
$node    = $node_id ? (new SensorNode($conn))->getById($node_id) : null;

if (!$node) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Select a valid monitoring location.']);
    exit;
}

$telemetry = new Telemetry($conn);
$records   = $telemetry->getByNode($node_id, 50);

echo json_encode([
    'success' => true,
    'node' => [
        'node_id'       => (int) $node['node_id'],
        'location_name' => $node['location_name'],
        'latitude'      => (float) $node['latitude'],
        'longitude'     => (float) $node['longitude'],
    ],
    'data' => $records,
]);