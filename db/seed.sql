-- Seed data for ClassReserve
-- Run after importing db/schema.sql

INSERT INTO users (name, email, password_hash, role)
VALUES
  ('Administrator', 'admin@classreserve.local', '$2y$10$kcSELHwFSH.e5DKi6tyr9.vxWMgdv49mrdUfYUeBaGtxlfe.4pOhq', 'admin');

INSERT INTO rooms (name, capacity, type, building, notes)
VALUES
  ('Auditorium A', 120, 'Auditorium', 'Main Building', 'Large room with projector and stage.'),
  ('Classroom 101', 40, 'Classroom', 'North Wing', 'Standard classroom with whiteboard.'),
  ('Conference Room B', 20, 'Conference', 'East Hall', 'Small meeting room with video conferencing support.'),
  ('Lab 202', 30, 'Computer Lab', 'Tech Center', 'Computer lab with 30 workstations.');
