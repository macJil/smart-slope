<?php
// predict.php - Risk calculation and AI analysis endpoints
require 'config.php';
require 'classes/RiskEngine.php';
require 'classes/AIAnalyzer.php';

$input = $_POST;
if ($input === []) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonInput)) {
        $input = $jsonInput;
    }
}

// Risk calculation endpoint
if (($input['action'] ?? '') === 'risk') {
    $result = RiskEngine::calculateRisk($input);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// AI analysis endpoint
if (($input['action'] ?? '') === 'ai') {
    $result = AIAnalyzer::analyze($input);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Invalid action
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid action. Use action=risk or action=ai']);