<?php
require_once __DIR__ . '/../config.php';

class SensorNode {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Get all monitoring locations (READ)
    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM sensor_nodes ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    // Get one location by ID (READ)
    public function getById($node_id) {
        $stmt = $this->conn->prepare("SELECT * FROM sensor_nodes WHERE node_id = ?");
        $stmt->execute([$node_id]);
        return $stmt->fetch();
    }
}