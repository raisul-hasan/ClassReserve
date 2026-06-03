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

-- Run this ALTER only if you created the issues table before this upload field was added.
-- ALTER TABLE issues ADD COLUMN uploaded_path VARCHAR(255) AFTER has_document;

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

INSERT IGNORE INTO issues (id, user_id, room_id, title, room_name, category, description, status, priority, upvotes, has_document, is_affecting_booking, related_booking, admin_response) VALUES
(1, 2, 1, 'Projector flickering during presentations', 'Room A-301', 'Projector/Equipment Issue', 'The projector flickers every few minutes and makes slides hard to read.', 'Under Review', 'High', 7, 0, 1, 'Software Engineering Extra Class', 'Maintenance team has been notified.'),
(2, 3, 5, 'Schedule conflict for Room D-202', 'Room D-202', 'Schedule Conflict', 'An extra class and club workshop appear to be assigned to the same time.', 'In Progress', 'Urgent', 4, 0, 1, 'Workshop - Tech Club', NULL),
(3, 4, 3, 'AC not working in Lab C-105', 'Lab C-105', 'AC/Fan/Light Problem', 'The air conditioning has not been working for the past two days.', 'Open', 'Medium', 3, 0, 0, NULL, NULL);

INSERT IGNORE INTO issue_comments (id, issue_id, user_id, author_name, author_role, message) VALUES
(1, 1, 2, 'Dr. Sarah Johnson', 'faculty', 'Confirmed during my morning lecture.'),
(2, 3, 4, 'Dr. Maria Garcia', 'faculty', 'Students reported the same issue yesterday.');
