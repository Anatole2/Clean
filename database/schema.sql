CREATE TABLE IF NOT EXISTS parkings (
    id CHAR(36) PRIMARY KEY, -- UUID
    owner_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    total_places INT NOT NULL,
    
    -- C'est ici que la magie opère pour tes Value Objects :
    price_grid JSON NOT NULL,
    opening_hours JSON NOT NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
