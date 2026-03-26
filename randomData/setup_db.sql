-- ============================================================
-- sensor_db 스키마 설정
-- 실행: sudo mysql < setup_db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS sensor_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'sensor_user'@'localhost'
  IDENTIFIED BY 'Sensor@Pass1';

GRANT ALL PRIVILEGES ON sensor_db.* TO 'sensor_user'@'localhost';
FLUSH PRIVILEGES;

USE sensor_db;

CREATE TABLE IF NOT EXISTS sensor_readings (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    recorded_at DATETIME(3)    NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    temperature DECIMAL(5,2)   NOT NULL COMMENT '온도 (°C)',
    humidity    DECIMAL(5,2)   NOT NULL COMMENT '상대습도 (%)',
    pressure    DECIMAL(7,2)   NOT NULL COMMENT '기압 (hPa)',
    light_level INT            NOT NULL COMMENT '조도 (lux)',
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
