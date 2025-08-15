-- PRO updates: schema wijzigingen voor reminders + performance
ALTER TABLE appointments
  ADD COLUMN IF NOT EXISTS reminder24_sent TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS reminder2_sent  TINYINT(1) NOT NULL DEFAULT 0;

-- Indexen
CREATE INDEX IF NOT EXISTS idx_appt_start ON appointments (start);
CREATE INDEX IF NOT EXISTS idx_appt_instructor_start ON appointments (instructor_id, start);
