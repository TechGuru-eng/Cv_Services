<?php
/**
 * Detects the active promotion dynamically from the homepage and its linked promo page.
 * Acts like a smart AI feature to always serve the correct current promo (e.g., Ramadan, 8 Mars) 
 * without hardcoding in the prospect relance messages.
 */
function get_active_season_promo() {
    $indexPath = __DIR__ . '/../../index.html';
    
    if (!file_exists($indexPath)) {
        return null;
    }
    
    $html = file_get_contents($indexPath);
    
    // Look for the promotional banner and extract occasion name and link
    if (preg_match('/id="topPromoBanner"[\s\S]*?OFFRE SPÉCIALE\s*([^<:\n\r]+)[\s\S]*?href="([^"]+\.php)"/i', $html, $matches)) {
        $promoName = ucfirst(strtolower(trim($matches[1])));
        $promoLink = trim($matches[2]);
        
        $promoPath = __DIR__ . '/../../' . $promoLink;
        $pricesArray = [];
        
        if (file_exists($promoPath)) {
            $promoContent = file_get_contents($promoPath);
            // Extract the $prices array from the promo PHP file
            if (preg_match('/\$prices\s*=\s*(?:array\(|\[)(.*?)(?:\)|\])\s*;/s', $promoContent, $priceMatches)) {
                $priceString = $priceMatches[1];
                if (preg_match_all("/(?:'|\")([^'\"]+)(?:'|\")\s*=>\s*(\d+)/", $priceString, $packMatches, PREG_SET_ORDER)) {
                    foreach ($packMatches as $pm) {
                        $pricesArray[strtolower(trim($pm[1]))] = (int)$pm[2];
                    }
                }
            }
        }
        
        return [
            'name' => $promoName,
            'link' => 'https://moncvpro.hiremeguide.com/' . $promoLink,
            'prices' => $pricesArray
        ];
    }
    
    return null;
}
?>
