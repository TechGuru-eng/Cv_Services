<?php
require_once 'config/db.php';

// Simulate a suspicious log
Security::logSuspicious("Test log entry: User accessed forbidden file.");
Security::logSuspicious("SQL Injection Attempt (Simulated): UNION SELECT * FROM users");

// Simulate a ban for a fake IP
$fakeIP = "192.168.1.999";
Security::banIP($fakeIP, "Test Ban from Script", "Tester");

echo "Test logs created and fake IP banned.<br>";
echo "<a href='security.php'>Go to Security Panel</a>";
?>
