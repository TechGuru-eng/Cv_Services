<?php
// includes/upload_helper.php
// Shared, validated file-upload handling (extension + real MIME + size + random name).

const UPLOAD_MIME_MAP = [
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'gif'  => ['image/gif'],
    'webp' => ['image/webp'],
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword', 'application/octet-stream', 'application/x-ole-storage'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
];

/**
 * Validate and store an uploaded file.
 *
 * @param array  $file        An entry of $_FILES
 * @param array  $allowedExts e.g. ['jpg','png','pdf']
 * @param string $destDir     Directory (relative to web root, no trailing slash), created if missing
 * @param string $prefix      Optional filename prefix (letters, digits, dash only)
 * @param int    $maxBytes    Max size, default 5 MB
 * @return string|null        Relative path on success, null on any failure
 */
function secure_upload(array $file, array $allowedExts, string $destDir, string $prefix = '', int $maxBytes = 5242880): ?string
{
    if (!isset($file['error'], $file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) return null;

    // Only the LAST extension counts, and it must be whitelisted (blocks shell.php.jpg style tricks via real MIME check below)
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true) || !isset(UPLOAD_MIME_MAP[$ext])) return null;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, UPLOAD_MIME_MAP[$ext], true)) return null;

    // Reject files that smuggle PHP code inside an allowed container
    $head = file_get_contents($file['tmp_name'], false, null, 0, 1048576);
    if ($head !== false && preg_match('/<\?(php|=)|<script/i', $head) && in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) return null;

    $destDir = rtrim($destDir, '/');
    $absDir = __DIR__ . '/../' . $destDir;
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) return null;

    $prefix = preg_replace('/[^A-Za-z0-9-]/', '', $prefix);
    $name = $prefix . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $absDir . '/' . $name)) return null;
    @chmod($absDir . '/' . $name, 0644);

    return $destDir . '/' . $name;
}

/** Fixed-window rate limit keyed on IP, stored in the DB-less temp dir. Returns true if allowed. */
function rate_limit_ok(string $bucket, int $max, int $windowSeconds): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $file = sys_get_temp_dir() . '/moncv_rl_' . md5($bucket . '|' . $ip);
    $handle = @fopen($file, 'c+');
    if ($handle === false) return false;
    if (!flock($handle, LOCK_EX)) { fclose($handle); return false; }
    try {
        $now = time();
        $hits = array_filter(array_map('intval', explode(',', stream_get_contents($handle))), fn($t) => $t > $now - $windowSeconds);
        if (count($hits) >= $max) return false;
        $hits[] = $now;
        rewind($handle);
        if (!ftruncate($handle, 0)) return false;
        $data = implode(',', $hits);
        return fwrite($handle, $data) === strlen($data) && fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
