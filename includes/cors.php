<?php
// includes/cors.php — allow cross-origin calls only from our own domain (replaces "Access-Control-Allow-Origin: *")
$__origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($__origin !== '' && preg_match('~^https://([a-z0-9-]+\.)?hiremeguide\.com$~i', $__origin)) {
    header('Access-Control-Allow-Origin: ' . $__origin);
    header('Vary: Origin');
}
