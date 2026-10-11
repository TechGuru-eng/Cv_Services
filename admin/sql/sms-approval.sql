CREATE TABLE IF NOT EXISTS sms_approval_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 recipient VARCHAR(30) NOT NULL,
 message TEXT NOT NULL,
 fingerprint CHAR(64) NOT NULL,
 current_fingerprint CHAR(64) DEFAULT NULL,
 segments INT NOT NULL DEFAULT 1,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 error_message VARCHAR(255) DEFAULT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 attempted_at DATETIME DEFAULT NULL,
 UNIQUE KEY unique_message (fingerprint),
 UNIQUE KEY unique_current_message (current_fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS sms_monthly_budget (
 period CHAR(7) PRIMARY KEY,
 used INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
