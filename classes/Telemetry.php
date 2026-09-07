<?php
require_once __DIR__ . '/../config.php';

class Telemetry {
    private $conn;

    // Constructor
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // CREATE (insert a new telemetry record)
    public function insert($node_id, $soil_moisture, $rainfall_mm, $humidity, $pressure, $temperature, $wind_speed, $source) {
        $stmt = $this->conn->prepare(
            "INSERT INTO telemetry_logs
             (node_id, soil_moisture, rainfall_mm, humidity, pressure, temperature, wind_speed, source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $node_id, $soil_moisture, $rainfall_mm, $humidity, $pressure,
            $temperature, $wind_speed, $source
        ]);
    }
    // UPDATE risk level for a stored telemetry record
    public function updateRiskLevel($log_id, $risk_level) {
        $stmt = $this->conn->prepare(
            "UPDATE telemetry_logs SET risk_level = ? WHERE log_id = ?"
        );
        return $stmt->execute([(int) $risk_level, (int) $log_id]);
    }

    // READ (latest N records)
    public function getLatest($limit = 50) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM telemetry_logs ORDER BY timestamp DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // READ (by node)
    public function getByNode($node_id, $limit = 50) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM telemetry_logs WHERE node_id = ? ORDER BY timestamp DESC LIMIT ?"
        );
        $stmt->bindValue(1, $node_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // DELETE (older than a date)
    public function deleteOlderThan($date) {
        $stmt = $this->conn->prepare("DELETE FROM telemetry_logs WHERE timestamp < ?");
        return $stmt->execute([$date]);
    }

    public function deleteImportedByNode($node_id) {
        $stmt = $this->conn->prepare(
            "DELETE FROM telemetry_logs WHERE node_id = ? AND source = 'CSV'"
        );
        return $stmt->execute([(int) $node_id]);
    }
}
?>