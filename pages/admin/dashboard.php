<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();
requireRole('admin');

$db1 = getDB1();
$db2 = getDB2();

// Stats from DB1
$totalCustomers = $db1->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalBilled    = $db1->query("SELECT COALESCE(SUM(amount_due),0) FROM bills")->fetchColumn();
$totalCollected = $db1->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments")->fetchColumn();
$unpaidCount    = $db1->query("SELECT COUNT(*) FROM bills WHERE payment_status IN ('unpaid','overdue')")->fetchColumn();
$leakageOpen    = $db1->query("SELECT COUNT(*) FROM leakage_reports WHERE status='open'")->fetchColumn();

// Recent bills from DB1
$recentBills = $db1->query("SELECT b.*, c.full_name, c.district FROM bills b
                             JOIN customers c ON b.account_number = c.account_number
                             ORDER BY b.generated_at DESC LIMIT 10")->fetchAll();

// District summary from DB2
try {
    $districtData = $db2->query("SELECT * FROM v_district_performance ORDER BY total_billed DESC")->fetchAll();
} catch (Exception $e) {
    $districtData = [];
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>System overview — DB1: MySQL · DB2: PostgreSQL</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= $totalCustomers ?></div>
        <div class="stat-sub">Registered accounts</div>
    </div>
    <div class="stat-card teal">
        <div class="stat-label">Total Billed</div>
        <div class="stat-value">M <?= number_format($totalBilled, 0) ?></div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Total Collected</div>
        <div class="stat-value">M <?= number_format($totalCollected, 0) ?></div>
        <div class="stat-sub">All payments</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Unpaid Bills</div>
        <div class="stat-value"><?= $unpaidCount ?></div>
        <div class="stat-sub">Require follow-up</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Leakage Reports</div>
        <div class="stat-value"><?= $leakageOpen ?></div>
        <div class="stat-sub">Open / pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Outstanding</div>
        <div class="stat-value">M <?= number_format($totalBilled - $totalCollected, 0) ?></div>
        <div class="stat-sub">Not yet collected</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

<!-- Recent Bills -->
<div class="card">
    <div class="card-title">Recent Bills (DB1 - MySQL)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Customer</th><th>District</th><th>Month</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentBills as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['full_name']) ?><br><small style="color:#999"><?= $b['account_number'] ?></small></td>
                <td><?= $b['district'] ?></td>
                <td><?= date('M Y', strtotime($b['billing_month'])) ?></td>
                <td>M <?= number_format($b['amount_due'], 2) ?></td>
                <td><span class="badge badge-<?= $b['payment_status'] ?>"><?= ucfirst($b['payment_status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- District Analytics from DB2 -->
<div class="card">
    <div class="card-title" style="color:#00796b">District Performance (DB2 - PostgreSQL)</div>
    <?php if ($districtData): ?>
    <?php foreach ($districtData as $d): ?>
    <div style="padding:.7rem 0;border-bottom:1px solid #f0f0f0;">
        <div style="display:flex;justify-content:space-between;font-size:.9rem;">
            <strong><?= $d['district'] ?></strong>
            <span>M <?= number_format($d['total_billed'], 0) ?></span>
        </div>
        <div style="font-size:.75rem;color:#999"><?= $d['customers'] ?> customers · <?= $d['total_units'] ?> m³</div>
        <div style="background:#e3f2fd;border-radius:4px;height:6px;margin-top:.3rem;">
            <div style="background:#1565c0;width:<?= min(100, ($d['total_billed']/$districtData[0]['total_billed'])*100) ?>%;height:100%;border-radius:4px;"></div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <p style="color:#999;font-size:.85rem">No analytics data. Check PostgreSQL connection.</p>
    <?php endif; ?>
</div>

</div>

<div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/pages/admin/customers.php" class="btn btn-primary">Manage Customers</a>
    <a href="<?= BASE_URL ?>/pages/admin/bills.php" class="btn btn-outline">Manage Bills</a>
    <a href="<?= BASE_URL ?>/pages/admin/rates.php" class="btn btn-outline">Billing Rates</a>
    <a href="<?= BASE_URL ?>/pages/admin/payments.php" class="btn btn-outline">Payments</a>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
