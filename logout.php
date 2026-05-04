<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
logout();
header('Location: ' . BASE_URL . '/index.php');
exit;
?>
