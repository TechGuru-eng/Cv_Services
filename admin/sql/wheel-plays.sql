CREATE TABLE IF NOT EXISTS exit_wheel_plays (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  browser_hash CHAR(64) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  reward_percent INT NOT NULL,
  promo_code VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_browser (browser_hash),
  UNIQUE KEY unique_ip (ip_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
