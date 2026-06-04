-- Minimal schema for ClassReserve
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','club','faculty','admin') NOT NULL DEFAULT 'student',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  capacity INT NOT NULL DEFAULT 0,
  type VARCHAR(100) DEFAULT NULL,
  building VARCHAR(100) DEFAULT NULL,
  floor VARCHAR(50) DEFAULT NULL,
  equipment TEXT,
  status ENUM('available','booked','maintenance','disabled') NOT NULL DEFAULT 'available',
  notes TEXT
);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  room_id INT,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  priority INT NOT NULL DEFAULT 1,
  title VARCHAR(255),
  description TEXT,
  uploaded_path VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS maintenance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  type ENUM('success','error','warning','pending','info') NOT NULL DEFAULT 'info',
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS issues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  room_id INT,
  title VARCHAR(255) NOT NULL,
  room_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('Open','Under Review','In Progress','Resolved','Rejected') NOT NULL DEFAULT 'Open',
  priority ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  upvotes INT NOT NULL DEFAULT 0,
  has_document TINYINT(1) NOT NULL DEFAULT 0,
  uploaded_path VARCHAR(255),
  is_affecting_booking TINYINT(1) NOT NULL DEFAULT 0,
  related_booking VARCHAR(255),
  related_booking_id INT,
  admin_response TEXT,
  rejection_reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS issue_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  issue_id INT NOT NULL,
  user_id INT,
  author_name VARCHAR(255) NOT NULL,
  author_role VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Demo login accounts for local testing.
-- Password for all demo accounts: ClassReserve123!
INSERT IGNORE INTO users (id, name, email, password_hash, role) VALUES
(1, 'System Admin', 'admin@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'admin'),
(2, 'Dr. Sarah Johnson', 'sarah.johnson@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty'),
(3, 'Prof. David Lee', 'david.lee@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty'),
(4, 'Dr. Maria Garcia', 'maria.garcia@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty');

INSERT IGNORE INTO rooms (id, name, capacity, type, building, floor, equipment, status, notes) VALUES
(1, 'Room A-301', 30, 'Lecture', 'Building A', '3rd Floor', 'Projector, Whiteboard, Wi-Fi', 'available', 'Standard lecture room'),
(2, 'Room A-302', 40, 'Lecture', 'Building A', '3rd Floor', 'Projector, Computer, Wi-Fi', 'available', 'Lecture room with instructor computer'),
(3, 'Lab C-105', 25, 'Lab', 'Building C', '1st Floor', 'Computers, Wi-Fi, Projector', 'available', 'Computer lab'),
(4, 'Auditorium B', 200, 'Auditorium', 'Building B', 'Ground Floor', 'Audio System, Projector, Stage', 'available', 'Large event space'),
(5, 'Room D-202', 35, 'Lecture', 'Building D', '2nd Floor', 'Whiteboard, Wi-Fi', 'maintenance', 'HVAC maintenance scheduled'),
(6, 'Room E-101', 20, 'Seminar', 'Building E', '1st Floor', 'TV Display, Wi-Fi', 'available', 'Small seminar room'),
(7, 'Lab C-106', 30, 'Lab', 'Building C', '1st Floor', 'Computers, Wi-Fi, Printer', 'available', 'Computer lab with printer'),
(8, 'Room B-205', 45, 'Lecture', 'Building B', '2nd Floor', 'Projector, Whiteboard, Wi-Fi, Computer', 'available', 'Medium lecture room');

INSERT IGNORE INTO issues (id, user_id, room_id, title, room_name, category, description, status, priority, upvotes, has_document, is_affecting_booking, related_booking, admin_response) VALUES
(1, 2, 1, 'Projector flickering during presentations', 'Room A-301', 'Projector/Equipment Issue', 'The projector flickers every few minutes and makes slides hard to read.', 'Under Review', 'High', 7, 0, 1, 'Software Engineering Extra Class', 'Maintenance team has been notified.'),
(2, 3, 5, 'Schedule conflict for Room D-202', 'Room D-202', 'Schedule Conflict', 'An extra class and club workshop appear to be assigned to the same time.', 'In Progress', 'Urgent', 4, 0, 1, 'Workshop - Tech Club', NULL),
(3, 4, 3, 'AC not working in Lab C-105', 'Lab C-105', 'AC/Fan/Light Problem', 'The air conditioning has not been working for the past two days.', 'Open', 'Medium', 3, 0, 0, NULL, NULL);

INSERT IGNORE INTO issue_comments (id, issue_id, user_id, author_name, author_role, message) VALUES
(1, 1, 2, 'Dr. Sarah Johnson', 'faculty', 'Confirmed during my morning lecture.'),
(2, 3, 4, 'Dr. Maria Garcia', 'faculty', 'Students reported the same issue yesterday.');
