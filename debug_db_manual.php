<?php
$host = '127.0.0.1';
$dbname = 'hireme_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully.\n";
    
    $stmt = $pdo->query("SELECT id, title, image_path FROM cv_designs");
    $designs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($designs as $design) {
        echo "ID: " . $design['id'] . ", Title: " . $design['title'] . ", Path: " . $design['image_path'] . "\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
