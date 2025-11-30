CREATE TABLE IF NOT EXISTS parkings (
    id CHAR(36) PRIMARY KEY,          -- UUID
    owner_id CHAR(36) NOT NULL,       -- ID du propriétaire
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL, -- Latitude avec précision GPS
    longitude DECIMAL(11, 8) NOT NULL, -- Longitude avec précision GPS
    total_places INT NOT NULL,
    
    price_grid JSON NOT NULL,         -- Stockage du PriceGrid
    opening_hours JSON NOT NULL,      -- Stockage du WeeklySchedule
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
