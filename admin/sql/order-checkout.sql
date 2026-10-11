-- Apply once before deploying the updated checkout.
ALTER TABLE orders MODIFY package_name VARCHAR(1000) NOT NULL;
CREATE TABLE IF NOT EXISTS order_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    event VARCHAR(40) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY order_event (order_id, event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
