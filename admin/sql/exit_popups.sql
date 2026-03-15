CREATE TABLE IF NOT EXISTS `exit_popups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_percent` int NOT NULL,
  `cta_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'PROFITER DE L''OFFRE',
  `is_active` tinyint(1) DEFAULT '0',
  `popup_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'standard',
  `slices` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
