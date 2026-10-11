<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — HireMe</title>

    <!-- Open Graph / Default Preview -->
    <?php 
        $currentAdminFile = basename($_SERVER['PHP_SELF']);
        $previewImg = "https://moncvpro.hiremeguide.com/assets/hireme-preview-v2.png";
        if ($currentAdminFile === 'club_platform.php') {
            $previewImg = "https://moncvpro.hiremeguide.com/assets/club-preview-premium.png";
        }
    ?>
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= $previewImg ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= $previewImg ?>">
    
    <!-- Favicons -->
    <!-- CSS / Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Space Grotesk', sans-serif; }
    </style>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
