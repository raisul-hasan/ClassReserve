-- Classroom Availability System
-- Run this script to create the database and seed sample data

CREATE DATABASE IF NOT EXISTS classroom_availability
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE classroom_availability;

-- Users: student, club, faculty, admin
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'club', 'faculty', 'admin') NOT NULL DEFAULT 'student',
    club_name VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    building VARCHAR(100) NOT NULL,
    floor INT NOT NULL DEFAULT 1,
    capacity INT NOT NULL,
    equipment TEXT NULL,
    status ENUM('available', 'maintenance') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
<<<<<<< HEAD
    purpose VARCHAR(100) NOT NULL DEFAULT 'Other',
    course_code VARCHAR(50) NULL,
    section VARCHAR(50) NULL,
    batch VARCHAR(50) NULL,
    department VARCHAR(100) NULL,
=======
>>>>>>> origin/Riche01
    description TEXT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    attendees INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    priority TINYINT NOT NULL DEFAULT 1,
    event_file VARCHAR(255) NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking_times (room_id, start_time, end_time),
    INDEX idx_booking_status (status)
);

-- Maintenance blocks (admin)
CREATE TABLE maintenance_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    reason TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_maintenance_times (room_id, start_time, end_time)
);

-- Seed admin (password: admin123)
INSERT INTO users (name, email, password_hash, role) VALUES
('System Admin', 'admin@classroom.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Seed faculty (password: faculty123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Dr. Jane Smith', 'faculty@classroom.local', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'faculty'),
('Prof. John Doe', 'john.doe@classroom.local', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'faculty');

-- Seed students (password: student123)
INSERT INTO users (name, email, password_hash, role) VALUES
('Alice Student', 'alice@student.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'student'),
('Bob Student', 'bob@student.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'student');

-- Seed club (password: club123)
INSERT INTO users (name, email, password_hash, role, club_name) VALUES
('Coding Club', 'club@student.local', '$2y$10$EItVZzK8vYvH5zJ5qJ5qJ.uH5zJ5qJ5qJ5qJ5qJ5qJ5qJ5qJ5qJ5q', 'club', 'Coding Club');

-- Fix club password hash (bcrypt for 'club123')
UPDATE users SET password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy'
WHERE email = 'club@student.local';

-- Seed rooms
INSERT INTO rooms (name, building, floor, capacity, equipment) VALUES
('A101', 'Science Block', 1, 40, 'Projector, Whiteboard'),
('A102', 'Science Block', 1, 30, 'Projector'),
('A201', 'Science Block', 2, 60, 'Projector, Microphones, Whiteboard'),
('B101', 'Arts Block', 1, 25, 'Whiteboard'),
('B205', 'Arts Block', 2, 50, 'Projector, Sound System'),
('C301', 'Engineering Block', 3, 80, 'Projector, Lab Equipment'),
('C302', 'Engineering Block', 3, 35, 'Computers, Projector'),
('D101', 'Library Annex', 1, 20, 'Whiteboard, TV Screen');
