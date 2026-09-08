<?php
// classes/SensorNode.php
// CRUD operations for monitoring locations (sensor_nodes table).

require_once __DIR__ . '/../config.php';

class SensorNode
{
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /** CREATE — add a new monitoring location. */
    public function create(string $name, float $lat, float $lon, string $status = 'active'): int {
        $stmt = $this->conn->prepare(
            'INSERT INTO sensor_nodes (location_name, latitude, longitude, status)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $lat, $lon, $status]);
        return (int) $this->conn->lastInsertId();
    }

    /** READ — get all locations, newest first. */
    public function getAll(): array {
        $stmt = $this->conn->query(
            'SELECT * FROM sensor_nodes ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /** READ — get one location by ID. */
    public function getById(int $node_id): ?array {
        $stmt = $this->conn->prepare('SELECT * FROM sensor_nodes WHERE node_id = ?');
        $stmt->execute([$node_id]);
        return $stmt->fetch() ?: null;
    }

    /** UPDATE — modify an existing location. */
    public function update(int $node_id, string $name, float $lat, float $lon, string $status): bool {
        $stmt = $this->conn->prepare(
            'UPDATE sensor_nodes
             SET location_name = ?, latitude = ?, longitude = ?, status = ?
             WHERE node_id = ?'
        );
        return $stmt->execute([$name, $lat, $lon, $status, $node_id]);
    }

    /** DELETE — remove a location. Telemetry is auto-deleted by ON DELETE CASCADE. */
    public function delete(int $node_id): bool {
        $stmt = $this->conn->prepare('DELETE FROM sensor_nodes WHERE node_id = ?');
        return $stmt->execute([$node_id]);
    }
}