<?php
require 'config.php';
require 'classes/AIAnalyzer.php';

$input = $_POST;

// Risk calculation endpoint
if (($input['action'] ?? '') === 'risk') {
    require 'classes/RiskEngine.php';
    $result = RiskEngine::calculateRisk($input);
    echo json_encode($result);
    exit;
}

// AI analysis endpoint
if (($input['action'] ?? '') === 'ai') {
    $result = AIAnalyzer::analyze($input);
    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);