-- ClassReserve complete database install
-- Import this file into MySQL/MariaDB to create a fresh functional database.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `classreserve`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `classreserve`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `issue_comments`;
DROP TABLE IF EXISTS `issues`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `maintenance`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student','club','faculty','admin') NOT NULL DEFAULT 'student',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `audit_logs` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(100) DEFAULT NULL,
  `target_id` INT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_idx` (`user_id`),
  KEY `audit_logs_action_idx` (`action`),
  KEY `audit_logs_created_at_idx` (`created_at`),
  CONSTRAINT `audit_logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `rooms` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `capacity` INT NOT NULL DEFAULT 0,
  `type` VARCHAR(100) DEFAULT NULL,
  `building` VARCHAR(100) DEFAULT NULL,
  `floor` VARCHAR(50) DEFAULT NULL,
  `equipment` TEXT DEFAULT NULL,
  `status` ENUM('available','booked','maintenance','disabled') NOT NULL DEFAULT 'available',
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `bookings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `room_id` INT DEFAULT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `priority` INT NOT NULL DEFAULT 1,
  `title` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `uploaded_path` VARCHAR(255) DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bookings_user_id_idx` (`user_id`),
  KEY `bookings_room_id_idx` (`room_id`),
  KEY `bookings_reviewed_by_idx` (`reviewed_by`),
  CONSTRAINT `bookings_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_room_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
  ,CONSTRAINT `bookings_reviewer_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `maintenance` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `maintenance_room_id_idx` (`room_id`),
  CONSTRAINT `maintenance_room_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `type` ENUM('success','error','warning','pending','info') NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_idx` (`user_id`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `issues` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `room_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `room_name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('Open','Under Review','In Progress','Resolved','Rejected') NOT NULL DEFAULT 'Open',
  `priority` ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `upvotes` INT NOT NULL DEFAULT 0,
  `has_document` TINYINT(1) NOT NULL DEFAULT 0,
  `uploaded_path` VARCHAR(255) DEFAULT NULL,
  `is_affecting_booking` TINYINT(1) NOT NULL DEFAULT 0,
  `related_booking` VARCHAR(255) DEFAULT NULL,
  `related_booking_id` INT DEFAULT NULL,
  `admin_response` TEXT DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `issues_user_id_idx` (`user_id`),
  KEY `issues_room_id_idx` (`room_id`),
  CONSTRAINT `issues_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issues_room_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `issue_comments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `issue_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `author_name` VARCHAR(255) NOT NULL,
  `author_role` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `issue_comments_issue_id_idx` (`issue_id`),
  KEY `issue_comments_user_id_idx` (`user_id`),
  CONSTRAINT `issue_comments_issue_fk` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `issue_comments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Demo login accounts.
-- Password for seeded admin accounts: Admin@123!
-- Password for all other seeded demo accounts: ClassReserve123!
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'System Admin', 'admin@classreserve.local', '$2y$10$pcd7dyprIETolqxSYGvhPugTe2ieIP9Zu8skj/seeD7Z.fml5xDhm', 'admin', 1, '2026-06-04 13:57:32'),
(2, 'Dr. Alice Johnson', 'alice.johnson@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty', 1, '2026-06-04 13:57:32'),
(3, 'Prof. Motaharul', 'motaharul@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty', 1, '2026-06-04 13:57:32'),
(4, 'Anika Tasmin', 'anika.tasnim@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty', 1, '2026-06-04 13:57:32'),
(5, 'Administrator', 'admin@uni.edu', '$2y$10$pcd7dyprIETolqxSYGvhPugTe2ieIP9Zu8skj/seeD7Z.fml5xDhm', 'admin', 1, '2026-06-04 13:57:43'),
(6, 'Computing Club', 'computerclub@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'club', 1, '2026-06-04 17:40:42'),
(7, 'Farha Rahman', 'f@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'student', 1, '2026-06-04 18:42:58'),
(8, 'Ayesha Khan', 'ayesha.khan@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'student', 1, '2026-06-05 09:12:00'),
(9, 'Rahul Sen', 'rahul.sen@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'student', 1, '2026-06-05 09:18:00'),
(10, 'Nadia Islam', 'nadia.islam@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'student', 1, '2026-06-05 09:24:00'),
(11, 'Tanvir Ahmed', 'tanvir.ahmed@uni.edu', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'student', 1, '2026-06-05 09:30:00');

INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `building`, `floor`, `equipment`, `status`, `notes`) VALUES
(1, 'Room A-301', 30, 'Lecture', 'Building A', '3rd Floor', 'Projector, Whiteboard, Wi-Fi', 'available', 'Standard lecture room'),
(2, 'Room A-302', 40, 'Lecture', 'Building A', '3rd Floor', 'Projector, Computer, Wi-Fi', 'available', 'Lecture room with instructor computer'),
(3, 'Lab C-105', 25, 'Lab', 'Building C', '1st Floor', 'Computers, Wi-Fi, Projector', 'available', 'Computer lab'),
(4, 'Auditorium B', 200, 'Auditorium', 'Building B', 'Ground Floor', 'Audio System, Projector, Stage', 'available', 'Large event space'),
(5, 'Room D-202', 35, 'Lecture', 'Building D', '2nd Floor', 'Whiteboard, Wi-Fi', 'maintenance', 'HVAC maintenance scheduled'),
(6, 'Room E-101', 20, 'Seminar', 'Building E', '1st Floor', 'TV Display, Wi-Fi', 'available', 'Small seminar room'),
(7, 'Lab C-106', 30, 'Lab', 'Building C', '1st Floor', 'Computers, Wi-Fi, Printer', 'available', 'Computer lab with printer'),
(8, 'Room B-205', 45, 'Lecture', 'Building B', '2nd Floor', 'Projector, Whiteboard, Wi-Fi, Computer', 'available', 'Medium lecture room'),
(9, 'Auditorium A', 120, 'Auditorium', 'Main Building', NULL, NULL, 'available', 'Large room with projector and stage.'),
(10, 'Classroom 101', 40, 'Classroom', 'North Wing', NULL, NULL, 'available', 'Standard classroom with whiteboard.'),
(11, 'Conference Room B', 20, 'Conference', 'East Hall', NULL, NULL, 'available', 'Small meeting room with video conferencing support.'),
(12, 'Lab 202', 30, 'Computer Lab', 'Tech Center', NULL, NULL, 'available', 'Computer lab with 30 workstations.');

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `start_datetime`, `end_datetime`, `status`, `priority`, `title`, `description`, `uploaded_path`, `reviewed_by`, `reviewed_at`, `rejection_reason`, `created_at`) VALUES
(1, 2, 8, '2026-06-05 10:00:00', '2026-06-05 12:00:00', 'approved', 3, 'Faculty Extra Class', 'Extra class for pending lecture material.', NULL, 1, '2026-06-04 15:00:00', NULL, '2026-06-04 14:00:00'),
(2, 6, 3, '2026-06-08 14:00:00', '2026-06-08 16:00:00', 'approved', 2, 'Programming Club Workshop', 'Hands-on workshop for club members.', NULL, 1, '2026-06-04 15:05:00', NULL, '2026-06-04 14:05:00'),
(3, 7, 10, '2026-06-12 11:00:00', '2026-06-12 13:00:00', 'pending', 1, 'Student Study Session', 'Group study session before exams.', NULL, NULL, NULL, NULL, '2026-06-04 14:10:00'),
(4, 6, 4, '2026-06-20 15:00:00', '2026-06-20 18:00:00', 'approved', 2, 'Debate Club Event', 'Inter-department debate club event.', NULL, 1, '2026-06-04 15:15:00', NULL, '2026-06-04 14:15:00'),
(5, 3, 2, '2026-06-24 08:00:00', '2026-06-24 10:00:00', 'approved', 3, 'Makeup Class CSE-221', 'Makeup class for CSE-221.', NULL, 1, '2026-06-04 15:20:00', NULL, '2026-06-04 14:20:00'),
(6, 8, 1, '2026-06-06 09:00:00', '2026-06-06 10:00:00', 'approved', 1, 'Calculus Study Circle', 'Peer-led study circle before the weekly quiz.', NULL, 1, '2026-06-05 10:00:00', NULL, '2026-06-05 09:45:00'),
(7, 9, 6, '2026-06-13 15:00:00', '2026-06-13 17:00:00', 'pending', 1, 'Database Project Meeting', 'Team meeting for the database course project.', NULL, NULL, NULL, NULL, '2026-06-05 10:05:00'),
(8, 10, 11, '2026-06-18 10:00:00', '2026-06-18 11:00:00', 'approved', 1, 'Thesis Group Consultation', 'Small group consultation with project teammates.', NULL, 5, '2026-06-05 10:30:00', NULL, '2026-06-05 10:20:00'),
(9, 11, 12, '2026-06-22 13:00:00', '2026-06-22 15:00:00', 'cancelled', 1, 'AI Lab Practice', 'Practice slot cancelled by requester.', NULL, NULL, NULL, NULL, '2026-06-05 10:35:00'),
(10, 8, 2, '2026-06-26 14:00:00', '2026-06-26 16:00:00', 'rejected', 1, 'Presentation Rehearsal', 'Rejected because the requested slot was reserved for a makeup class.', NULL, 1, '2026-06-05 11:00:00', 'The requested slot is reserved for a makeup class.', '2026-06-05 10:50:00'),
(11, 8, 9, '2026-02-12 07:36:00', '2026-02-12 19:35:00', 'pending', 1, 'club workshop', '', 'uploads/booking_0df48ff0423de63225e524c3c754b3e7.jpeg', NULL, NULL, NULL, '2026-06-06 01:38:14');

INSERT INTO `maintenance` (`id`, `room_id`, `start_datetime`, `end_datetime`, `reason`, `created_at`) VALUES
(1, 5, '2026-06-10 09:00:00', '2026-06-10 12:00:00', 'HVAC inspection and maintenance.', '2026-06-04 14:25:00'),
(2, 1, '2026-06-15 09:00:00', '2026-06-15 17:00:00', 'Projector maintenance and replacement testing.', '2026-06-04 14:30:00');

INSERT INTO `issues` (`id`, `user_id`, `room_id`, `title`, `room_name`, `category`, `description`, `status`, `priority`, `upvotes`, `has_document`, `uploaded_path`, `is_affecting_booking`, `related_booking`, `related_booking_id`, `admin_response`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Projector flickering during presentations', 'Room A-301', 'Projector/Equipment Issue', 'The projector flickers every few minutes and makes slides hard to read.', 'Under Review', 'High', 7, 0, NULL, 1, 'Software Engineering Extra Class', NULL, 'Maintenance team has been notified.', NULL, '2026-06-04 13:57:32', NULL),
(2, 3, 5, 'Schedule conflict for Room D-202', 'Room D-202', 'Schedule Conflict', 'An extra class and club workshop appear to be assigned to the same time.', 'In Progress', 'Urgent', 4, 0, NULL, 1, 'Workshop - Tech Club', NULL, NULL, NULL, '2026-06-04 13:57:32', NULL),
(3, 4, 3, 'AC not working in Lab C-105', 'Lab C-105', 'AC/Fan/Light Problem', 'The air conditioning has not been working for the past two days.', 'Open', 'Medium', 3, 0, NULL, 0, NULL, NULL, NULL, NULL, '2026-06-04 13:57:32', NULL),
(4, 8, 2, 'Whiteboard markers are missing', 'Room A-302', 'Other', 'There were no usable markers available during our study session.', 'Open', 'Low', 2, 0, NULL, 0, NULL, NULL, NULL, NULL, '2026-06-05 11:10:00', NULL),
(5, 9, 6, 'Loose chair near front row', 'Room E-101', 'Furniture Problem', 'One chair near the front row is unstable and should be repaired or removed.', 'Under Review', 'Medium', 5, 0, NULL, 0, NULL, NULL, 'Facilities team will inspect it today.', NULL, '2026-06-05 11:25:00', '2026-06-05 12:05:00'),
(6, 10, 12, 'Several lab computers cannot log in', 'Lab 202', 'Projector/Equipment Issue', 'Four computers show login errors and block students from completing lab work.', 'In Progress', 'High', 8, 0, NULL, 1, 'AI Lab Practice', 9, 'IT support has been assigned.', NULL, '2026-06-05 11:40:00', '2026-06-05 12:20:00'),
(7, 11, 4, 'Auditorium speakers crackling', 'Auditorium B', 'Projector/Equipment Issue', 'The main speakers crackle when microphones are used at high volume.', 'Open', 'Medium', 4, 0, NULL, 1, 'Debate Club Event', 4, NULL, NULL, '2026-06-05 11:55:00', NULL);

INSERT INTO `issue_comments` (`id`, `issue_id`, `user_id`, `author_name`, `author_role`, `message`, `created_at`) VALUES
(1, 1, 2, 'Dr. Alice Johnson', 'faculty', 'Confirmed during my morning lecture.', '2026-06-04 13:57:32'),
(2, 3, 4, 'Anika Tasmin', 'faculty', 'Students reported the same issue yesterday.', '2026-06-04 13:57:32'),
(3, 3, 7, 'Farha Rahman', 'student', 'Same issue affected our lab session.', '2026-06-04 18:44:45'),
(4, 4, 8, 'Ayesha Khan', 'student', 'We had to borrow markers from another room.', '2026-06-05 12:00:00'),
(5, 5, 9, 'Rahul Sen', 'student', 'The chair is still there after lunch.', '2026-06-05 12:15:00'),
(6, 6, 10, 'Nadia Islam', 'student', 'The issue affected our assigned workstation group.', '2026-06-05 12:30:00'),
(7, 6, 1, 'System Admin', 'admin', 'IT support has been notified.', '2026-06-05 12:45:00'),
(8, 7, 6, 'Computing Club', 'club', 'We noticed this during event setup as well.', '2026-06-05 13:00:00'),
(9, 7, 8, 'Ayesha Khan', 'student', 'llo', '2026-06-06 01:39:33');

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'info', 'New Issue Comment', 'Farha Rahman commented on "AC not working in Lab C-105".', 0, '2026-06-04 18:44:45'),
(2, 7, 'pending', 'Booking Pending', 'Your Student Study Session request is waiting for approval.', 0, '2026-06-04 18:45:00'),
(3, 6, 'success', 'Booking Approved', 'Your Programming Club Workshop booking was approved.', 0, '2026-06-04 18:46:00'),
(4, 8, 'success', 'Booking Approved', 'Your Calculus Study Circle booking was approved.', 0, '2026-06-05 13:10:00'),
(5, 9, 'pending', 'Booking Pending', 'Your Database Project Meeting request is waiting for approval.', 0, '2026-06-05 13:15:00'),
(6, 10, 'warning', 'Issue Status Updated', '"Several lab computers cannot log in" is now In Progress.', 0, '2026-06-05 13:20:00'),
(7, 11, 'info', 'Issue Received', 'Your Auditorium B speaker report has been received.', 0, '2026-06-05 13:25:00'),
(8, 1, 'warning', 'New Issue Report', 'Several lab computers cannot log in was reported for Lab 202.', 0, '2026-06-05 13:30:00'),
(9, 1, 'pending', 'New Booking Request', 'club workshop is waiting for approval.', 0, '2026-06-06 01:38:14'),
(10, 2, 'pending', 'New Booking Request', 'club workshop is waiting for approval.', 0, '2026-06-06 01:38:14'),
(11, 3, 'pending', 'New Booking Request', 'club workshop is waiting for approval.', 0, '2026-06-06 01:38:14'),
(12, 4, 'pending', 'New Booking Request', 'club workshop is waiting for approval.', 0, '2026-06-06 01:38:14'),
(13, 5, 'pending', 'New Booking Request', 'club workshop is waiting for approval.', 0, '2026-06-06 01:38:14'),
(14, 11, 'info', 'New Issue Comment', 'Ayesha Khan commented on "Auditorium speakers crackling".', 0, '2026-06-06 01:39:33');

ALTER TABLE `users` AUTO_INCREMENT = 12;
ALTER TABLE `audit_logs` AUTO_INCREMENT = 1;
ALTER TABLE `rooms` AUTO_INCREMENT = 13;
ALTER TABLE `bookings` AUTO_INCREMENT = 12;
ALTER TABLE `maintenance` AUTO_INCREMENT = 3;
ALTER TABLE `issues` AUTO_INCREMENT = 8;
ALTER TABLE `issue_comments` AUTO_INCREMENT = 10;
ALTER TABLE `notifications` AUTO_INCREMENT = 15;
