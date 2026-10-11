CREATE TABLE IF NOT EXISTS transactional_notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_type VARCHAR(20) NOT NULL,
 order_id INT NOT NULL,
 event VARCHAR(40) NOT NULL,
 channel VARCHAR(10) NOT NULL,
 recipient VARCHAR(255) NOT NULL,
 subject VARCHAR(255) NOT NULL DEFAULT '',
 body MEDIUMTEXT NOT NULL,
 attempts INT NOT NULL DEFAULT 0,
 next_attempt_at DATETIME DEFAULT NULL,
 sent_at DATETIME DEFAULT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY notification_event (order_type, order_id, event, channel),
 KEY pending_delivery (sent_at, next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
