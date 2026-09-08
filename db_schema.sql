-- Smart Slope V2 Database Schema
-- Run in phpMyAdmin (Import tab) or MySQL CLI

DROP DATABASE IF EXISTS landslide_db;
CREATE DATABASE landslide_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE landslide_db;

-- Table 1: Monitoring locations (sensor nodes)
CREATE TABLE sensor_nodes (
    node_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_name  VARCHAR(100) NOT NULL,
    latitude       DECIMAL(10,6) NOT NULL,
    longitude      DECIMAL(10,6) NOT NULL,
    status         ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table 2: Weather telemetry readings with risk score and level
CREATE TABLE telemetry_logs (
    log_id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    node_id        INT UNSIGNED NOT NULL,
    soil_moisture  DECIMAL(6,2) NULL,
    rainfall_mm    DECIMAL(8,2) NOT NULL DEFAULT 0,
    humidity       DECIMAL(5,2) NULL,
    pressure       DECIMAL(7,2) NULL,
    temperature    DECIMAL(5,2) NULL,
    wind_speed     DECIMAL(6,2) NULL,
    risk_score     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100 calculated by RiskEngine',
    risk_level     ENUM('Low','Moderate','High','Critical') NOT NULL DEFAULT 'Low',
    source         ENUM('API','ESP32','CSV') NOT NULL,
    timestamp      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_telemetry_node
        FOREIGN KEY (node_id) REFERENCES sensor_nodes(node_id)
        ON DELETE CASCADE,
    INDEX idx_telemetry_timestamp (timestamp),
    INDEX idx_telemetry_node_timestamp (node_id, timestamp)
) ENGINE=InnoDB;

-- Table 3: User accounts (admin, CDRRMO staff, viewers)
CREATE TABLE users (
    user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    full_name      VARCHAR(100) NOT NULL,
    role           ENUM('admin','cdrrmo_staff','viewer') NOT NULL DEFAULT 'viewer',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed: 3 monitoring locations in Baguio City
INSERT INTO sensor_nodes (location_name, latitude, longitude, status) VALUES
('Barangay Loay, Baguio City',     16.4173, 120.5963, 'active'),
('Barangay Pinget, Baguio City',   16.4230, 120.5900, 'active'),
('Barangay Gibraltar, Baguio City', 16.4078, 120.6000, 'active');