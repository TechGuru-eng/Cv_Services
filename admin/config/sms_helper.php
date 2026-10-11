<?php
require_once __DIR__ . '/../../includes/sms-approval.php';
class SMSHelper {
    private static $pdo = null;

    private static function init() {
        if (self::$pdo === null) {
            // Read configuration from the active database
            global $pdo;
            if (isset($pdo)) {
                self::$pdo = $pdo;
            } else {
                require_once __DIR__ . '/db.php';
                self::$pdo = $pdo;
            }
        }
    }

    /**
     * Formats phone numbers to standard international format (defaults to +237 for Cameroon)
     */
    public static function formatPhoneNumber($phone) {
        // Strip all non-numeric characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        if (empty($phone)) {
            return '';
        }

        // If it starts with +, return as is
        if (strpos($phone, '+') === 0) {
            return $phone;
        }

        // If it starts with 00, replace with +
        if (strpos($phone, '00') === 0) {
            return '+' . substr($phone, 2);
        }

        // Cameroon standard 9-digit mobile numbers starting with 6 (e.g. 690034617)
        if (preg_match('/^6[25789]\d{7}$/', $phone)) {
            return '+237' . $phone;
        }

        // Default: If no country code prefix is clear, prepend +237
        if (strlen($phone) === 9) {
            return '+237' . $phone;
        }

        return '+' . $phone;
    }

    /**
     * Sends an SMS via the TextBee API gateway and logs the result
     * @param string $recipient
     * @param string $message
     * @return array ['success' => bool, 'error' => string]
     */
    public static function sendSMS($recipient, $message, $shortenUrls = true) {
        self::init();
        try { return SMSApproval::queue(self::$pdo, (string)$recipient, (string)$message); }
        catch (Throwable $e) { return ['success'=>false,'error'=>'Impossible de mettre le SMS en attente d’approbation.']; }
    }

    public static function approveSMS(int $id, string $csrf): array {
        self::init();
        return SMSApproval::approve(self::$pdo, $id, $csrf, function ($recipient, $message) {
            return self::deliverSMS($recipient, $message, false);
        });
    }

    public static function sendOrderSMS(int $notificationId): array {
        self::init();
        $stmt = self::$pdo->prepare("SELECT * FROM transactional_notifications WHERE id=? AND channel='sms' AND ((order_type IN ('cv','linkedin') AND event IN ('completed','approved')) OR (order_type='chariow' AND event='completed'))");
        $stmt->execute([$notificationId]);
        $item=$stmt->fetch();
        if (!$item) return ['success'=>false,'error'=>'Notification de commande invalide.'];
        // Only server-created order events qualify for automatic sending.
        $queued=SMSApproval::queue(self::$pdo, $item['recipient'], $item['body']);
        if (!$queued['success']) return $queued;
        $hash=hash('sha256',self::formatPhoneNumber($item['recipient'])."\n".preg_replace('/\s+/u',' ',trim($item['body'])));
        $stmt=self::$pdo->prepare('SELECT id, message FROM sms_approval_requests WHERE fingerprint=?');
        $stmt->execute([$hash]);$request=$stmt->fetch();
        if (!$request || $request['message'] !== $item['body']) return ['success'=>false,'terminal'=>true,'error'=>'Notification modifiée : approbation manuelle requise.'];
        return SMSApproval::dispatch(self::$pdo,(int)$request['id'],function ($recipient,$message) {
            return self::deliverSMS($recipient,$message,false);
        });
    }

    private static function deliverSMS($recipient, $message, $shortenUrls = true) {
        self::init();

        try {
            // 1. Fetch SMS settings from database
            $stmt = self::$pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('sms_enabled', 'textbee_api_key', 'textbee_device_id')");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $smsEnabled = getenv('SMS_ENABLED') !== false ? getenv('SMS_ENABLED') : ($settings['sms_enabled'] ?? '0');
            $apiKey = getenv('TEXTBEE_API_KEY') ?: ($settings['textbee_api_key'] ?? '');
            $deviceId = getenv('TEXTBEE_DEVICE_ID') ?: ($settings['textbee_device_id'] ?? '');

            if ($smsEnabled !== '1') {
                return ['success' => false, 'error' => 'L\'envoi de SMS est désactivé dans les paramètres.'];
            }

            if (empty($apiKey) || empty($deviceId)) {
                return ['success' => false, 'error' => 'Configuration SMS incomplète (clé API ou Device ID manquants).'];
            }

            // 2. Format phone number
            $formattedPhone = self::formatPhoneNumber($recipient);
            if (empty($formattedPhone)) {
                return ['success' => false, 'error' => 'Numéro de téléphone invalide ou vide.'];
            }

            // Shorten URLs in text to save characters!
            if ($shortenUrls) $message = self::shortenUrlsInText($message);

            // 3. Make cURL POST request to TextBee
            $url = 'https://api.textbee.dev/api/v1/gateway/send-sms';
            $headers = [
                "x-api-key: " . $apiKey,
                "Content-Type: application/json"
            ];
            $payload = [
                "deviceId" => $deviceId,
                "recipients" => [$formattedPhone],
                "message" => $message
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // 4. Parse response
            $status = 'error';
            $errorMessage = null;

            if ($curlError) {
                $errorMessage = "cURL Error: " . $curlError;
            } else {
                $resData = json_decode($response, true);
                if ($httpCode >= 200 && $httpCode < 300) {
                    $status = 'success';
                } else {
                    $errorMessage = $resData['message'] ?? $resData['error'] ?? "HTTP Error Code " . $httpCode;
                }
            }

            // 5. Log the SMS in sms_logs
            try {
                $stmtLog = self::$pdo->prepare("INSERT INTO sms_logs (recipient, message, status, error_message) VALUES (?, ?, ?, ?)");
                $stmtLog->execute([$formattedPhone, $message, $status, $errorMessage]);
            } catch (Exception $logError) {
                // A logging failure must not resend an SMS already accepted by TextBee.
                error_log('SMS log write failed.');
            }

            if ($status === 'success') {
                return ['success' => true, 'error' => ''];
            } else {
                return ['success' => false, 'error' => $errorMessage];
            }

        } catch (Exception $e) {
            // Fail-safe logging
            try {
                $stmtLog = self::$pdo->prepare("INSERT INTO sms_logs (recipient, message, status, error_message) VALUES (?, ?, 'error', ?)");
                $stmtLog->execute([$recipient, $message, "Exception: " . $e->getMessage()]);
            } catch (Exception $ex) {}

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generates a unique 6-character short code
     */
    private static function generateUniqueCode() {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        for ($i = 0; $i < 10; $i++) {
            $code = "";
            for ($j = 0; $j < 6; $j++) {
                $code .= $chars[rand(0, strlen($chars) - 1)];
            }
            // Check uniqueness
            $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM short_links WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() == 0) {
                return $code;
            }
        }
        return substr(md5(uniqid()), 0, 6);
    }

    /**
     * Finds and shortens URLs belonging to this domain inside a text message
     */
    public static function shortenUrlsInText($message) {
        self::init();
        
        // Match URLs belonging to our domain or localhost
        $pattern = '/https?:\/\/(?:[a-zA-Z0-9-]+\.)*(?:hiremeguide\.com|localhost)[^\s]*/i';
        
        if (preg_match_all($pattern, $message, $matches)) {
            foreach ($matches[0] as $url) {
                // If it is already a short link, skip it!
                if (strpos($url, '/r/') !== false) {
                    continue;
                }
                
                // Check if this URL was already shortened to avoid duplicate keys
                $stmt = self::$pdo->prepare("SELECT code FROM short_links WHERE destination_url = ? LIMIT 1");
                $stmt->execute([$url]);
                $code = $stmt->fetchColumn();
                
                if (!$code) {
                    $code = self::generateUniqueCode();
                    $stmtIns = self::$pdo->prepare("INSERT INTO short_links (code, destination_url) VALUES (?, ?)");
                    $stmtIns->execute([$code, $url]);
                }
                
                // Build short URL structure
                $domain = 'https://moncvpro.hiremeguide.com';
                if (strpos($url, 'localhost') !== false) {
                    $domain = 'http://localhost/MonCV';
                }
                
                $shortUrl = $domain . '/r/' . $code;
                $message = str_replace($url, $shortUrl, $message);
            }
        }
        
        return $message;
    }
}
