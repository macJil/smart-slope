<?php
require_once __DIR__ . '/../config.php';

class SensorNode {
    private $conn;
    private $node_id;
    private $location_name;
    private $latitude;
    private $longitude;
    private $status;

    // Constructor
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // CREATE
    public function create($location_name, $latitude, $longitude, $status = 'active') {
        $stmt = $this->conn->prepare(
            "INSERT INTO sensor_nodes (location_name, latitude, longitude, status)
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$location_name, $latitude, $longitude, $status]);
    }

    // READ (all)
    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM sensor_nodes ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    // READ (one)
    public function getById($node_id) {
        $stmt = $this->conn->prepare("SELECT * FROM sensor_nodes WHERE node_id = ?");
        $stmt->execute([$node_id]);
        return $stmt->fetch();
    }

    // UPDATE
    public function update($node_id, $location_name, $latitude, $longitude, $status) {
        $stmt = $this->conn->prepare(
            "UPDATE sensor_nodes
             SET location_name = ?, latitude = ?, longitude = ?, status = ?
             WHERE node_id = ?"
        );
        return $stmt->execute([$location_name, $latitude, $longitude, $status, $node_id]);
    }

    // DELETE
    public function delete($node_id) {
        $stmt = $this->conn->prepare("DELETE FROM sensor_nodes WHERE node_id = ?");
        return $stmt->execute([$node_id]);
    }
}
?>