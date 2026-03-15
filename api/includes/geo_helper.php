<?php
// api/includes/geo_helper.php

/**
 * Shared geolocation logic to avoid race conditions between track.php and get_visitor_config.php
 */
function initVisitorSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Check for manual debug override
    if (isset($_GET['debug_country'])) {
        $_SESSION['visitor_country'] = $_GET['debug_country'];
    }

    // 2. Return if already set
    if (isset($_SESSION['visitor_country']) && !empty($_SESSION['visitor_country'])) {
        return $_SESSION['visitor_country'];
    }

    // 3. Detect via IP-API
    $visitor_ip = $_SERVER['REMOTE_ADDR'];
    // Handle local testing
    if ($visitor_ip === '127.0.0.1' || $visitor_ip === '::1') {
        $visitor_ip = '102.215.56.0'; // Mock an IP from Cameroon
    }

    try {
        $url = "http://ip-api.com/json/{$visitor_ip}?fields=status,message,country,countryCode";
        $ctx = stream_context_create(['http' => ['timeout' => 2]]); // 2s timeout
        $response = file_get_contents($url, false, $ctx);
        $data = json_decode($response, true);

        if ($data && $data['status'] === 'success') {
            $_SESSION['visitor_country'] = $data['country'];
        } else {
            $_SESSION['visitor_country'] = 'Cameroun'; // Default fallback
        }
    } catch (Exception $e) {
        $_SESSION['visitor_country'] = 'Cameroun';
    }

    return $_SESSION['visitor_country'];
}

/**
 * Returns phone prefix based on country
 */
function getPrefixByCountry($country) {
    $prefixes = [
        'Cameroun' => '+237',
        'Gabon' => '+241',
        'Sénégal' => '+221',
        'Côte d\'Ivoire' => '+225',
        'Bénin' => '+229',
        'Togo' => '+228',
        'France' => '+33',
        'United States' => '+1',
        'Canada' => '+1',
        'Belgium' => '+32',
        'Switzerland' => '+41'
    ];
    
    if (isset($prefixes[$country])) return $prefixes[$country];
    
    // Default to Cameroon prefix if unknown
    return '+237';
}
