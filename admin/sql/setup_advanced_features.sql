CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_email VARCHAR(255) NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add tracking columns to prospects for Kanban
ALTER TABLE prospects ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE prospects ADD COLUMN IF NOT EXISTS priority ENUM('low', 'medium', 'high') DEFAULT 'medium';
ALTER TABLE prospects ADD COLUMN IF NOT EXISTS notes TEXT NULL;
ALTER TABLE prospects ADD COLUMN IF NOT EXISTS position INT DEFAULT 0;
