-- ClassReserve Database Schema
-- Run: mysql -u root < db/classreserve.sql

CREATE DATABASE IF NOT EXISTS classreserve
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE classreserve;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'faculty', 'club', 'student') NOT NULL DEFAULT 'student',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    building VARCHAR(100) NOT NULL,
    floor INT NOT NULL DEFAULT 1,
    capacity INT NOT NULL,
    type ENUM('Lecture', 'Lab', 'Seminar', 'Auditorium') NOT NULL DEFAULT 'Lecture',
    status ENUM('available', 'blocked', 'maintenance', 'disabled') NOT NULL DEFAULT 'available',
    notes TEXT NULL
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    priority TINYINT NOT NULL DEFAULT 1 COMMENT '1=student, 2=club, 3=faculty',
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    uploaded_path VARCHAR(255) NULL,
    checkin_code VARCHAR(10) NULL,
    checked_in_at DATETIME NULL,
    no_show TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_booking_times (room_id, start_datetime, end_datetime),
    INDEX idx_booking_status (status)
);

CREATE TABLE maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    reason TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_maintenance_times (room_id, start_datetime, end_datetime)
);

CREATE TABLE issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Open', 'Under Review', 'In Progress', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Open',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
    upvotes INT NOT NULL DEFAULT 0,
    is_affecting_booking TINYINT(1) NOT NULL DEFAULT 0,
    related_booking INT NULL,
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_booking) REFERENCES bookings(id) ON DELETE SET NULL
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('success', 'error', 'warning', 'pending', 'info') NOT NULL DEFAULT 'info',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id INT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed users (password: admin123, faculty123, club123, student123)
INSERT INTO users (name, email, password, role) VALUES
('System Admin', 'admin@classreserve.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. Jane Smith', 'faculty@classreserve.local', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'faculty'),
('Coding Club', 'club@classreserve.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'club'),
('Alice Student', 'student@classreserve.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'student'),
('Bob Student', 'bob@classreserve.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'student');

INSERT INTO rooms (name, building, floor, capacity, type, status, notes) VALUES
('A101', 'Science Block', 1, 40, 'Lecture', 'available', 'Projector, Whiteboard'),
('A102', 'Science Block', 1, 30, 'Lecture', 'available', 'Projector'),
('A201', 'Science Block', 2, 60, 'Seminar', 'available', 'Projector, Microphones'),
('B101', 'Arts Block', 1, 25, 'Lecture', 'available', 'Whiteboard'),
('B205', 'Arts Block', 2, 50, 'Auditorium', 'available', 'Sound System'),
('C301', 'Engineering Block', 3, 80, 'Lab', 'available', 'Lab Equipment'),
('C302', 'Engineering Block', 3, 35, 'Lab', 'available', 'Computers, Projector'),
('D101', 'Library Annex', 1, 20, 'Seminar', 'available', 'TV Screen');

INSERT INTO bookings (user_id, room_id, start_datetime, end_datetime, status, priority, title, description, checkin_code) VALUES
(2, 1, DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 1 DAY), INTERVAL 2 HOUR), 'approved', 3, 'Advanced Physics Lecture', 'Weekly lecture session', 'ABC123'),
(3, 5, DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 3 HOUR), 'pending', 2, 'Club Meeting', 'Monthly club gathering', NULL),
(4, 3, DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 3 DAY), INTERVAL 1 HOUR), 'approved', 1, 'Study Group', 'Exam prep session', 'XYZ789');

INSERT INTO notifications (user_id, type, title, message) VALUES
(4, 'success', 'Booking Approved', 'Your booking for A201 has been approved.'),
(3, 'pending', 'Booking Pending', 'Your club meeting request is awaiting review.'),
(2, 'info', 'Welcome', 'Welcome to ClassReserve faculty portal.');

INSERT INTO issues (user_id, title, room_name, category, description, status, priority, upvotes) VALUES
(4, 'Projector not working', 'A101', 'Equipment', 'The projector in A101 flickers and shuts off after 10 minutes.', 'Open', 'High', 3),
(5, 'AC too cold', 'C302', 'Comfort', 'Air conditioning is set too low making the lab uncomfortable.', 'Under Review', 'Medium', 1);

INSERT INTO comments (issue_id, user_id, message) VALUES
(1, 5, 'I experienced the same issue yesterday during my class.'),
(1, 2, 'Maintenance has been notified about this.');
