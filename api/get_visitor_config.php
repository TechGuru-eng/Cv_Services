<?php
// api/get_visitor_config.php
require_once '../admin/config/db.php';
require_once 'includes/geo_helper.php';
session_start();

// 1. Detect Country
$country = initVisitorSession();
$prefix = getPrefixByCountry($country);
$is_foreign = false; // ($country !== 'Cameroun'); // Disabled to allow Promo Codes for everyone (Phase: Universal XAF)

// 2. Return Configuration
$config = [
    'country' => $country,
    'currency' => 'XAF',
    'symbol' => 'FCFA',
    'rate' => 1,
    'prefix' => $prefix,
    'is_foreign' => $is_foreign,
    'intl_data' => [
        'intl_pack_etudiant_chariow' => 'https://boutique.hiremeguide.com/packetudiant/checkout',
        'intl_pack_pro_chariow' => 'https://boutique.hiremeguide.com/packpro/checkout',
        'intl_pack_senior_chariow' => 'https://boutique.hiremeguide.com/packsenior/checkout',
        'intl_pack_expert_chariow' => 'https://boutique.hiremeguide.com/packexpert/checkout',
        'intl_pack_templates40_chariow' => 'https://boutique.hiremeguide.com/prd_nun0gx'
    ]
];

header('Content-Type: application/json');
echo json_encode($config);
