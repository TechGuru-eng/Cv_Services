<?php
// admin/includes/sync_helper.php
require_once __DIR__ . '/prospect_helper.php';

/**
 * Synchronize paid orders with the prospects list (relance).
 * Marks prospects as 'converted' if a verified order is found with matching
 * normalized Phone (WhatsApp) or Email.
 *
 * @param PDO $pdo Database connection
 * @return int Number of prospects converted
 */
function syncProspectsWithOrders($pdo) {

    // 1. Fetch all VERIFIED orders
    // We get only necessary fields.
    // We normalize phone numbers in PHP to be safe, though SQL could do some.
    $stmtOrders = $pdo->query("SELECT id, name, email, whatsapp FROM orders WHERE payment_status = 'verified'");
    $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orders)) {
        return 0;
    }

    $convertedCount = 0;

    // Prepare update statement
    // We update ONE prospect at a time to be precise, or we could do bulk updates.
    // Given the scale, iterative update is fine and safer for logic.
    $stmtUpdate = $pdo->prepare("UPDATE prospects SET status = 'converted' WHERE id = ?");

    // 2. Fetch all PENDING/ABANDONED prospects
    // We don't want to check already converted ones
    $stmtProspects = $pdo->query("SELECT id, name, email, whatsapp FROM prospects WHERE status != 'converted'");
    $prospects = $stmtProspects->fetchAll(PDO::FETCH_ASSOC);

    foreach ($prospects as $prospect) {
        $isMatch = false;


        // Iterate through verified orders to find a match
        // Optimization: For very large datasets, this O(N*M) is bad.
        // But for < 10k records it's instantaneous.
        // If it grows, we should move logic to SQL queries.
        foreach ($orders as $order) {

            if (prospectMatches($prospect, $order)) {
                $isMatch = true;
                break;
            }
        }

        if ($isMatch) {
            $stmtUpdate->execute([$prospect['id']]);
            $convertedCount++;
        }
    }

    return $convertedCount;
}
