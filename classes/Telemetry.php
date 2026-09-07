<?php
require_once __DIR__ . '/../config.php';

class Telemetry {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Create: insert a telemetry record with risk level
    public function insert($node_id, $soil_moisture, $rainfall_mm, $humidity,
                           $pressure, $temperature, $wind_speed, $source, $risk_level = 0) {
        $stmt = $this->conn->prepare(
            "INSERT INTO telemetry_logs
             (node_id, soil_moisture, rainfall_mm, humidity, pressure,
              temperature, wind_speed, risk_level, source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $node_id, $soil_moisture, $rainfall_mm, $humidity,
            $pressure, $temperature, $wind_speed, $risk_level, $source
        ]);
    }

    // Read: get latest records for a specific location
    public function getByNode($node_id, $limit = 50) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM telemetry_logs WHERE node_id = ? ORDER BY timestamp DESC LIMIT ?"
        );
        $stmt->bindValue(1, $node_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Delete: remove CSV-imported records for a location (keeps API/ESP32 data)
    public function deleteImportedByNode($node_id) {
        $stmt = $this->conn->prepare(
            "DELETE FROM telemetry_logs WHERE node_id = ? AND source = 'CSV'"
        );
        return $stmt->execute([$node_id]);
    }
}