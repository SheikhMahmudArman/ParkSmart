-- Import into a NEW, EMPTY parksmart_clean database. No DROP TABLE commands.

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    role ENUM('driver', 'staff', 'admin') NOT NULL DEFAULT 'driver',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE parking_lots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(200) NOT NULL,
    description TEXT NULL,
    contact VARCHAR(100) NULL,
    opens_at TIME NOT NULL DEFAULT '00:00:00',
    closes_at TIME NOT NULL DEFAULT '23:59:59',
    hourly_rate DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CHECK (hourly_rate > 0),
    CHECK (closes_at > opens_at)
) ENGINE=InnoDB;


CREATE TABLE parking_spaces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parking_lot_id BIGINT UNSIGNED NOT NULL,
    space_number VARCHAR(30) NOT NULL,
    type ENUM('Standard', 'Disabled', 'EV', 'Reserved') NOT NULL DEFAULT 'Standard',
    status ENUM('Available', 'Occupied', 'Maintenance') NOT NULL DEFAULT 'Available',

    UNIQUE (parking_lot_id, space_number),

    FOREIGN KEY (parking_lot_id)
        REFERENCES parking_lots(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plate_number VARCHAR(30) NOT NULL UNIQUE,
    make VARCHAR(60) NULL,
    model VARCHAR(60) NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    space_id BIGINT UNSIGNED NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM(
        'Pending',
        'Confirmed',
        'Active',
        'Completed',
        'Cancelled',
        'Expired'
    ) NOT NULL DEFAULT 'Pending',
    payment_status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (vehicle_id)
        REFERENCES vehicles(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (space_id)
        REFERENCES parking_spaces(id)
        ON DELETE RESTRICT,

    CHECK (end_at > start_at),
    CHECK (hourly_rate > 0),
    CHECK (total_amount >= 0),

    INDEX idx_spot_schedule (space_id, status, start_at, end_at),
    INDEX idx_vehicle_schedule (vehicle_id, status, start_at, end_at)
) ENGINE=InnoDB;


CREATE TABLE parking_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL UNIQUE,
    entry_time DATETIME NOT NULL,
    exit_time DATETIME NULL,
    duration_minutes INT UNSIGNED NULL,
    total_cost DECIMAL(10,2) NULL,

    FOREIGN KEY (reservation_id)
        REFERENCES reservations(id)
        ON DELETE RESTRICT,

    CHECK (exit_time IS NULL OR exit_time >= entry_time)
) ENGINE=InnoDB;


CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('Demo') NOT NULL DEFAULT 'Demo',
    status ENUM('Completed') NOT NULL DEFAULT 'Completed',
    transaction_id VARCHAR(64) NOT NULL UNIQUE,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (reservation_id)
        REFERENCES reservations(id)
        ON DELETE RESTRICT,

    CHECK (amount > 0)
) ENGINE=InnoDB;


CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    reservation_id BIGINT UNSIGNED NULL,
    message VARCHAR(255) NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (reservation_id)
        REFERENCES reservations(id)
        ON DELETE RESTRICT,

    INDEX idx_user_notification (user_id, is_read, created_at)
) ENGINE=InnoDB;


CREATE TABLE reservation_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(20) NOT NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (reservation_id)
        REFERENCES reservations(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;