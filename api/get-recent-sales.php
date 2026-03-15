<?php
require_once '../admin/config/db.php';

header('Content-Type: application/json');

try {
    // Fetch last 20 approved orders
    $stmt = $pdo->query("SELECT name, city, package_name, created_at 
                         FROM orders 
                         WHERE payment_status IN ('approved', 'verified') 
                         ORDER BY created_at DESC 
                         LIMIT 20");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedSales = [];

    // Helper to anonymize name (e.g. "Jean Dupont" -> "Jean D.")
    function anonymize($fullName) {
        $parts = explode(' ', trim($fullName));
        if (count($parts) > 1) {
            return $parts[0] . ' ' . substr(end($parts), 0, 1) . '.';
        }
        return $fullName; // Return as is if single name
    }

    foreach ($orders as $o) {
        // Clean up package name (remove " (FLASH)")
        $pack = str_replace(' (FLASH)', '', $o['package_name']);
        
        // Calculate "Time Ago" string (e.g. "Il y a 5 min")
        $timeDiff = time() - strtotime($o['created_at']);
        $timeAgo = "À l'instant";
        if ($timeDiff > 60) {
            $mins = floor($timeDiff / 60);
            $timeAgo = "Il y a $mins min";
        }
        if ($timeDiff > 3600) {
            $hours = floor($timeDiff / 3600);
            $timeAgo = "Il y a $hours h";
        }

        $formattedSales[] = [
            'name' => anonymize($o['name']),
            'city' => !empty($o['city']) ? $o['city'] : 'Cameroun',
            'pack' => $pack,
            'time' => $timeAgo
        ];
    }

    // Add some "filler" if not enough real sales (Optional, maybe user wants ONLY real)
    // For now, let's just return what we have. Frontend can handle empty state or fallback.
    
    echo json_encode($formattedSales);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
