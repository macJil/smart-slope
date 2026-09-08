<?php
// clear_telemetry.php - Remove telemetry for one selected location.
require 'auth.php';
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use POST to clear telemetry.');
}

$nodeId = filter_input(INPUT_POST, 'node_id', FILTER_VALIDATE_INT);
if (!$nodeId) {
    http_response_code(400);
    exit('Invalid location.');
}

$stmt = $conn->prepare('DELETE FROM telemetry_logs WHERE node_id = ?');
$stmt->execute([$nodeId]);

header('Location: index.php?node_id=' . $nodeId . '&cleared=1');
exit;
