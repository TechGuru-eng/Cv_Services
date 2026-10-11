<?php
// admin/includes/security_helper.php

/**
 * Checks if a phone number or email is blacklisted.
 * Identifiers are normalized (no spaces, lowercase).
 */
function isBlacklisted($identifier, $pdo) {
    if (empty($identifier)) return false;
    
    // Normalize phone (remove all non-digits, take last 9 for CM/Local match)
    // and email (lowercase, trim)
    $cleanId = trim(strtolower($identifier));
    $phoneOnly = preg_replace('/[^0-9]/', '', $identifier);
    
    // Search by full indentifier or by normalized phone (if it looks like a phone)
    $sql = "SELECT id FROM blacklisted_clients WHERE identifier = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cleanId]);
    if ($stmt->fetch()) return true;

    if (strlen($phoneOnly) >= 9) {
        // Check for phone number specifically (matches last 9 digits common in Cameroon numbers)
        $shortPhone = substr($phoneOnly, -9);
        $sqlPhone = "SELECT id FROM blacklisted_clients WHERE identifier LIKE ?";
        $stmtPhone = $pdo->prepare($sqlPhone);
        $stmtPhone->execute(["%$shortPhone"]);
        if ($stmtPhone->fetch()) return true;
    }

    return false;
}

/**
 * Bans a client by adding both their phone and email to the blacklist.
 */
function banClient($phone, $email, $reason, $pdo) {
    $identifiers = [];
    if (!empty($phone)) $identifiers[] = ['val' => trim($phone), 'type' => 'phone'];
    if (!empty($email)) $identifiers[] = ['val' => trim(strtolower($email)), 'type' => 'email'];

    foreach ($identifiers as $id) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO blacklisted_clients (identifier, type, reason) VALUES (?, ?, ?)");
            $stmt->execute([$id['val'], $id['type'], $reason]);
        } catch (Exception $e) {
            // Ignore duplicates
        }
    }
}
?>
