-- Smart Slope Database Schema
-- Run this in phpMyAdmin (Import tab) or MySQL CLI

CREATE DATABASE IF NOT EXISTS landslide_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE landslide_db;

-- Table 1: Monitoring locations (sensor nodes)
CREATE TABLE IF NOT EXISTS sensor_nodes (
    node_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 4) NOT NULL,
    longitude DECIMAL(10, 4) NOT NULL,
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table 2: Weather telemetry readings (with AI risk level)
CREATE TABLE IF NOT EXISTS telemetry_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    node_id INT UNSIGNED NOT NULL,
    soil_moisture FLOAT NULL,
    rainfall_mm FLOAT NOT NULL DEFAULT 0,
    humidity FLOAT NULL,
    pressure FLOAT NULL,
    temperature FLOAT NULL,
    wind_speed FLOAT NULL,
    risk_level TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=Low, 1=High - AI prediction stored at ingest',
    source ENUM('API', 'ESP32', 'CSV') NOT NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_telemetry_node
        FOREIGN KEY (node_id) REFERENCES sensor_nodes(node_id)
        ON DELETE CASCADE,
    INDEX idx_telemetry_timestamp (timestamp),
    INDEX idx_telemetry_node_timestamp (node_id, timestamp)
) ENGINE=InnoDB;

-- Table 3: User accounts (admin, CDRRMO staff, viewers)
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'cdrrmo_staff', 'viewer') NOT NULL DEFAULT 'viewer',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed: 3 monitoring locations in Baguio City
INSERT INTO sensor_nodes (location_name, latitude, longitude, status)
SELECT 'Barangay Loay, Baguio City', 16.4173, 120.5963, 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM sensor_nodes WHERE location_name = 'Barangay Loay, Baguio City'
);

INSERT INTO sensor_nodes (location_name, latitude, longitude, status)
SELECT 'Barangay Pinget, Baguio City', 16.4230, 120.5900, 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM sensor_nodes WHERE location_name = 'Barangay Pinget, Baguio City'
);

INSERT INTO sensor_nodes (location_name, latitude, longitude, status)
SELECT 'Barangay Gibraltar, Baguio City', 16.4078, 120.6000, 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM sensor_nodes WHERE location_name = 'Barangay Gibraltar, Baguio City'
);