-- Parking Management System Database Schema
-- Parkings Table
CREATE TABLE IF NOT EXISTS parkings (
    id CHAR(36) PRIMARY KEY,
    owner_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    total_places INT NOT NULL,
    price_grid JSON NOT NULL,
    opening_hours JSON NOT NULL,
    subscription_plans JSON NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Accounts Table
CREATE TABLE accounts (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role VARCHAR(20) NOT NULL
);

-- user_subscriptions Table
CREATE TABLE user_subscriptions (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    parking_id CHAR(36) NOT NULL,
    plan_id VARCHAR(255) NOT NULL,
    plan_name VARCHAR(255) NOT NULL,
    price INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    schedule_json JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES accounts(id),
    FOREIGN KEY (parking_id) REFERENCES parkings(id) ON DELETE CASCADE,

    INDEX idx_sub_period (parking_id, start_date, end_date, is_active),
    INDEX idx_user_sub (user_id, start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reservations Table
CREATE TABLE reservations (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    parking_id CHAR(36) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    price_paid INT NOT NULL,
    status VARCHAR(20) DEFAULT 'CONFIRMED',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES accounts(id),
    FOREIGN KEY (parking_id) REFERENCES parkings(id) ON DELETE CASCADE,

    -- Index critique pour le calcul de chevauchement
    INDEX idx_res_overlap (parking_id, status, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Parking Sessions Table
CREATE TABLE parking_sessions (
    id CHAR(36) PRIMARY KEY,
    parking_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    reservation_id CHAR(36) NULL,
    entry_time DATETIME NOT NULL,
    exit_time DATETIME DEFAULT NULL,
    price_paid INT DEFAULT 0,
    
    FOREIGN KEY (parking_id) REFERENCES parkings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES accounts(id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,

    -- Index pour trouver rapidement les voitures "encore à l'intérieur" (exit_time IS NULL)
    INDEX idx_session_active (parking_id, exit_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
