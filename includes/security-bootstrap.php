<?php
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../admin/includes/Security.php';
Security::init($pdo);
Security::checkBan();
Security::detectMaliciousInputs();
