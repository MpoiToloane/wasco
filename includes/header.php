<?php
$role = $_SESSION['role'] ?? '';
$name = $_SESSION['full_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <span class="brand-icon">💧</span>
        <span class="brand-name">WASCO</span>
        <span class="brand-sub">Billing Portal</span>
    </div>
    <div class="nav-links">
        <?php if ($role === 'customer'): ?>
            <a href="<?= BASE_URL ?>/pages/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>/pages/my_bills.php">My Bills</a>
            <a href="<?= BASE_URL ?>/pages/pay_bill.php">Pay Bill</a>
            <a href="<?= BASE_URL ?>/pages/usage.php">Usage</a>
            <a href="<?= BASE_URL ?>/pages/leakage.php">Report Leak</a>
        <?php elseif ($role === 'admin'): ?>
            <a href="<?= BASE_URL ?>/pages/admin/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>/pages/admin/customers.php">Customers</a>
            <a href="<?= BASE_URL ?>/pages/admin/bills.php">Bills</a>
            <a href="<?= BASE_URL ?>/pages/admin/payments.php">Payments</a>
            <a href="<?= BASE_URL ?>/pages/admin/rates.php">Rates</a>
        <?php elseif ($role === 'branch_manager'): ?>
            <a href="<?= BASE_URL ?>/pages/manager/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>/pages/manager/reports.php">Reports</a>
            <a href="<?= BASE_URL ?>/pages/manager/districts.php">Districts</a>
        <?php endif; ?>
    </div>
    <div class="nav-user">
        <span class="user-name">👤 <?= htmlspecialchars($name) ?></span>
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<main class="main-content">
