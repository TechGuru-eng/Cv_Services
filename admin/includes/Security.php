<?php
class Security {
    private static $pdo;

    public static function init($pdo_instance) {
        self::$pdo = $pdo_instance;
    }

    public static function getIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '127.0.0.1'; // Fallback for CLI or unknown
    }

    public static function checkBan() {
        if (!self::$pdo) return;
        
        $ip = self::getIP();
        $stmt = self::$pdo->prepare("SELECT * FROM banned_ips WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $ban = $stmt->fetch();

        if ($ban) {
            die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
                    <h1 style='color:#d9534f;'>Accès Refusé / Access Denied</h1>
                    <p>Votre adresse IP ($ip) a été bannie pour la raison suivante :</p>
                    <p><strong>" . htmlspecialchars($ban['reason']) . "</strong></p>
                    <p>Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur.</p>
                 </div>");
        }
    }

    public static function logSuspicious($reason) {
        if (!self::$pdo) return;

        $ip = self::getIP();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'Unknown';

        $stmt = self::$pdo->prepare("INSERT INTO security_logs (ip_address, user_agent, request_uri, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ip, $ua, $uri, $reason]);
    }

    public static function banIP($ip, $reason, $admin_name = 'System') {
        if (!self::$pdo) return false;

        // Check if already banned
        $stmt = self::$pdo->prepare("SELECT id FROM banned_ips WHERE ip_address = ?");
        $stmt->execute([$ip]);
        if ($stmt->fetch()) {
            return false; // Already banned
        }

        $stmt = self::$pdo->prepare("INSERT INTO banned_ips (ip_address, reason, banned_by) VALUES (?, ?, ?)");
        return $stmt->execute([$ip, $reason, $admin_name]);
    }

    public static function unbanIP($id) {
        if (!self::$pdo) return false;
        $stmt = self::$pdo->prepare("DELETE FROM banned_ips WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Basic detection of malicious patterns in GET/POST
    public static function detectMaliciousInputs() {
        $patterns = [
            '/union\s+select/i',
            '/loading_file/i',
            '/information_schema/i',
            '/<script>/i',
            '/alert\(/i'
        ];

        foreach ($_REQUEST as $key => $value) {
            if (is_array($value)) continue; // Skip arrays for now
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    self::logSuspicious("Malicious Input Detected: " . htmlspecialchars($value));
                    // Optional: Auto-ban? Or just die?
                    die("Paramètre illégal détecté.");
                }
            }
        }
    }
}
?>
