<?php
function ensureAdminPageTable(PDO $pdo, string $table): void {
    $definitions = array (
  'cv_builder_leads' => 'CREATE TABLE IF NOT EXISTS `cv_builder_leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `whatsapp` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `slug` varchar(20) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `cv_data` longtext,
  `converted` tinyint(1) DEFAULT \'0\',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'before_after_cvs' => 'CREATE TABLE IF NOT EXISTS `before_after_cvs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(150) NOT NULL DEFAULT 0xF09F92BC2047657374696F6E202620446972656374696F6E,
  `before_img` varchar(255) NOT NULL,
  `before_tag` varchar(150) DEFAULT \'CV Ordinaire (Rejeté)\',
  `before_score` varchar(50) DEFAULT \'Score ATS : 28%\',
  `before_quote` text,
  `after_img` varchar(255) NOT NULL,
  `after_tag` varchar(150) DEFAULT \'HireMe CV (Recruté)\',
  `after_score` varchar(50) DEFAULT \'Score ATS : 98%\',
  `after_badge` varchar(100) DEFAULT \'ATS OPTIMIZED\',
  `after_quote` text,
  `display_order` int DEFAULT \'0\',
  `is_active` tinyint(1) DEFAULT \'1\',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
);
    if (!isset($definitions[$table])) throw new InvalidArgumentException('Unknown schema');
    $check = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
    $check->execute([$table]);
    if (!$check->fetchColumn()) $pdo->exec($definitions[$table]);
}
