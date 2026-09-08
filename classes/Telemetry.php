<?php
// classes/Telemetry.php
// Insert and read telemetry records.

require_once __DIR__ . '/../config.php';

class Telemetry
{
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * CREATE — insert a telemetry record with risk score and level.
     * $timestamp is optional (null = use DB default, i.e. now).
     */
    public function insert(
        int    $node_id,
        ?float $soil_moisture,
        float  $rainfall_mm,
        ?float $humidity,
        ?float $pressure,
        ?float $temperature,
        ?float $wind_speed,
        int    $risk_score,
        string $risk_level,
        string $source,
        ?string $timestamp = null
    ): bool {
        if ($timestamp !== null) {
            $sql = 'INSERT INTO telemetry_logs
                    (node_id, soil_moisture, rainfall_mm, humidity, pressure,
                     temperature, wind_speed, risk_score, risk_level, source, timestamp)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $params = [$node_id, $soil_moisture, $rainfall_mm, $humidity, $pressure,
                       $temperature, $wind_speed, $risk_score, $risk_level, $source, $timestamp];
        } else {
            $sql = 'INSERT INTO telemetry_logs
                    (node_id, soil_moisture, rainfall_mm, humidity, pressure,
                     temperature, wind_speed, risk_score, risk_level, source)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $params = [$node_id, $soil_moisture, $rainfall_mm, $humidity, $pressure,
                       $temperature, $wind_speed, $risk_score, $risk_level, $source];
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /** READ — get latest N records for a location (newest first). */
    public function getByNode(int $node_id, int $limit = 50): array {
        $stmt = $this->conn->prepare(
            'SELECT * FROM telemetry_logs
             WHERE node_id = ? ORDER BY timestamp DESC LIMIT ?'
        );
        $stmt->bindValue(1, $node_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** DELETE — remove CSV-imported records for a location (keeps API/ESP32 data). */
    public function deleteImportedByNode(int $node_id): bool {
        $stmt = $this->conn->prepare(
            "DELETE FROM telemetry_logs WHERE node_id = ? AND source = 'CSV'"
        );
        return $stmt->execute([$node_id]);
    }
}