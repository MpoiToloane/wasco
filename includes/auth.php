<?php
require_once __DIR__ . '/../config/database.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}

function login(string $username, string $password): array {
    $db = getDB1();
    $stmt = $db->prepare("SELECT u.*, c.full_name, c.district FROM users u
                          LEFT JOIN customers c ON u.account_number = c.account_number
                          WHERE u.username = ? AND u.is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['account_number']= $user['account_number'];
        $_SESSION['full_name']     = $user['full_name'] ?? $user['username'];
        $_SESSION['district']      = $user['district'] ?? '';
        auditLog($user['user_id'], 'LOGIN', 'users', $user['user_id'], 'Successful login');
        return ['success' => true, 'role' => $user['role']];
    }
    return ['success' => false, 'message' => 'Invalid username or password'];
}

function logout(): void {
    if (isset($_SESSION['user_id'])) {
        auditLog($_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
    }
    session_unset();
    session_destroy();
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

function calculateBill(float $units, string $customerType): float {
    $db = getDB1();
    $stmt = $db->prepare("
        SELECT cost_per_unit, usage_max FROM billing_rates
        WHERE customer_type = ?
          AND usage_min <= ?
          AND (usage_max >= ? OR usage_max IS NULL)
        ORDER BY effective_date DESC LIMIT 1
    ");
    $stmt->execute([$customerType, $units, $units]);
    $rate = $stmt->fetch();
    if (!$rate) return $units * 5.00; // fallback
    return round($units * $rate['cost_per_unit'], 2);
}
?>
