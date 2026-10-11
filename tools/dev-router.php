<?php
// PHP's development server does not read .htaccess. Mirror public routes locally.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
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
if (str_starts_with($route, 'includes/pdf/')) {
    http_response_code(403);
    exit;
}
$file = null;
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
if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    if (is_file($root . '/' . $route)) return false;
    header('Content-Type: text/html; charset=UTF-8');
    readfile($root . '/' . $file);
    return true;
}
$_SERVER['SCRIPT_NAME'] = '/' . $file;
$_SERVER['PHP_SELF'] = '/' . $file;
$_SERVER['SCRIPT_FILENAME'] = $root . '/' . $file;
chdir(dirname($root . '/' . $file));
require $root . '/' . $file;
return true;
