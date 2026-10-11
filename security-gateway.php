<?php
$root = __DIR__;
// Password-protected diagnostics must run even when database bootstrap fails.
$diagnosticRoute = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$diagnosticBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($diagnosticBase === '.' || $diagnosticBase === '/') $diagnosticBase = '';
if (in_array($diagnosticRoute, [$diagnosticBase . '/diagnose-site', $diagnosticBase . '/diagnose-site.php'], true)) {
    require $root . '/diagnose-site.php';
    return true;
}
require_once $root . '/includes/security-bootstrap.php';
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$originalPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($base && $base !== '.' && $base !== '/' && str_starts_with($originalPath, $base . '/')) $originalPath = substr($originalPath, strlen($base));
else $base = '';
$path = $originalPath;
if (str_contains($path, "\0") || str_contains($path, '\\') || preg_match('~(?:^|/)\.\.(?:/|$)~', $path)) {
    http_response_code(400);
    exit;
}
if (preg_match('~(?:^|/)\.(?:git|svn|env|htpasswd)|^/(?:_ops|node_modules|scratch|tmp|tools|tests|PHPMailer|premium-ebook-page)(?:/|$)|^/admin/(?:config|sql|includes)(?:/|$)|\.(?:sql|md|txt|bak|log|zip|lock|ini|sh|ya?ml)$~i', $path) && $path !== '/robots.txt') {
    http_response_code(403);
    exit;
}
if (preg_match('~^/(?:package(?:-lock)?\.json|tailwind\.config\.js|input\.css|promos_.*\.json|prospect_cols\.json)$~i', $path)) {
    http_response_code(403);
    exit;
}
$route = ltrim($path, '/');
if (str_starts_with($route, 'includes/')) {
    http_response_code(403);
    exit;
}
$file = null;
if ($route === 'security-gateway.php' || $route === 'security-gateway') { http_response_code(404); exit; }
if ($route === 'admin/linkedin' || $route === 'admin/linkedin/') $route = 'admin/linkedin/index.php';
if ($route === 'admin/linkedin/commandes') $route = 'admin/linkedin/commandes.php';
if ($route === 'admin/linkedin/parametres') $route = 'admin/linkedin/parametres.php';
if (preg_match('~^blog/([a-zA-Z0-9-]+)$~', $route, $match)) {
    $_GET['slug'] = $match[1];
    $file = 'article.php';
} elseif (preg_match('~^r/([a-zA-Z0-9]+)$~', $route, $match)) {
    $_GET['c'] = $match[1];
    $file = 'r.php';
} elseif (preg_match('~^linkedin/commande/([a-zA-Z0-9-]+)$~', $route, $match)) {
    $_GET['ref'] = $match[1];
    $file = 'linkedin/commande.php';
} elseif (preg_match('~^admin/linkedin/commandes/([a-zA-Z0-9-]+)$~', $route, $match)) {
    $_GET['id'] = $match[1];
    $file = 'admin/linkedin/order_details.php';
} elseif (in_array($route, ['suivi-linkedin', 'suivi-linkedin/', 'linkedin/suivi-linkedin', 'linkedin/suivi-linkedin/'], true)) {
    $file = 'linkedin/suivi.php';
} else {
    $candidate = $root . '/' . $route;
    if (is_dir($candidate)) {
        foreach (['index.html', 'index.php'] as $index) {
            if (is_file($candidate . '/' . $index)) { $file = trim($route, '/') . '/' . $index; break; }
        }
    } elseif (is_file($candidate)) {
        $file = $route;
    } else {
        foreach (['.html', '.php'] as $extension) {
            if (is_file($candidate . $extension)) { $file = $route . $extension; break; }
        }
    }
}
if ($file === null) {
    http_response_code(404);
    require $root . '/error.php';
    return true;
}
$file = ltrim($file, '/');
// Preserve the upload directory's prohibition on active content.
if (str_starts_with($file, 'uploads/') && preg_match('/\.(?:php[0-9]?|phtml|phar|pht|shtml|cgi|pl|py|sh|html?|svg|js)$/i', $file)) { http_response_code(403); exit; }
// Existing links with .php still canonicalize to the extensionless URL.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && str_ends_with($path, '.php')) {
    $target = $base . substr($path, 0, -4);
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $target . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}
if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    $types = ['html' => 'text/html; charset=UTF-8', 'htm' => 'text/html; charset=UTF-8', 'css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'pdf' => 'application/pdf', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'txt' => 'text/plain', 'xml' => 'application/xml', 'json' => 'application/json', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!isset($types[$ext])) { http_response_code(403); exit; }
    header('Content-Type: ' . $types[$ext]);
    readfile($root . '/' . $file);
    return true;
}
if (str_starts_with($file, 'uploads/')) { http_response_code(403); exit; }
$_SERVER['SCRIPT_NAME'] = $base . '/' . $file;
$_SERVER['PHP_SELF'] = $base . '/' . $file;
$_SERVER['SCRIPT_FILENAME'] = $root . '/' . $file;
chdir(dirname($root . '/' . $file));
require $root . '/' . $file;
return true;
