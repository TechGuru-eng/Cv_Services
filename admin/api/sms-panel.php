<?php
session_start();
if (!isset($_SESSION['admin'])) { http_response_code(403); exit; }
header('Location: ../sms-panel');
exit;
