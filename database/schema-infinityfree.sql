
CREATE TABLE IF NOT EXISTS administrators (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(80) NOT NULL UNIQUE,
 full_name VARCHAR(120) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS drivers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 email VARCHAR(160) NOT NULL UNIQUE,
 phone VARCHAR(30) NOT NULL,
 licence_number VARCHAR(80) NULL,
 password_hash VARCHAR(255) NOT NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 registration_number VARCHAR(40) NOT NULL UNIQUE,
 vehicle_type VARCHAR(80) NOT NULL,
 vehicle_model VARCHAR(100) NULL,
 capacity VARCHAR(60) NULL,
 status ENUM('Available','Assigned','Active','Maintenance','Inactive') NOT NULL DEFAULT 'Available',
 assigned_driver_id INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_vehicle_driver FOREIGN KEY (assigned_driver_id) REFERENCES drivers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS collection_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference_code VARCHAR(40) NOT NULL UNIQUE,
 customer_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 email VARCHAR(160) NULL,
 region VARCHAR(80) NOT NULL,
 address VARCHAR(255) NOT NULL,
 waste_category VARCHAR(60) NOT NULL,
 estimated_quantity VARCHAR(80) NULL,
 preferred_date DATE NULL,
 preferred_time VARCHAR(40) NULL,
 customer_latitude DECIMAL(10,7) NULL,
 customer_longitude DECIMAL(10,7) NULL,
 notes TEXT NULL,
 status ENUM('Pending','Approved','Assigned','Driver En Route','Arrived','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
 assigned_vehicle_id INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_collection_status(status),
 INDEX idx_collection_phone_ref(phone,reference_code),
 CONSTRAINT fk_request_vehicle FOREIGN KEY (assigned_vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_assignments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 collection_request_id BIGINT UNSIGNED NOT NULL,
 vehicle_id INT UNSIGNED NOT NULL,
 driver_id INT UNSIGNED NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 started_at DATETIME NULL,
 completed_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_assignment_driver_active(driver_id,active),
 CONSTRAINT fk_assignment_request FOREIGN KEY (collection_request_id) REFERENCES collection_requests(id) ON DELETE CASCADE,
 CONSTRAINT fk_assignment_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
 CONSTRAINT fk_assignment_driver FOREIGN KEY (driver_id) REFERENCES drivers(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vehicle_id INT UNSIGNED NOT NULL,
 driver_id INT UNSIGNED NOT NULL,
 assignment_id BIGINT UNSIGNED NOT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy DECIMAL(9,2) NULL,
 recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_vehicle_latest(vehicle_id,recorded_at),
 INDEX idx_assignment_latest(assignment_id,recorded_at),
 CONSTRAINT fk_location_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
 CONSTRAINT fk_location_driver FOREIGN KEY (driver_id) REFERENCES drivers(id),
 CONSTRAINT fk_location_assignment FOREIGN KEY (assignment_id) REFERENCES vehicle_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS environmental_reports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference_code VARCHAR(40) NOT NULL UNIQUE,
 reporter_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 location VARCHAR(255) NOT NULL,
 category VARCHAR(60) NOT NULL,
 description TEXT NOT NULL,
 evidence_path VARCHAR(255) NULL,
 status ENUM('Submitted','Under Review','Assigned','Resolved','Rejected') NOT NULL DEFAULT 'Submitted',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recycling_listings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 material VARCHAR(60) NOT NULL,
 quantity DECIMAL(10,2) NOT NULL,
 unit VARCHAR(20) NOT NULL DEFAULT 'kg',
 location VARCHAR(180) NOT NULL,
 description TEXT NULL,
 status ENUM('Pending Review','Available','Reserved','Collected','Sold','Rejected') NOT NULL DEFAULT 'Pending Review',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recycling_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 listing_id BIGINT UNSIGNED NOT NULL,
 buyer_name VARCHAR(120) NOT NULL,
 quantity DECIMAL(10,2) NOT NULL,
 amount_ghs DECIMAL(10,2) NULL,
 status ENUM('Pending','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_transaction_listing FOREIGN KEY (listing_id) REFERENCES recycling_listings(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 actor_type VARCHAR(30) NOT NULL,
 actor_id INT UNSIGNED NULL,
 action VARCHAR(120) NOT NULL,
 details TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
