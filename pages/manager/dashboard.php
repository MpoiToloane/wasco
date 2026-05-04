<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();
requireRole('branch_manager');

$db1 = getDB1();
$db2 = getDB2();

// ---- DB1 stats ----
$totalCustomers = $db1->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalBilled    = $db1->query("SELECT COALESCE(SUM(amount_due),0) FROM bills")->fetchColumn();
$totalPaid      = $db1->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments")->fetchColumn();
$outstanding    = $totalBilled - $totalPaid;

// Monthly trend from DB1
$monthlyTrend = $db1->query("SELECT DATE_FORMAT(billing_month,'%Y-%m') as month,
                              COUNT(*) as bill_count,
                              SUM(amount_due) as total_billed,
                              SUM(units_consumed) as total_units
                              FROM bills GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll();

// Customer type breakdown from DB1
$typeBreakdown = $db1->query("SELECT c.customer_type, COUNT(DISTINCT c.account_number) as count,
                               COALESCE(SUM(b.amount_due),0) as total_billed
                               FROM customers c LEFT JOIN bills b ON c.account_number=b.account_number
                               GROUP BY c.customer_type")->fetchAll();

// ---- DB2 analytics ----
try {
    $districtPerf = $db2->query("SELECT * FROM v_district_performance")->fetchAll();
    $yearlyTrend  = $db2->query("SELECT * FROM v_yearly_trend ORDER BY year,month")->fetchAll();
    $districtSums = $db2->query("SELECT * FROM district_summaries WHERE period_type='monthly' ORDER BY period_start DESC LIMIT 20")->fetchAll();
    $db2Connected = true;
} catch (Exception $e) {
    $districtPerf = $yearlyTrend = $districtSums = [];
    $db2Connected = false;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>Branch Manager Dashboard</h1>
    <p>Summative insights on water usage &amp; billing across all districts</p>
</div>

<!-- DB connection status -->
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <span style="background:#e8f5e9;color:#2e7d32;padding:.3rem .8rem;border-radius:20px;font-size:.8rem;">✅ DB1: MySQL (Operational) — Connected</span>
    <span style="background:<?= $db2Connected?'#e8f5e9':'#ffebee' ?>;color:<?= $db2Connected?'#2e7d32':'#c62828' ?>;padding:.3rem .8rem;border-radius:20px;font-size:.8rem;">
        <?= $db2Connected?'✅':'❌' ?> DB2: PostgreSQL (Analytics) — <?= $db2Connected?'Connected':'Unavailable' ?>
    </span>
</div>

<!-- Key Stats -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= number_format($totalCustomers) ?></div>
        <div class="stat-sub">All districts</div>
    </div>
    <div class="stat-card teal">
        <div class="stat-label">Total Billed</div>
        <div class="stat-value">M <?= number_format($totalBilled,0) ?></div>
        <div class="stat-sub">All time revenue</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Total Collected</div>
        <div class="stat-value">M <?= number_format($totalPaid,0) ?></div>
        <div class="stat-sub">Payments received</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Outstanding</div>
        <div class="stat-value">M <?= number_format($outstanding,0) ?></div>
        <div class="stat-sub">Uncollected revenue</div>
    </div>
</div>

<!-- Monthly Trend from DB1 -->
<div class="card">
    <div class="card-title">📅 Monthly Billing Trend (DB1 — MySQL)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Month</th><th>Bills Generated</th><th>Total Billed (M)</th><th>Total Usage (m³)</th><th>Avg Bill (M)</th></tr></thead>
            <tbody>
            <?php foreach ($monthlyTrend as $m): ?>
            <tr>
                <td><?= date('M Y', strtotime($m['month'].'-01')) ?></td>
                <td><?= $m['bill_count'] ?></td>
                <td>M <?= number_format($m['total_billed'],2) ?></td>
                <td><?= number_format($m['total_units'],2) ?> m³</td>
                <td>M <?= number_format($m['total_billed'] / max(1,$m['bill_count']),2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

<!-- Customer Type Breakdown -->
<div class="card">
    <div class="card-title">👥 Customer Type Breakdown</div>
    <?php foreach ($typeBreakdown as $t): ?>
    <div style="margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;">
            <strong><?= ucfirst($t['customer_type']) ?></strong>
            <span><?= $t['count'] ?> customers · M <?= number_format($t['total_billed'],2) ?></span>
        </div>
        <div style="background:#f0f0f0;border-radius:4px;height:10px;">
            <?php $pct = $totalBilled > 0 ? ($t['total_billed']/$totalBilled)*100 : 0; ?>
            <div style="background:#1565c0;width:<?= round($pct) ?>%;height:100%;border-radius:4px;"></div>
        </div>
        <div style="font-size:.75rem;color:#999;margin-top:.2rem;"><?= round($pct,1) ?>% of total revenue</div>
    </div>
    <?php endforeach; ?>
</div>

<!-- District Performance from DB2 -->
<div class="card">
    <div class="card-title" style="color:<?= $db2Connected?'#00796b':'#999' ?>">🗺️ District Performance (DB2 — PostgreSQL)</div>
    <?php if ($districtPerf): ?>
    <?php foreach ($districtPerf as $d): ?>
    <div style="padding:.6rem 0;border-bottom:1px solid #f5f5f5;">
        <div style="display:flex;justify-content:space-between;font-size:.9rem;">
            <strong><?= htmlspecialchars($d['district']) ?></strong>
            <span>M <?= number_format($d['total_billed'],0) ?></span>
        </div>
        <div style="font-size:.75rem;color:#999"><?= $d['customers'] ?> customers · <?= number_format($d['total_units'],0) ?> m³ · Avg M <?= number_format($d['avg_bill_per_customer'],2) ?>/customer</div>
        <div style="background:#e0f2f1;border-radius:4px;height:6px;margin-top:.3rem;">
            <?php $maxBilled = $districtPerf[0]['total_billed'] ?? 1; ?>
            <div style="background:#00796b;width:<?= min(100,($d['total_billed']/$maxBilled)*100) ?>%;height:100%;border-radius:4px;"></div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="alert alert-info">Analytics data unavailable. PostgreSQL DB2 not connected or no data synced yet.</div>
    <?php endif; ?>
</div>

</div>

<!-- District Summaries Table from DB2 -->
<?php if ($districtSums): ?>
<div class="card">
    <div class="card-title" style="color:#00796b">📊 Monthly District Summaries (DB2 — PostgreSQL)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>District</th><th>Period</th><th>Customers</th><th>Consumption (m³)</th><th>Revenue (M)</th><th>Outstanding (M)</th></tr></thead>
            <tbody>
            <?php foreach ($districtSums as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['district']) ?></td>
                <td><?= date('M Y', strtotime($s['period_start'])) ?></td>
                <td><?= $s['total_customers'] ?></td>
                <td><?= number_format($s['total_consumption'],2) ?></td>
                <td>M <?= number_format($s['total_revenue'],2) ?></td>
                <td style="color:<?= $s['outstanding_balance']>0?'#c62828':'#2e7d32' ?>"><strong>M <?= number_format($s['outstanding_balance'],2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
