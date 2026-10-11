<?php
session_start();

// Simple password protection
$ADMIN_PASSWORD = 'premiumadmin'; // You can change this password

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Mot de passe incorrect.";
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
<meta charset="UTF-8">
        <title>Connexion Admin</title>
        <style>
            body { background: #070B14; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: rgba(15, 23, 42, 0.8); padding: 40px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); text-align: center; width: 100%; max-width: 350px; }
            input { width: 100%; padding: 12px; margin: 15px 0; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.5); color: #fff; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #D4AF37; color: #000; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
            .error { color: #ff6b6b; margin-bottom: 15px; font-size: 0.9em; }
        </style>
    
    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
    <body>
        <div class="login-box">
            <h2>Accès Sécurisé</h2>
            <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Mot de passe" required>
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

require '../api/db.php';

// Handle Actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete_sub' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } elseif ($_GET['action'] === 'delete_vis' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM visitors WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } elseif ($_GET['action'] === 'contact_vis' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("UPDATE visitors SET is_contacted = 1 WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } elseif ($_GET['action'] === 'delete_cv' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $path = __DIR__ . '/../uploads/cvs/' . $file;
        if (file_exists($path)) {
            unlink($path);
        }
    } elseif ($_GET['action'] === 'delete_coupon' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } elseif ($_GET['action'] === 'set_default_coupon' && isset($_GET['id'])) {
        $pdo->exec("UPDATE coupons SET is_default = 0");
        $stmt = $pdo->prepare("UPDATE coupons SET is_default = 1 WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    }
    header("Location: index.php");
    exit();
}

if (isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = (int)$_POST['discount_amount'];
    $max_uses = (int)$_POST['max_uses'];
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] . ' 23:59:59' : null;
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if ($is_default) {
        $pdo->exec("UPDATE coupons SET is_default = 0");
    }
    
    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_amount, max_uses, expires_at, is_default) VALUES (?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$code, $discount, $max_uses, $expires_at, $is_default]);
    } catch (Exception $e) {}
    
    header("Location: index.php");
    exit();
}

if (isset($_POST['save_settings'])) {
    $scarcity_end_date = $_POST['scarcity_end_date'] ?? '';
    $toast_enabled = isset($_POST['toast_enabled']) ? '1' : '0';
    $toast_frequency_seconds = $_POST['toast_frequency_seconds'] ?? '12';
    
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->execute([$scarcity_end_date, 'scarcity_end_date']);
    $stmt->execute([$toast_enabled, 'toast_enabled']);
    $stmt->execute([$toast_frequency_seconds, 'toast_frequency_seconds']);
    
    header("Location: index.php#sales_boost");
    exit();
}

$cv_error = "";
$cv_success = "";
$target_dir = __DIR__ . "/../uploads/cvs/";

if (isset($_POST['upload_cv'])) {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $existing = [];
    if(file_exists($target_dir)) {
        $existing = array_diff(scandir($target_dir), array('.', '..'));
    }
    
    if (count($existing) >= 6) {
        $cv_error = "Vous avez déjà 6 CV. Veuillez en supprimer avant d'en ajouter.";
    } elseif (!empty($_FILES["cv_image"]["name"])) {
        $target_file = $target_dir . time() . "_" . basename($_FILES["cv_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed = array("jpg", "jpeg", "png", "webp", "gif");
        if (in_array($imageFileType, $allowed)) {
            if (move_uploaded_file($_FILES["cv_image"]["tmp_name"], $target_file)) {
                $cv_success = "CV ajouté avec succès.";
            } else {
                $cv_error = "Erreur lors de l'upload.";
            }
        } else {
            $cv_error = "Format non supporté (JPG, PNG, WEBP uniquement).";
        }
    }
}

$cv_files = [];
if(file_exists($target_dir)) {
    $cv_files = array_values(array_diff(scandir($target_dir), array('.', '..')));
}

$ebook_success = "";
$ebook_error = "";
$assets_dir = __DIR__ . "/../assets/";

if (isset($_POST['upload_file'])) {
    if (!file_exists($assets_dir)) {
        mkdir($assets_dir, 0777, true);
    }
    
    $file_type = $_POST['file_type']; // ebook1, ebook2, ebook3, ebook4, cv
    
    if (!empty($_FILES["asset_file"]["name"])) {
        $allowed = ['pdf', 'zip'];
        $ext = strtolower(pathinfo($_FILES["asset_file"]["name"], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $dest_name = "";
            if ($file_type === 'ebook1') $dest_name = "Guide_Ultime.pdf";
            elseif ($file_type === 'ebook2') $dest_name = "Bonus_1.pdf";
            elseif ($file_type === 'ebook3') $dest_name = "Bonus_2.pdf";
            elseif ($file_type === 'ebook4') $dest_name = "Bonus_3.pdf";
            elseif ($file_type === 'cv') $dest_name = "Modele_CV_Pro.zip";
            
            if ($dest_name) {
                if (move_uploaded_file($_FILES["asset_file"]["tmp_name"], $assets_dir . $dest_name)) {
                    $ebook_success = "Fichier '$dest_name' uploadé avec succès.";
                } else {
                    $ebook_error = "Erreur lors de l'upload.";
                }
            }
        } else {
            $ebook_error = "Format non supporté (PDF, ZIP uniquement).";
        }
    }
}

// Get Stats
$stats = [
    'subscribers' => $pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn(),
    'hits' => $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn(),
    'retargeting' => $pdo->query("SELECT COUNT(*) FROM visitors WHERE is_converted = 0 AND is_contacted = 0 AND (email IS NOT NULL OR whatsapp IS NOT NULL)")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(price_paid) FROM purchases WHERE status IN ('approved', 'paid_unverified')")->fetchColumn()
];
if (!$stats['revenue']) $stats['revenue'] = 0;

$conversion_rate = $stats['hits'] > 0 ? round(($stats['subscribers'] / $stats['hits']) * 100, 1) : 0;

// Get Subscribers
$subscribers = $pdo->query("SELECT * FROM subscribers ORDER BY created_at DESC")->fetchAll();

// Get Retargeting
$retargeting = $pdo->query("SELECT * FROM visitors WHERE is_converted = 0 AND is_contacted = 0 AND (email IS NOT NULL OR whatsapp IS NOT NULL) ORDER BY last_seen DESC")->fetchAll();

// Get Coupons
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();

// Get Purchases
$purchases = $pdo->query("SELECT * FROM purchases ORDER BY id DESC")->fetchAll();

// Get Recent Activity (Union of recent subscribers and visitors)
$recent_subs = $pdo->query("SELECT full_name as name, created_at as time, 'sub' as type FROM subscribers ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_vis = $pdo->query("SELECT session_id as name, last_seen as time, 'vis' as type FROM visitors ORDER BY last_seen DESC LIMIT 5")->fetchAll();
$activities = array_merge($recent_subs, $recent_vis);
usort($activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$activities = array_slice($activities, 0, 5);

// Get Settings for Sales Features
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireMe - Premium Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-dark: #070B14;
            --bg-card: rgba(15, 23, 42, 0.8);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --gold-primary: #D4AF37;
            --gold-light: #FDE047;
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        
        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid var(--border-glass);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            box-sizing: border-box;
            backdrop-filter: blur(10px);
            z-index: 100;
            transition: transform 0.3s ease;
        }
        
        .sidebar-logo {
            font-size: 1.8rem;
            color: var(--gold-primary);
            font-weight: 800;
            margin-bottom: 40px;
            text-align: center;
            letter-spacing: 1px;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }
        
        .nav-item {
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold-primary);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 40px;
            box-sizing: border-box;
            max-width: calc(100% - 260px);
        }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
        .header h1 { margin: 0; color: #fff; font-size: 2.2rem; }
        .header-actions { display: flex; gap: 15px; }

        /* Mobile Nav Toggle */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--gold-primary);
            cursor: pointer;
        }

        .dashboard-grid { display: grid; grid-template-columns: 2.5fr 1fr; gap: 24px; margin-bottom: 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        
        @media(max-width: 1024px) { 
            .dashboard-grid { grid-template-columns: 1fr; } 
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media(max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; max-width: 100%; padding: 20px; }
            .mobile-toggle { display: block; }
            .stats-grid { grid-template-columns: 1fr; } 
            .settings-grid { grid-template-columns: 1fr !important; }
        }
        
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        
        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border-glass);
            border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex; align-items: center; gap: 20px;
        }

        .stat-icon {
            width: 60px; height: 60px; background: rgba(212, 175, 55, 0.1);
            color: var(--gold-primary); border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }

        .stat-info h3 { margin: 0; font-size: 2rem; color: var(--text-main); }
        .stat-info p { margin: 5px 0 0; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }

        .activity-feed {
            background: var(--bg-card); border: 1px solid var(--border-glass);
            border-radius: 16px; padding: 24px; height: 100%; box-sizing: border-box;
        }
        .activity-feed h2 { margin: 0 0 20px 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;}
        .activity-item { display: flex; gap: 12px; margin-bottom: 15px; font-size: 0.9rem; }
        .activity-item .dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
        .dot.sub { background: #4ADE80; box-shadow: 0 0 10px #4ADE80; }
        .dot.vis { background: var(--gold-primary); box-shadow: 0 0 10px var(--gold-primary); }
        .activity-time { color: var(--text-muted); font-size: 0.8rem; margin-top: 4px; display: block;}

        .section {
            background: var(--bg-card); border: 1px solid var(--border-glass);
            border-radius: 16px; padding: 30px; margin-bottom: 40px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .section-header h2 { margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        
        .search-bar {
            background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass);
            color: #fff; padding: 10px 16px; border-radius: 8px; width: 100%; max-width: 300px;
            font-family: 'Plus Jakarta Sans', sans-serif; outline: none;
        }
        .search-bar:focus { border-color: var(--gold-primary); }

        .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: bold; cursor: pointer; border: none; }
        .btn-export { background: rgba(212, 175, 55, 0.1); color: var(--gold-primary); border: 1px solid var(--gold-primary); padding: 10px 20px; transition: all 0.3s; }
        .btn-export:hover { background: var(--gold-primary); color: #000; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .btn-whatsapp:hover { background: #128C7E; }
        .btn-delete { background: rgba(255, 107, 107, 0.1); color: #ff6b6b; border: 1px solid rgba(255, 107, 107, 0.3); }
        .btn-delete:hover { background: #ff6b6b; color: #fff; }
        .btn-check { background: rgba(74, 222, 128, 0.1); color: #4ADE80; border: 1px solid rgba(74, 222, 128, 0.3); }
        .btn-check:hover { background: #4ADE80; color: #000; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-glass); }
        th { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255,255,255,0.02); }

        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
        
        /* Tabs */
        .tab-content { display: none; animation: fadeIn 0.4s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <span style="color: #fff;">Hire</span>Me.
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="#dashboard" class="nav-link active"><i data-lucide="layout-dashboard"></i> Vue d'ensemble</a></li>
            <li class="nav-item"><a href="#commandes" class="nav-link"><i data-lucide="shopping-cart"></i> Commandes</a></li>
            <li class="nav-item"><a href="#fichiers" class="nav-link"><i data-lucide="file-archive"></i> Fichiers</a></li>
            <li class="nav-item"><a href="#marketing" class="nav-link"><i data-lucide="target"></i> Marketing (Relance)</a></li>
            <li class="nav-item"><a href="#sales_boost" class="nav-link"><i data-lucide="rocket"></i> Boost Ventes</a></li>
        </ul>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-glass);">
            <a href="?logout=1" class="nav-link" style="color: #ff6b6b; padding: 12px 0;"><i data-lucide="log-out"></i> Déconnexion</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <div style="display:flex; align-items:center; gap: 15px;">
                <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <i data-lucide="menu" style="width:28px; height:28px;"></i>
                </button>
                <h1>Tableau de Bord</h1>
            </div>

        </div>

    <div id="tab-dashboard" class="tab-content active">
    <div class="dashboard-grid">
        <div class="stats-grid">
            <div class="stat-card" style="border-color: var(--gold-primary); background: linear-gradient(145deg, rgba(212,175,55,0.1), rgba(15,23,42,0.9));">
                <div class="stat-icon"><i data-lucide="banknote" style="width: 30px; height: 30px;"></i></div>
                <div class="stat-info">
                    <h3 style="color: var(--gold-light);"><?= number_format($stats['revenue'], 0, ',', ' ') ?> <span style="font-size:1rem;">XAF</span></h3>
                    <p style="color: var(--gold-primary);">Chiffre d'Affaires</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="book-open" style="width: 30px; height: 30px;"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['subscribers'] ?></h3>
                    <p>Précommandes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #4ADE80; background: rgba(74, 222, 128, 0.1);"><i data-lucide="pie-chart" style="width: 30px; height: 30px;"></i></div>
                <div class="stat-info">
                    <h3><?= $conversion_rate ?>%</h3>
                    <p>Taux de Conversion</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="target" style="width: 30px; height: 30px;"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['retargeting'] ?></h3>
                    <p>File de Relance</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="users" style="width: 30px; height: 30px;"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['hits'] ?></h3>
                    <p>Visiteurs Uniques</p>
                </div>
            </div>
        </div>
        
        <div class="activity-feed">
            <h2><i data-lucide="activity"></i> Activité Récente</h2>
            <?php if(count($activities) > 0): ?>
                <?php foreach($activities as $act): ?>
                    <div class="activity-item">
                        <div class="dot <?= $act['type'] ?>"></div>
                        <div>
                            <?php if($act['type'] === 'sub'): ?>
                                <strong><?= htmlspecialchars($act['name']) ?></strong> a précommandé.
                            <?php else: ?>
                                Un visiteur a commencé à remplir le formulaire.
                            <?php endif; ?>
                            <span class="activity-time"><?= date('d/m/Y à H:i', strtotime($act['time'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Aucune activité récente.</p>
            <?php endif; ?>
        </div>
    </div> <!-- END dashboard-grid -->

    <!-- SUBSCRIBERS TABLE (Belongs to Dashboard) -->
    <div class="section" id="subscribers-section-dashboard">
        <div class="section-header">
            <h2><i data-lucide="check-circle" style="color: #4ADE80;"></i> Précommandes Validées</h2>
            <div style="display: flex; gap: 16px;">
                <input type="text" id="search-subs" class="search-bar" placeholder="Rechercher par nom, email, whatsapp..." onkeyup="filterTable('search-subs', 'table-subs')">
                <a href="export.php?type=subscribers" class="btn-export btn-action"><i data-lucide="download"></i> Exporter</a>
            </div>
        </div>
        
        <?php if(count($subscribers) > 0): ?>
        <div class="table-responsive">
        <table id="table-subs">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Ville</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($subscribers as $sub): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                    <td><strong style="color: var(--text-main);"><?= htmlspecialchars($sub['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars($sub['email']) ?></td>
                    <td><?= htmlspecialchars($sub['whatsapp']) ?></td>
                    <td><?= htmlspecialchars($sub['city']) ?></td>
                    <td>
                        <a href="?action=delete_sub&id=<?= $sub['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer définitivement cette précommande ?')"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="empty-state">Aucune précommande pour le moment.</div>
        <?php endif; ?>
    </div>
    </div> <!-- END TAB DASHBOARD -->

    <!-- CV UPLOAD SECTION -->
    <div id="tab-fichiers" class="tab-content">
    <div class="section">
        <div class="section-header">
            <h2><i data-lucide="image" style="color: var(--gold-primary);"></i> Gestion des CV Flottants (Max 6)</h2>
        </div>
        
        <?php if($cv_error): ?><div style="color: #ff6b6b; margin-bottom: 15px;"><?= $cv_error ?></div><?php endif; ?>
        <?php if($cv_success): ?><div style="color: #4ADE80; margin-bottom: 15px;"><?= $cv_success ?></div><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <input type="file" name="cv_image" accept="image/png, image/jpeg, image/webp" required style="max-width: 300px;">
            <button type="submit" name="upload_cv" class="btn-export btn-action" style="cursor: pointer; border: none; background: var(--gold-primary); color: #000;"><i data-lucide="upload"></i> Ajouter CV</button>
        </form>

        <?php if(count($cv_files) > 0): ?>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <?php foreach($cv_files as $cv): ?>
                    <div style="position: relative; width: 120px; height: 160px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-glass);">
                        <img src="../uploads/cvs/<?= htmlspecialchars($cv) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <a href="?action=delete_cv&file=<?= urlencode($cv) ?>" onclick="return confirm('Supprimer ce CV ?')" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: #ff6b6b; padding: 5px; border-radius: 5px;"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">Aucun CV uploadé.</div>
        <?php endif; ?>
    </div>

    <!-- ASSETS UPLOAD SECTION -->
    <div class="section">
        <div class="section-header">
            <h2><i data-lucide="file-archive" style="color: var(--gold-primary);"></i> Gestion des Fichiers à Livrer</h2>
        </div>
        
        <?php if($ebook_error): ?><div style="color: #ff6b6b; margin-bottom: 15px;"><?= $ebook_error ?></div><?php endif; ?>
        <?php if($ebook_success): ?><div style="color: #4ADE80; margin-bottom: 15px;"><?= $ebook_success ?></div><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 0.85rem; margin-bottom: 5px;">Type de fichier</label>
                <select name="file_type" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 6px;" required>
                    <option value="ebook1">1. Le Guide Ultime (PDF)</option>
                    <option value="ebook2">2. Bonus 1 (PDF)</option>
                    <option value="ebook3">3. Bonus 2 (PDF)</option>
                    <option value="ebook4">4. Bonus 3 (PDF)</option>
                    <option value="cv">Modèle CV Pro (ZIP)</option>
                </select>
            </div>
            <div style="flex: 2; min-width: 250px;">
                <label style="display:block; font-size: 0.85rem; margin-bottom: 5px;">Fichier (.pdf ou .zip)</label>
                <input type="file" name="asset_file" accept=".pdf,.zip" required style="width: 100%; padding: 8px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 6px;">
            </div>
            <button type="submit" name="upload_file" class="btn-export btn-action" style="padding: 10px 24px; cursor: pointer; border: none; background: var(--gold-primary); color: #000;"><i data-lucide="upload"></i> Mettre à jour</button>
        </form>
        
        <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; font-size: 0.9em; color: var(--text-muted);">
            <strong>Fichiers actuels :</strong><br>
            - Guide : <?= file_exists(__DIR__ . '/../assets/Guide_Ultime.pdf') ? '<span style="color:#4ADE80">Présent</span>' : '<span style="color:#ff6b6b">Manquant</span>' ?><br>
            - Bonus 1 : <?= file_exists(__DIR__ . '/../assets/Bonus_1.pdf') ? '<span style="color:#4ADE80">Présent</span>' : '<span style="color:#ff6b6b">Manquant</span>' ?><br>
            - Bonus 2 : <?= file_exists(__DIR__ . '/../assets/Bonus_2.pdf') ? '<span style="color:#4ADE80">Présent</span>' : '<span style="color:#ff6b6b">Manquant</span>' ?><br>
            - Bonus 3 : <?= file_exists(__DIR__ . '/../assets/Bonus_3.pdf') ? '<span style="color:#4ADE80">Présent</span>' : '<span style="color:#ff6b6b">Manquant</span>' ?><br>
            - CV Pro : <?= file_exists(__DIR__ . '/../assets/Modele_CV_Pro.zip') ? '<span style="color:#4ADE80">Présent</span>' : '<span style="color:#ff6b6b">Manquant</span>' ?>
        </div>
    </div>

    </div>
    <!-- END TAB FICHIERS -->

    <!-- PURCHASES / COMMANDES TABLE -->
    <div id="tab-commandes" class="tab-content">
    <div class="section">
        <div class="section-header">
            <h2><i data-lucide="shopping-cart" style="color: var(--gold-primary);"></i> Commandes & Paiements</h2>
            <div style="display: flex; gap: 16px;">
                <input type="text" id="search-purchases" class="search-bar" placeholder="Rechercher..." onkeyup="filterTable('search-purchases', 'table-purchases')">
            </div>
        </div>
        
        <?php if(isset($_GET['success']) && $_GET['success'] === 'delivered'): ?>
            <div style="background: rgba(74, 222, 128, 0.1); color: #4ADE80; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(74, 222, 128, 0.3);">
                ✅ L'email avec le reçu et les liens de téléchargement a été envoyé au client avec succès !
            </div>
        <?php elseif(isset($_GET['success']) && $_GET['success'] === 'n8n_delivered'): ?>
            <div style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(56, 189, 248, 0.3);">
                🚀 Les données ont été envoyées à n8n avec succès. La livraison (Email/WhatsApp) est en cours !
            </div>
        <?php endif; ?>
        
        <?php if(count($purchases) > 0): ?>
        <div class="table-responsive">
        <table id="table-purchases">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Contacts</th>
                    <th>Montant (XAF)</th>
                    <th>Détails</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($purchases as $p): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                    <td>
                        <strong style="color: var(--text-main);"><?= htmlspecialchars($p['full_name']) ?></strong><br>
                        <small style="color: var(--text-muted);"><?= htmlspecialchars($p['city']) ?></small>
                    </td>
                    <td>
                        <div style="font-size: 0.9em;"><?= htmlspecialchars($p['email']) ?></div>
                        <div style="font-size: 0.9em; color: var(--text-muted);"><?= htmlspecialchars($p['whatsapp']) ?></div>
                    </td>
                    <td>
                        <strong style="color: var(--gold-primary);"><?= number_format($p['price_paid'], 0, ',', ' ') ?></strong>
                    </td>
                    <td style="font-size: 0.9em; color: var(--text-muted);">
                        <?php if($p['coupon_used']): ?>Promo: <?= htmlspecialchars($p['coupon_used']) ?><br><?php endif; ?>
                        <?php if($p['upsell_cv']): ?><span style="color: #4ADE80;">+ CV Pro</span><br><?php endif; ?>
                        <?php if(!empty($p['proof_file_path'])): ?>
                            <a href="../<?= htmlspecialchars($p['proof_file_path']) ?>" target="_blank" style="color: #38bdf8; text-decoration: underline;"><i data-lucide="image" style="width:14px; height:14px; vertical-align:middle;"></i> Voir la preuve</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($p['delivery_status'] === 'delivered_email' || $p['delivery_status'] === 'delivered_whatsapp'): ?>
                            <span style="background: rgba(74, 222, 128, 0.1); color: #4ADE80; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">LIVRÉ</span>
                        <?php elseif($p['status'] === 'approved'): ?>
                            <span style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">APPROUVÉ (Non Livré)</span>
                        <?php else: ?>
                            <span style="background: rgba(255, 171, 0, 0.1); color: #FFAB00; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">EN ATTENTE</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; flex-direction: column;">
                            <?php if($p['status'] !== 'approved' && $p['delivery_status'] === 'pending'): ?>
                                <a href="../api/approve_only.php?id=<?= $p['id'] ?>" class="btn-action" style="background: var(--gold-primary); color: #000; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.85rem; text-align: center;" onclick="return confirm('Approuver ce paiement (sans livrer) ?')">Approuver Paiement</a>
                                <?php if($p['whatsapp']): ?>
                                    <?php 
                                        $wa_number_p = preg_replace('/[^0-9]/', '', $p['whatsapp']); 
                                        $promo_text_p = "Bonjour " . $p['full_name'] . ", nous sommes en attente de la validation de votre paiement pour l'Ebook Premium. Avez-vous rencontré un problème ?";
                                        $wa_link_p = "https://wa.me/" . $wa_number_p . "?text=" . rawurlencode($promo_text_p);
                                    ?>
                                    <a href="<?= $wa_link_p ?>" target="_blank" class="btn-action btn-whatsapp" style="padding: 6px 12px; text-align: center; border-radius: 4px; font-size: 0.85rem; text-decoration: none; background: #25D366; color: #fff;">
                                        <i data-lucide="message-circle" style="width:14px; height:14px; vertical-align:middle;"></i> Relance WhatsApp
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if($p['status'] === 'approved' && $p['delivery_status'] !== 'delivered_n8n'): ?>
                                <a href="../api/trigger_delivery.php?id=<?= $p['id'] ?>" class="btn-action" style="background: #a855f7; color: #fff; text-decoration: none; padding: 10px 12px; border-radius: 6px; font-weight: bold; font-size: 0.85rem; text-align: center; display: flex; justify-content: center; align-items: center; gap: 8px;" onclick="return confirm('Déclencher la livraison via n8n (Email & WhatsApp) ?')">
                                    <i data-lucide="rocket" style="width:16px; height:16px;"></i> Lancer la Livraison (n8n)
                                </a>
                            <?php endif; ?>

                            
                            <?php if($p['delivery_status'] !== 'pending'): ?>
                                <span style="color: var(--text-muted); font-size: 0.85rem; text-align: center;"><i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> <?= $p['delivery_status'] ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="empty-state">Aucune commande pour le moment.</div>
        <?php endif; ?>
    </div>

    </div>
    <!-- END TAB COMMANDES -->


    
    <!-- COUPONS MANAGEMENT -->
    <div id="tab-marketing" class="tab-content">
    <div class="section">
        <div class="section-header">
            <h2><i data-lucide="tag" style="color: var(--gold-primary);"></i> Gestion des Codes Promo</h2>
        </div>
        
        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid var(--border-glass);">
            <h3 style="margin-top: 0; font-size: 1.1rem;">Nouveau Code Promo</h3>
            <form method="POST" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 1; min-width: 150px;">
                    <label style="display:block; font-size: 0.85rem; margin-bottom: 5px;">Code</label>
                    <input type="text" name="code" placeholder="EX: PROMO50" required style="width: 100%; padding: 10px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 6px;">
                </div>
                <div style="flex: 1; min-width: 120px;">
                    <label style="display:block; font-size: 0.85rem; margin-bottom: 5px;">Réduction (XAF)</label>
                    <input type="number" name="discount_amount" placeholder="5000" required style="width: 100%; padding: 10px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 6px;">
                </div>
                <div style="flex: 1; min-width: 120px;">
                    <label style="display:block; font-size: 0.85rem; margin-bottom: 5px;">Max Utilisations</label>
                    <input type="number" name="max_uses" placeholder="100" required style="width: 100%; padding: 10px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 6px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display:block; font-size: 0.85rem; margin-bottom: 5px;">Date d'Expiration</label>
                    <input type="date" name="expires_at" style="width: 100%; padding: 10px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 6px; color-scheme: dark;">
                </div>
                <div style="display: flex; align-items: center; gap: 8px; padding-bottom: 12px;">
                    <input type="checkbox" name="is_default" id="is_default" value="1">
                    <label for="is_default" style="font-size: 0.9rem; cursor: pointer;">Définir par défaut</label>
                </div>
                <button type="submit" name="add_coupon" class="btn-export btn-action" style="padding: 10px 24px;">Créer</button>
            </form>
        </div>

        <?php if(count($coupons) > 0): ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Réduction</th>
                    <th>Utilisations</th>
                    <th>Expiration</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($coupons as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['code']) ?></strong></td>
                    <td><?= number_format($c['discount_amount'], 0, ',', ' ') ?> XAF</td>
                    <td><?= $c['current_uses'] ?> / <?= $c['max_uses'] ?></td>
                    <td><?= $c['expires_at'] ? date('d/m/Y H:i', strtotime($c['expires_at'])) : 'Jamais' ?></td>
                    <td>
                        <?php if($c['is_default']): ?>
                            <span style="background: rgba(212, 175, 55, 0.2); color: var(--gold-primary); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;">Par Défaut</span>
                        <?php endif; ?>
                    </td>
                    <td style="display: flex; gap: 8px;">
                        <?php if(!$c['is_default']): ?>
                            <a href="?action=set_default_coupon&id=<?= $c['id'] ?>" class="btn-action btn-check" title="Définir par défaut">Défaut</a>
                        <?php endif; ?>
                        <a href="?action=delete_coupon&id=<?= $c['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer ce code promo ?')" title="Supprimer"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="empty-state">Aucun code promo créé.</div>
        <?php endif; ?>
    </div>

    <!-- RETARGETING TABLE -->
    <div class="section">
        <div class="section-header">
            <h2><i data-lucide="refresh-cw" style="color: var(--gold-primary);"></i> File de Retargeting (Abandons)</h2>
            <div style="display: flex; gap: 16px;">
                <input type="text" id="search-ret" class="search-bar" placeholder="Rechercher email ou whatsapp..." onkeyup="filterTable('search-ret', 'table-ret')">
                <a href="export.php?type=retargeting" class="btn-export btn-action"><i data-lucide="download"></i> Exporter</a>
            </div>
        </div>
        
        <?php if(count($retargeting) > 0): ?>
        <div class="table-responsive">
        <table id="table-ret">
            <thead>
                <tr>
                    <th>Dernière visite</th>
                    <th>Email capturé</th>
                    <th>WhatsApp capturé</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($retargeting as $ret): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($ret['last_seen'])) ?></td>
                    <td><?= htmlspecialchars($ret['email'] ?? 'Non renseigné') ?></td>
                    <td><?= htmlspecialchars($ret['whatsapp'] ?? 'Non renseigné') ?></td>
                    <td style="display: flex; gap: 8px;">
                        <?php if($ret['whatsapp']): ?>
                            <?php 
                                $wa_number = preg_replace('/[^0-9]/', '', $ret['whatsapp']); 
                                $promo_text = "Bonjour, j'ai vu que vous étiez intéressé(e) par Le Guide Ultime des Cabinets RH. Avez-vous rencontré un problème lors du paiement ? Voici un code promo de 10% pour finaliser votre commande : RELANCE10";
                                $wa_link = "https://wa.me/" . $wa_number . "?text=" . rawurlencode($promo_text);
                            ?>
                            <a href="<?= $wa_link ?>" target="_blank" class="btn-action btn-whatsapp">
                                <i data-lucide="message-circle" style="width: 16px; height: 16px;"></i> Relance Rapide
                            </a>
                        <?php endif; ?>
                        <a href="?action=contact_vis&id=<?= $ret['id'] ?>" class="btn-action btn-check" title="Marquer comme contacté"><i data-lucide="check" style="width: 16px; height: 16px;"></i></a>
                        <a href="?action=delete_vis&id=<?= $ret['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer ce prospect ?')" title="Supprimer"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="empty-state">Aucun abandon de formulaire à relancer.</div>
        <?php endif; ?>
    </div>
    </div>
    </div> <!-- END TAB MARKETING -->

    <!-- SALES BOOST (SCARCITY & MAGIC LINKS) -->
    <div id="tab-sales_boost" class="tab-content">
        <div class="section">
            <div class="section-header">
                <h2><i data-lucide="rocket" style="color: var(--gold-primary);"></i> Boost des Ventes (Fonctionnalités Agressives)</h2>
            </div>
            
            <form method="POST" class="settings-grid">
                <!-- Scarcity Manager -->
                <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid var(--border-glass);">
                    <h3 style="margin-top: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="timer" style="color: #ff6b6b;"></i> Gestionnaire d'Urgence
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                        Définissez la date de fin de la promotion. Le compte à rebours sur la page de vente s'adaptera automatiquement.
                    </p>
                    <label style="display:block; font-size: 0.9rem; margin-bottom: 8px; font-weight: bold;">Date de fin du compte à rebours</label>
                    <input type="datetime-local" name="scarcity_end_date" value="<?= htmlspecialchars($settings['scarcity_end_date'] ?? '') ?>" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 8px; color-scheme: dark; margin-bottom: 16px;">
                </div>

                <!-- Toast Controller -->
                <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid var(--border-glass);">
                    <h3 style="margin-top: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="bell-ring" style="color: #4ADE80;"></i> Notifications de Vente (Toasts)
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                        Contrôlez les petites fenêtres "Marc a acheté..." en bas à gauche de l'écran pour la preuve sociale.
                    </p>
                    
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; background: rgba(255,255,255,0.05); padding: 12px; border-radius: 8px;">
                        <input type="checkbox" name="toast_enabled" id="toast_enabled" value="1" <?= (!isset($settings['toast_enabled']) || $settings['toast_enabled'] == '1') ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                        <label for="toast_enabled" style="font-size: 0.95rem; cursor: pointer; font-weight: bold;">Activer les notifications de vente</label>
                    </div>
                    
                    <label style="display:block; font-size: 0.9rem; margin-bottom: 8px; font-weight: bold;">Fréquence d'apparition (en secondes)</label>
                    <input type="number" name="toast_frequency_seconds" value="<?= htmlspecialchars($settings['toast_frequency_seconds'] ?? '12') ?>" required min="5" max="60" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.5); border: 1px solid var(--border-glass); color: #fff; border-radius: 8px;">
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="save_settings" class="btn-export btn-action" style="padding: 12px 30px; font-size: 1rem; width: 100%; justify-content: center;">
                        <i data-lucide="save"></i> Sauvegarder les Réglages
                    </button>
                </div>
            </form>
            
            <!-- Magic Link Generator -->
            <div style="background: linear-gradient(145deg, rgba(212,175,55,0.1), rgba(0,0,0,0.2)); padding: 24px; border-radius: 12px; border: 1px solid var(--gold-primary); margin-top: 40px;">
                <h3 style="margin-top: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; color: var(--gold-light);">
                    <i data-lucide="wand-2"></i> Générateur de Lien Magique
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;">
                    Envoyez un lien de paiement direct à un prospect. Le code promo par défaut (s'il y en a un actif) sera automatiquement appliqué.
                </p>
                <?php 
                    // Find default coupon
                    $default_c = null;
                    foreach($coupons as $c) {
                        if($c['is_default']) { $default_c = $c; break; }
                    }
                    
                    // Build magic link
                    $base_url = "http://" . $_SERVER['HTTP_HOST'] . "/MonCv/premium-ebook-page/paiement.html";
                    if ($default_c) {
                        $magic_link = $base_url . "?coupon=" . urlencode($default_c['code']);
                    } else {
                        $magic_link = $base_url;
                    }
                ?>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <input type="text" readonly value="<?= $magic_link ?>" id="magicLinkInput" style="flex: 1; padding: 12px; background: rgba(0,0,0,0.8); border: 1px solid var(--border-glass); color: var(--gold-light); border-radius: 8px; font-family: monospace;">
                    <button onclick="copyMagicLink()" class="btn-export btn-action" style="padding: 12px 20px;">
                        <i data-lucide="copy"></i> Copier le lien
                    </button>
                </div>
                <?php if($default_c): ?>
                    <p style="font-size: 0.8rem; color: #4ADE80; margin-top: 10px; margin-bottom: 0;">
                        <i data-lucide="check-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i> Code promo <strong><?= $default_c['code'] ?></strong> (-<?= $default_c['discount_amount'] ?> XAF) inclus.
                    </p>
                <?php else: ?>
                    <p style="font-size: 0.8rem; color: #ff6b6b; margin-top: 10px; margin-bottom: 0;">
                        <i data-lucide="alert-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i> Aucun code promo par défaut défini. Allez dans "Gestion des Codes Promo".
                    </p>
                <?php endif; ?>
                <script>
                    function copyMagicLink() {
                        const copyText = document.getElementById("magicLinkInput");
                        copyText.select();
                        copyText.setSelectionRange(0, 99999);
                        navigator.clipboard.writeText(copyText.value);
                        alert("Lien magique copié : " + copyText.value);
                    }
                </script>
            </div>
        </div>
    </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Simple client-side search filter
        function filterTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            const filter = input.value.toLowerCase();
            const table = document.getElementById(tableId);
            if(!table) return;
            const trs = table.getElementsByTagName("tr");

            for (let i = 1; i < trs.length; i++) {
                let show = false;
                const tds = trs[i].getElementsByTagName("td");
                for (let j = 0; j < tds.length - 1; j++) { // exclude action column
                    if (tds[j]) {
                        const txtValue = tds[j].textContent || tds[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            show = true;
                            break;
                        }
                    }       
                }
                trs[i].style.display = show ? "" : "none";
            }
        }
        
        // Tab switching logic
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId.startsWith('#')) {
                    e.preventDefault();
                    
                    // Remove active from all links and tabs
                    document.querySelectorAll('.sidebar .nav-link').forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                    
                    // Add active to clicked link and target tab
                    this.classList.add('active');
                    const targetTab = document.querySelector(targetId.replace('#', '#tab-'));
                    if (targetTab) targetTab.classList.add('active');
                    
                    // On mobile, close sidebar after clicking
                    if (window.innerWidth <= 768) {
                        document.getElementById('sidebar').classList.remove('open');
                    }
                    
                    // Update URL hash without jumping
                    history.pushState(null, null, targetId);
                }
            });
        });

        // Open tab from URL hash on load
        if (window.location.hash) {
            const hashLink = document.querySelector(`.sidebar .nav-link[href="${window.location.hash}"]`);
            if (hashLink) hashLink.click();
        }
    </script>
</body>
</html>
