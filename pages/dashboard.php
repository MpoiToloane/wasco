<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();
requireRole('customer');

$db = getDB1();
$acc = $_SESSION['account_number'];

// Customer info
$stmt = $db->prepare("SELECT * FROM customers WHERE account_number = ?");
$stmt->execute([$acc]);
$customer = $stmt->fetch();

// Stats
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(amount_due) as total_due FROM bills WHERE account_number = ?");
$stmt->execute([$acc]);
$billStats = $stmt->fetch();

$stmt = $db->prepare("SELECT COALESCE(SUM(amount_paid),0) as total_paid FROM payments WHERE account_number = ?");
$stmt->execute([$acc]);
$payStats = $stmt->fetch();

$outstanding = $billStats['total_due'] - $payStats['total_paid'];

// Latest bill
$stmt = $db->prepare("SELECT * FROM bills WHERE account_number = ? ORDER BY billing_month DESC LIMIT 1");
$stmt->execute([$acc]);
$latestBill = $stmt->fetch();

// Recent usage
$stmt = $db->prepare("SELECT * FROM water_usage WHERE account_number = ? ORDER BY reading_month DESC LIMIT 6");
$stmt->execute([$acc]);
$usageHistory = $stmt->fetchAll();

// Recent payments
$stmt = $db->prepare("SELECT p.*, b.billing_month FROM payments p JOIN bills b ON p.bill_id = b.bill_id
                      WHERE p.account_number = ? ORDER BY p.payment_date DESC LIMIT 5");
$stmt->execute([$acc]);
$recentPayments = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Welcome, <?= htmlspecialchars($customer['full_name']) ?> 👋</h1>
    <p>Account: <?= $acc ?> · <?= $customer['district'] ?> District</p>
</div>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card <?= $outstanding > 0 ? 'red' : 'green' ?>">
        <div class="stat-label">Outstanding Balance</div>
        <div class="stat-value">M <?= number_format($outstanding, 2) ?></div>
        <div class="stat-sub"><?= $outstanding > 0 ? 'Payment required' : 'All paid up!' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Bills</div>
        <div class="stat-value"><?= $billStats['total'] ?></div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Total Paid</div>
        <div class="stat-value">M <?= number_format($payStats['total_paid'], 2) ?></div>
        <div class="stat-sub">All payments</div>
    </div>
    <?php if ($latestBill): ?>
    <div class="stat-card <?= $latestBill['payment_status'] === 'unpaid' ? 'orange' : 'teal' ?>">
        <div class="stat-label">Latest Bill</div>
        <div class="stat-value">M <?= number_format($latestBill['amount_due'], 2) ?></div>
        <div class="stat-sub"><?= date('M Y', strtotime($latestBill['billing_month'])) ?> · <span class="badge badge-<?= $latestBill['payment_status'] ?>"><?= ucfirst($latestBill['payment_status']) ?></span></div>
    </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;flex-wrap:wrap;">

<!-- Usage History -->
<div class="card">
    <div class="card-title">💧 Water Usage History</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Month</th><th>Prev (m³)</th><th>Current (m³)</th><th>Used (m³)</th></tr></thead>
            <tbody>
            <?php foreach ($usageHistory as $u): ?>
            <tr>
                <td><?= date('M Y', strtotime($u['reading_month'])) ?></td>
                <td><?= $u['previous_reading'] ?></td>
                <td><?= $u['current_reading'] ?></td>
                <td><strong><?= $u['units_consumed'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$usageHistory): ?>
            <tr><td colspan="4" style="text-align:center;color:#999">No usage records</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Payments -->
<div class="card">
    <div class="card-title">💳 Recent Payments</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Month</th><th>Amount</th><th>Method</th></tr></thead>
            <tbody>
            <?php foreach ($recentPayments as $p): ?>
            <tr>
                <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                <td><?= date('M Y', strtotime($p['billing_month'])) ?></td>
                <td>M <?= number_format($p['amount_paid'], 2) ?></td>
                <td><?= ucfirst($p['payment_method']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$recentPayments): ?>
            <tr><td colspan="4" style="text-align:center;color:#999">No payments yet</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem">
        <a href="<?= BASE_URL ?>/pages/pay_bill.php" class="btn btn-primary">Pay a Bill</a>
    </div>
</div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
