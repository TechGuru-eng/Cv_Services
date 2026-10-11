<?php
// api/get_exit_popup.php
session_start();
require_once __DIR__ . '/../admin/config/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/../includes/wheel-promos.php';

try {
    $browser = $_GET['browser'] ?? '';
    $stmtPlayed = $pdo->prepare('SELECT id FROM exit_wheel_plays WHERE ip_hash = ? OR browser_hash = ? LIMIT 1');
    $stmtPlayed->execute([hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''), hash('sha256', is_string($browser) ? $browser : '')]);
    if (!empty($_SESSION['exit_wheel_order_placed']) || $stmtPlayed->fetchColumn()) {
        echo json_encode(['success' => false, 'played' => true]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM exit_popups WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $popup = $stmt->fetch();

    if ($popup) {
        // Decode slices JSON if it exists as a string
        if (!empty($popup['slices']) && is_string($popup['slices'])) {
            $popup['slices'] = json_decode($popup['slices']);
        }



        if (($popup['popup_type'] ?? '') === 'spin_wheel') {
            $popup['slices'] = wheel_rewards(json_decode(json_encode($popup['slices'] ?? []), true) ?: [], wheel_promos($pdo));
            if (count($popup['slices']) !== 4) {
                echo json_encode(['success' => false, 'message' => 'Wheel rewards are not fully configured']);
                exit;
            }
        }
        if (($popup['popup_type'] ?? '') === 'spin_wheel') {
        array_unshift($popup['slices'], ['text' => 'Pas de réduction', 'value' => 0, 'color' => '#64748b']);
        foreach ($popup['slices'] as &$slice) unset($slice['code']);
        unset($slice);
        unset($popup['discount_code']);
        $_SESSION['exit_wheel_token'] = $_SESSION['exit_wheel_token'] ?? bin2hex(random_bytes(32));
        $popup['token'] = $_SESSION['exit_wheel_token'];
        }
        echo json_encode([
            'success' => true,
            'data' => $popup
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No active popup'
        ]);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Popup unavailable']);
}
