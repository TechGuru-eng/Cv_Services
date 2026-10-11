<?php
// admin/auth.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/activity_helper.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>