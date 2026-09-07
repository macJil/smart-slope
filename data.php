<?php
require_once 'config.php';
require_once 'classes/Telemetry.php';
require_once 'classes/SensorNode.php';
header('Content-Type: application/json');

$node_id = filter_input(INPUT_GET, 'node_id', FILTER_VALIDATE_INT);
$node = $node_id ? (new SensorNode($conn))->getById($node_id) : null;
if (!$node) {
	http_response_code(400);
	echo json_encode(['error' => 'Select a valid monitoring location.']);
	exit;
}

$telemetry = new Telemetry($conn);
$latest = $telemetry->getByNode($node_id, 50);

echo json_encode($latest);
$conn = null;
?>