<?php
// admin/includes/csrf.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify Token Function
function verify_csrf()
{
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Session expirée ou requête invalide (CSRF mismatch). Veuillez rafraîchir la page.');
    }
}

// HTML Helper
function csrf_field()
{
    echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}
?>