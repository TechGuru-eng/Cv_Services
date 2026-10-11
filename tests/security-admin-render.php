<?php
if (PHP_SAPI !== 'cli') exit;
session_id(bin2hex(random_bytes(16)));
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin'] = true;
$_SESSION['admin_email'] = 'security-render@example.com';
session_write_close();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/MonCv/admin/security.php';
$_SERVER['SCRIPT_NAME'] = '/MonCv/admin/security.php';
chdir(__DIR__ . '/../admin');
ob_start();
require 'security.php';
$html = ob_get_clean();
if (!str_contains($html, 'Protection active') || substr_count($html, 'name="csrf_token"') < 2 || !str_contains($html, 'Bannir cette IP')) throw new RuntimeException('Security page render failed');
session_destroy();
echo "Authenticated security page rendering and CSRF fields passed.\n";
