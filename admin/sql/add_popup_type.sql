ALTER TABLE exit_popups ADD COLUMN popup_type VARCHAR(50) DEFAULT 'standard' AFTER is_active;
ALTER TABLE exit_popups ADD COLUMN slices TEXT NULL AFTER popup_type;
