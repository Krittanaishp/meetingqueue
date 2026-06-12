-- Meeting Queue Demo — database schema
-- Database: meetingqueue_db

CREATE DATABASE IF NOT EXISTS meetingqueue_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE meetingqueue_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emp_code VARCHAR(50) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) DEFAULT NULL,
    position_name VARCHAR(100),
    dept_name VARCHAR(100),
    photo LONGTEXT,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    room_number VARCHAR(50) DEFAULT NULL,
    capacity INT UNSIGNED NOT NULL,
    location VARCHAR(255),
    description TEXT,
    image_url VARCHAR(255),
    images TEXT DEFAULT NULL,
    status ENUM('available', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    equipments VARCHAR(255) DEFAULT NULL,
    participants_count INT UNSIGNED DEFAULT 0,
    phone VARCHAR(20),
    attachment_path VARCHAR(255),
    department_name VARCHAR(100),
    is_external BOOLEAN DEFAULT FALSE,
    external_org VARCHAR(255),
    meeting_image VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    deleted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_start_end (start_time, end_time),
    INDEX idx_status (status),
    INDEX idx_is_external (is_external),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS booking_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS meeting_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_booking_user (booking_id, user_id)
) ENGINE=InnoDB;

INSERT INTO rooms (name, room_number, capacity, location) VALUES
('ห้องประชุมเอื้องผึ้ง (30-40 คน)', '1', 40, ''),
('ห้องประชุมต้นข้าว(ศูนย์เสริมสุขไทย 20-30 คน)', '2', 30, 'ศูนย์เสริมสุขไทย'),
('ห้องประชุมเอื้องหลวง (10-15คน)', '3', 15, ''),
('ห้องประชุมเอื้องสามปอย(ข้างร้านค้าสวัสดิการ 40-50 คน)', '4', 50, 'ข้างร้านค้าสวัสดิการ'),
('ห้องประชุมเอื้องคำ(องค์กรแพทย์) ( 10-15 คน)', '5', 15, 'องค์กรแพทย์'),
('ห้องประชุมเอื้องเงิน(10-15คน)', '6', 15, '');
