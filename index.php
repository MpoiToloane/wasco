<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header('Location: ' . BASE_URL . '/pages/admin/dashboard.php');
    elseif ($role === 'branch_manager') header('Location: ' . BASE_URL . '/pages/manager/dashboard.php');
    else header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $result = login($username, $password);
        if ($result['success']) {
            if ($result['role'] === 'admin') header('Location: ' . BASE_URL . '/pages/admin/dashboard.php');
            elseif ($result['role'] === 'branch_manager') header('Location: ' . BASE_URL . '/pages/manager/dashboard.php');
            else header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit;
        }
        $error = $result['message'];
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WASCO - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
<div class="login-box">
    <div class="login-logo">
        <span class="icon">💧</span>
        <h1>WASCO Portal</h1>
        <p>Water &amp; Sewerage Company · Lesotho</p>
    </div>

    <?php if (isset($_GET['timeout'])): ?>
        <div class="alert alert-info">Session expired. Please log in again.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:.75rem;">Login</button>
    </form>

    <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid #eee;font-size:.8rem;color:#999;">
        <strong>Demo Credentials:</strong><br>
        Admin: admin / password &nbsp;|&nbsp; Manager: manager / password<br>
        Customer: thabiso / password
    </div>
</div>
</body>
</html>
