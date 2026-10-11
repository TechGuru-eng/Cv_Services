<?php
// api/get_sponsorship_stats.php
header('Content-Type: application/json');
require_once '../admin/config/db.php';

try {
    // 1. Total Sponsored (Credits Total sum)
    $stmtTotal = $pdo->query("
        SELECT SUM(p.credits_total) as total 
        FROM sponsorship_pool p
        JOIN orders o ON p.order_id = o.id
        WHERE TRIM(UPPER(o.payment_status)) IN ('VERIFIED', 'PAID', 'CONFIRMED', 'COMPLETED')
    ");
    $totalSponsored = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 2. Credits Available (Credits Remaining sum)
    $stmtAvail = $pdo->query("
        SELECT SUM(p.credits_remaining) as available 
        FROM sponsorship_pool p
        JOIN orders o ON p.order_id = o.id
        WHERE TRIM(UPPER(o.payment_status)) IN ('VERIFIED', 'PAID', 'CONFIRMED', 'COMPLETED')
    ");
    $creditsAvailable = $stmtAvail->fetch(PDO::FETCH_ASSOC)['available'] ?? 0;

    // 3. Recent Sponsors (Donors)
    $stmtRecent = $pdo->query("
        SELECT p.donor_name, p.package_name, p.credits_total, p.credits_remaining 
        FROM sponsorship_pool p
        JOIN orders o ON p.order_id = o.id
        WHERE TRIM(UPPER(o.payment_status)) IN ('VERIFIED', 'PAID', 'CONFIRMED', 'COMPLETED')
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $donors = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    $formattedList = [];
    foreach($donors as $d) {
        $formattedList[] = [
            'donor_name' => $d['donor_name'],
            'package_name' => $d['package_name'],
            'credits_total' => $d['credits_total'],
            'credits_remaining' => $d['credits_remaining']
        ];
    }

    // 4. Beneficiaries (Approved Requests)
    // Check if table exists first to avoid error if migration not run
    $beneficiaries = [];
    try {
        // SQL: Join with sponsorship_pool to get package info
        // Attempt to find allocated_pool_id column
        // We use a safe join that works even if column is missing (using check) or assume it exists based on debug
        // Based on debug step 244: allocated_pool_id EXISTS.
        
        $stmtBen = $pdo->query("
            SELECT r.name, r.motivation, r.created_at, r.is_realized, r.recovery_link, p.package_name 
            FROM sponsorship_requests r
            LEFT JOIN sponsorship_pool p ON r.allocated_pool_id = p.id
            WHERE r.status IN ('approved', 'granted', 'completed') 
            ORDER BY r.created_at DESC 
            LIMIT 10
        ");
        $beneficiaries = $stmtBen->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback if column missing or other error
    }

    echo json_encode([
        'success' => true,
        'total_sponsored' => $totalSponsored,
        'credits_available' => $creditsAvailable,
        'donors' => $formattedList,
        'beneficiaries' => $beneficiaries
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
