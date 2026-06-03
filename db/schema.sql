-- Minimal schema for ClassReserve
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','club','faculty','admin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  capacity INT NOT NULL DEFAULT 0,
  type VARCHAR(100) DEFAULT NULL,
  building VARCHAR(100) DEFAULT NULL,
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

-- Demo login accounts for local testing.
-- Password for all demo accounts: ClassReserve123!
INSERT IGNORE INTO users (id, name, email, password_hash, role) VALUES
(1, 'System Admin', 'admin@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'admin'),
(2, 'Dr. Sarah Johnson', 'sarah.johnson@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty'),
(3, 'Prof. David Lee', 'david.lee@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty'),
(4, 'Dr. Maria Garcia', 'maria.garcia@classreserve.test', '$2y$10$Oln95RkA3WZEh0rfEjRscuvVGOAUiRoLitINzz9lOhUGtj.ndIgbO', 'faculty');
