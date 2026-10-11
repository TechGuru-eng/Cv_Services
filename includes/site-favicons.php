<?php
// Pass a URL prefix relative to the document's base URL when hosted in a subdirectory.
$faviconBase = isset($faviconBase) ? $faviconBase : '/';
$faviconBase = htmlspecialchars(rtrim($faviconBase, '/') . '/', ENT_QUOTES, 'UTF-8');
?>
<link rel="icon" type="image/png" sizes="48x48" href="<?= $faviconBase ?>assets/favicons/favicon-48.png?v=20261011">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $faviconBase ?>assets/favicons/favicon-32.png?v=20261011">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $faviconBase ?>assets/favicons/favicon-16.png?v=20261011">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $faviconBase ?>assets/favicons/apple-touch-icon-180.png?v=20261011">
