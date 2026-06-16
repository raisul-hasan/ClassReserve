-- Adds structured academic details for faculty bookings.
-- Class Type is stored in the existing bookings.purpose column.
-- Run this against the configured ClassReserve database before saving the new faculty fields.

DROP PROCEDURE IF EXISTS add_faculty_academic_booking_fields;

DELIMITER //
CREATE PROCEDURE add_faculty_academic_booking_fields()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'bookings'
          AND COLUMN_NAME = 'course_code'
    ) THEN
        ALTER TABLE bookings ADD COLUMN course_code VARCHAR(50) NULL AFTER purpose;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'bookings'
          AND COLUMN_NAME = 'section'
    ) THEN
        ALTER TABLE bookings ADD COLUMN `section` VARCHAR(50) NULL AFTER course_code;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'bookings'
          AND COLUMN_NAME = 'batch'
    ) THEN
        ALTER TABLE bookings ADD COLUMN batch VARCHAR(50) NULL AFTER `section`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'bookings'
          AND COLUMN_NAME = 'department'
    ) THEN
        ALTER TABLE bookings ADD COLUMN department VARCHAR(100) NULL AFTER batch;
    END IF;
END//
DELIMITER ;

CALL add_faculty_academic_booking_fields();
DROP PROCEDURE add_faculty_academic_booking_fields;
