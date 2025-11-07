-- Migration to add start_time and end_time columns
-- Run this if you already have the database set up

-- Add start_time and end_time columns to time_entries table
ALTER TABLE time_entries
ADD COLUMN start_time TIME DEFAULT NULL AFTER work_date,
ADD COLUMN end_time TIME DEFAULT NULL AFTER start_time;

-- For existing records, set default times (can be updated manually later)
UPDATE time_entries
SET start_time = '09:00:00',
    end_time = DATE_ADD(start_time, INTERVAL hours HOUR)
WHERE start_time IS NULL;

-- Make the columns required (NOT NULL)
ALTER TABLE time_entries
MODIFY COLUMN start_time TIME NOT NULL,
MODIFY COLUMN end_time TIME NOT NULL;
