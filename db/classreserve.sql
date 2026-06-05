-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2026 at 11:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `classreserve`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `priority` int(11) NOT NULL DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','Under Review','In Progress','Resolved','Rejected') NOT NULL DEFAULT 'Open',
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `upvotes` int(11) NOT NULL DEFAULT 0,
  `has_document` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_path` varchar(255) DEFAULT NULL,
  `is_affecting_booking` tinyint(1) NOT NULL DEFAULT 0,
  `related_booking` varchar(255) DEFAULT NULL,
  `related_booking_id` int(11) DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `user_id`, `room_id`, `title`, `room_name`, `category`, `description`, `status`, `priority`, `upvotes`, `has_document`, `uploaded_path`, `is_affecting_booking`, `related_booking`, `related_booking_id`, `admin_response`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Projector flickering during presentations', 'Room A-301', 'Projector/Equipment Issue', 'The projector flickers every few minutes and makes slides hard to read.', 'Under Review', 'High', 7, 0, NULL, 1, 'Software Engineering Extra Class', NULL, 'Maintenance team has been notified.', NULL, '2026-06-04 13:57:32', NULL),
(2, 3, 5, 'Schedule conflict for Room D-202', 'Room D-202', 'Schedule Conflict', 'An extra class and club workshop appear to be assigned to the same time.', 'In Progress', 'Urgent', 4, 0, NULL, 1, 'Workshop - Tech Club', NULL, NULL, NULL, '2026-06-04 13:57:32', NULL),
(3, 4, 3, 'AC not working in Lab C-105', 'Lab C-105', 'AC/Fan/Light Problem', 'The air conditioning has not been working for the past two days.', 'Open', 'Medium', 3, 0, NULL, 0, NULL, NULL, NULL, NULL, '2026-06-04 13:57:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `issue_comments`
--

CREATE TABLE `issue_comments` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_role` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issue_comments`
--

INSERT INTO `issue_comments` (`id`, `issue_id`, `user_id`, `author_name`, `author_role`, `message`, `created_at`) VALUES
(1, 1, 2, 'Dr. Sarah Johnson', 'faculty', 'Confirmed during my morning lecture.', '2026-06-04 13:57:32'),
(2, 3, 4, 'Dr. Maria Garcia', 'faculty', 'Students reported the same issue yesterday.', '2026-06-04 13:57:32'),
(3, 3, 7, 'farha', 'student', 'yyyy', '2026-06-04 18:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('success','error','warning','pending','info') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'info', 'New Issue Comment', 'farha commented on \"AC not working in Lab C-105\".', 0, '2026-06-04 18:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `type` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `equipment` text DEFAULT NULL,
  `status` enum('available','booked','maintenance','disabled') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','club','faculty','admin') NOT NULL DEFAULT 'student',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'System Admin', 'admin@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'admin', 1, '2026-06-04 13:57:32'),
(2, 'Dr. Sarah Johnson', 'sarah.johnson@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty', 1, '2026-06-04 13:57:32'),
(3, 'Prof. David Lee', 'david.lee@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty', 1, '2026-06-04 13:57:32'),
(4, 'Dr. Maria Garcia', 'maria.garcia@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty', 1, '2026-06-04 13:57:32'),
(5, 'Administrator', 'admin@classreserve.local', '$2y$10$kcSELHwFSH.e5DKi6tyr9.vxWMgdv49mrdUfYUeBaGtxlfe.4pOhq', 'admin', 1, '2026-06-04 13:57:43'),
(6, 'Computing club', 'computerclub@uni.edu', '$2y$10$wquBAi3d9IB/VnvxKsL.OerHEXOP5mULvarY0kkHi74nsrxBvAf3S', 'club', 1, '2026-06-04 17:40:42'),
(7, 'farha', 'f@uni.edu', '$2y$10$nkzsYO4sQBHwfHB6c2KUEOOJfFPSoiumW3jwfKgBrSphv5SM0dbJG', 'student', 1, '2026-06-04 18:42:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `issue_comments`
--
ALTER TABLE `issue_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `issues_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD CONSTRAINT `issue_comments_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
