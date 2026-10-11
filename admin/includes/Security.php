<?php
class Security {
    private static ?PDO $pdo = null;
    public static function init(PDO $pdo): void { self::$pdo = $pdo; }
    public static function getIP(): string { return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; }
    public static function deny(string $message): never {
        http_response_code(403);
        header('Cache-Control: no-store');
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json; charset=UTF-8');
            exit(json_encode(['success' => false, 'message' => $message]));
        }
        header('Content-Type: text/html; charset=UTF-8');
        exit('<h1>Accès refusé / Access denied</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>');
    }
    public static function bansEnabled(): bool {
        $stmt = self::$pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ip_bans_enabled'");
        $stmt->execute();
        return $stmt->fetchColumn() !== '0';
    }
    public static function checkBan(): void {
        if (!self::bansEnabled()) return;
        $stmt = self::$pdo->prepare('SELECT id FROM banned_ips WHERE ip_address = ?');
        $stmt->execute([self::getIP()]);
        if ($stmt->fetch()) self::deny('Cette adresse IP est bloquée. Contactez le support en cas d’erreur.');
    }
    public static function logSuspicious(string $reason): void {
        if (!self::$pdo) return;
        // Do not store query strings, passwords or submitted personal information.
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $stmt = self::$pdo->prepare('INSERT INTO security_logs (ip_address, user_agent, request_uri, reason) VALUES (?, ?, ?, ?)');
        $stmt->execute([self::getIP(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255), mb_substr($uri, 0, 255), mb_substr($reason, 0, 1000)]);
    }
    public static function banIP(string $ip, string $reason, string $admin = 'System'): bool {
        if ($admin === 'System' && !self::bansEnabled()) return false;
        if (!filter_var($ip, FILTER_VALIDATE_IP)) throw new InvalidArgumentException('Adresse IP invalide.');
        if (in_array($ip, ['127.0.0.1', '::1'], true) || ($admin !== 'System' && $ip === self::getIP())) throw new InvalidArgumentException('Impossible de bannir localhost ou votre propre IP.');
        $stmt = self::$pdo->prepare('INSERT IGNORE INTO banned_ips (ip_address, reason, banned_by) VALUES (?, ?, ?)');
        $stmt->execute([$ip, mb_substr($reason, 0, 1000), mb_substr($admin, 0, 100)]);
        return $stmt->rowCount() > 0;
    }
    public static function unbanIP(int $id): bool {
        $stmt = self::$pdo->prepare('DELETE FROM banned_ips WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    public static function detect(array $input, string $path): ?string {
        $path = rawurldecode($path);
        if (preg_match('~(?:^|/)(?:\.env(?:\.|/|$)|\.git(?:/|$)|wp-config\.php|phpinfo\.php)|\.\./~i', $path)) return 'Sonde de fichier sensible / traversal';
        $patterns = [
            'Injection SQL' => '~\bunion\s+(?:all\s+)?select\b|\b(?:sleep|benchmark)\s*\(\s*\d|\bload_file\s*\(|[\"\']\s*or\s+[0-9]+\s*=\s*[0-9]+\s*(?:--|#)~i',
            'Script injecté (XSS)' => '~<\s*script\b|<\s*(?:img|svg|iframe)\b[^>]*\bon\w+\s*=~i',
            'Traversal de fichier' => '~(?:\.\./){2,}~',
        ];
        $walk = function (array $values, int $depth = 0) use (&$walk, $patterns): ?string {
            if ($depth > 12) return null;
            foreach ($values as $key => $value) {
                if (in_array(strtolower((string)$key), ['password', 'csrf_token'], true)) continue;
                if (is_array($value)) { if ($hit = $walk($value, $depth + 1)) return $hit; continue; }
                if (!is_string($value)) continue;
                foreach ($patterns as $label => $pattern) if (preg_match($pattern, $value)) return $label;
            }
            return null;
        };
        return $walk($input);
    }
    public static function recordAttack(string $reason): void {
        self::logSuspicious($reason);
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM security_logs WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND reason IN ('Injection SQL', 'Script injecté (XSS)', 'Traversal de fichier', 'Sonde de fichier sensible / traversal')");
        $stmt->execute([self::getIP()]);
        if ((int)$stmt->fetchColumn() >= 3 && !in_array(self::getIP(), ['127.0.0.1', '::1'], true)) self::banIP(self::getIP(), 'Automatique : 3 requêtes malveillantes en 15 minutes.');
    }
    public static function recordFailedLogin(): void {
        self::logSuspicious('Échec de connexion administrateur');
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM security_logs WHERE ip_address = ? AND reason = 'Échec de connexion administrateur' AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([self::getIP()]);
        if ((int)$stmt->fetchColumn() >= 8 && !in_array(self::getIP(), ['127.0.0.1', '::1'], true)) self::banIP(self::getIP(), 'Automatique : 8 échecs de connexion en 15 minutes.');
    }
    public static function detectMaliciousInputs(): void {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $input = ['query' => $_GET, 'form' => $_POST];
        // Ban reasons are stored as escaped text and can quote an attack from the journal.
        if (preg_match('~/admin/security(?:\.php)?$~', $path)) unset($input['form']['reason']);
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) $input['json'] = $json;
        }
        if ($reason = self::detect($input, $path)) {
            self::recordAttack($reason);
            self::deny('Requête suspecte bloquée. / Suspicious request blocked.');
        }
    }
}
