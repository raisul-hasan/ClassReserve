-- Run this only if you already imported an older ClassReserve schema.
ALTER TABLE rooms
  ADD COLUMN floor VARCHAR(50) DEFAULT NULL,
  ADD COLUMN equipment TEXT,
  ADD COLUMN status ENUM('available','booked','maintenance','disabled') NOT NULL DEFAULT 'available';

INSERT IGNORE INTO rooms (id, name, capacity, type, building, floor, equipment, status, notes) VALUES
(1, 'Room A-301', 30, 'Lecture', 'Building A', '3rd Floor', 'Projector, Whiteboard, Wi-Fi', 'available', 'Standard lecture room'),
(2, 'Room A-302', 40, 'Lecture', 'Building A', '3rd Floor', 'Projector, Computer, Wi-Fi', 'available', 'Lecture room with instructor computer'),
(3, 'Lab C-105', 25, 'Lab', 'Building C', '1st Floor', 'Computers, Wi-Fi, Projector', 'available', 'Computer lab'),
(4, 'Auditorium B', 200, 'Auditorium', 'Building B', 'Ground Floor', 'Audio System, Projector, Stage', 'available', 'Large event space'),
(5, 'Room D-202', 35, 'Lecture', 'Building D', '2nd Floor', 'Whiteboard, Wi-Fi', 'maintenance', 'HVAC maintenance scheduled'),
(6, 'Room E-101', 20, 'Seminar', 'Building E', '1st Floor', 'TV Display, Wi-Fi', 'available', 'Small seminar room'),
(7, 'Lab C-106', 30, 'Lab', 'Building C', '1st Floor', 'Computers, Wi-Fi, Printer', 'available', 'Computer lab with printer'),
(8, 'Room B-205', 45, 'Lecture', 'Building B', '2nd Floor', 'Projector, Whiteboard, Wi-Fi, Computer', 'available', 'Medium lecture room');
