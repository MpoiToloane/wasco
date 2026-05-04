<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();
requireRole('branch_manager');

$db1 = getDB1();
$db2 = getDB2();

// ---- Advanced SQL Reports ----

// 1. Outstanding balances view (from DB1 VIEW)
$outstanding = $db1->query("SELECT * FROM v_customer_outstanding ORDER BY outstanding_balance DESC")->fetchAll();

// 2. Monthly usage report (from DB1 VIEW)
$usageReport = $db1->query("SELECT * FROM v_monthly_usage_report ORDER BY month DESC, total_billed DESC LIMIT 30")->fetchAll();

// 3. Payment method breakdown
$paymentMethods = $db1->query("SELECT payment_method, COUNT(*) as count, SUM(amount_paid) as total
                                FROM payments GROUP BY payment_method ORDER BY total DESC")->fetchAll();

// 4. Top debtors
$topDebtors = $db1->query("SELECT c.full_name, c.account_number, c.district,
                            SUM(b.amount_due) as total_billed,
                            COALESCE(SUM(p.amount_paid),0) as total_paid,
                            (SUM(b.amount_due) - COALESCE(SUM(p.amount_paid),0)) as debt
                            FROM customers c
                            JOIN bills b ON c.account_number=b.account_number
                            LEFT JOIN payments p ON b.bill_id=p.bill_id
                            GROUP BY c.account_number
                            HAVING debt > 0
                            ORDER BY debt DESC LIMIT 10")->fetchAll();

// 5. Yearly trend from DB2
try {
    $yearlyTrend = $db2->query("SELECT * FROM v_yearly_trend ORDER BY year DESC, month DESC LIMIT 24")->fetchAll();
} catch (Exception $e) {
    $yearlyTrend = [];
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>Reports &amp; Analytics</h1>
    <p>Advanced SQL reports from DB1 (MySQL) and DB2 (PostgreSQL)</p>
</div>

<!-- Payment Method Breakdown -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;margin-bottom:1.5rem;">
<div class="card">
    <div class="card-title">💳 Payment Methods</div>
    <?php
    $totalPayments = array_sum(array_column($paymentMethods,'total'));
    foreach ($paymentMethods as $pm):
        $pct = $totalPayments > 0 ? ($pm['total']/$totalPayments)*100 : 0;
    ?>
    <div style="margin-bottom:.8rem;">
        <div style="display:flex;justify-content:space-between;font-size:.85rem;">
            <span><?= ucfirst($pm['payment_method']) ?> (<?= $pm['count'] ?>)</span>
            <span>M <?= number_format($pm['total'],2) ?></span>
        </div>
        <div style="background:#f0f0f0;border-radius:4px;height:8px;margin-top:.3rem;">
            <div style="background:#1565c0;width:<?= round($pct) ?>%;height:100%;border-radius:4px;"></div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$paymentMethods): ?><p style="color:#999;font-size:.85rem">No payment data.</p><?php endif; ?>
</div>

<!-- Top Debtors -->
<div class="card">
    <div class="card-title">⚠️ Top Debtors (Outstanding Balances)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Customer</th><th>District</th><th>Billed</th><th>Paid</th><th>Debt</th></tr></thead>
            <tbody>
            <?php foreach ($topDebtors as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['full_name']) ?><br><small style="color:#999"><?= $d['account_number'] ?></small></td>
                <td><?= $d['district'] ?></td>
                <td>M <?= number_format($d['total_billed'],2) ?></td>
                <td>M <?= number_format($d['total_paid'],2) ?></td>
                <td style="color:#c62828;font-weight:700">M <?= number_format($d['debt'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$topDebtors): ?>
            <tr><td colspan="5" style="text-align:center;color:#2e7d32">🎉 No outstanding debts!</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Customer Outstanding View -->
<div class="card">
    <div class="card-title">📋 Customer Outstanding Balances (VIEW: v_customer_outstanding — DB1)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Account</th><th>Name</th><th>District</th><th>Total Billed</th><th>Total Paid</th><th>Outstanding</th></tr></thead>
            <tbody>
            <?php foreach ($outstanding as $o): ?>
            <tr>
                <td><?= $o['account_number'] ?></td>
                <td><?= htmlspecialchars($o['full_name']) ?></td>
                <td><?= $o['district'] ?></td>
                <td>M <?= number_format($o['total_billed'],2) ?></td>
                <td>M <?= number_format($o['total_paid'],2) ?></td>
                <td style="color:<?= $o['outstanding_balance']>0?'#c62828':'#2e7d32' ?>;font-weight:700">
                    M <?= number_format($o['outstanding_balance'],2) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Monthly Usage Report -->
<div class="card">
    <div class="card-title">📊 Monthly Usage Report by District (VIEW: v_monthly_usage_report — DB1)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Month</th><th>District</th><th>Type</th><th>Customers</th><th>Total Units (m³)</th><th>Total Billed</th></tr></thead>
            <tbody>
            <?php foreach ($usageReport as $u): ?>
            <tr>
                <td><?= date('M Y', strtotime($u['month'].'-01')) ?></td>
                <td><?= $u['district'] ?></td>
                <td><span class="badge badge-<?= $u['customer_type'] ?>"><?= ucfirst($u['customer_type']) ?></span></td>
                <td><?= $u['customer_count'] ?></td>
                <td><?= number_format($u['total_units'],2) ?> m³</td>
                <td>M <?= number_format($u['total_billed'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Yearly Trend from DB2 -->
<?php if ($yearlyTrend): ?>
<div class="card">
    <div class="card-title" style="color:#00796b">📈 Yearly Revenue Trend (VIEW: v_yearly_trend — DB2 PostgreSQL)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Year</th><th>Month</th><th>Total Units (m³)</th><th>Revenue (M)</th></tr></thead>
            <tbody>
            <?php foreach ($yearlyTrend as $y): ?>
            <tr>
                <td><?= (int)$y['year'] ?></td>
                <td><?= date('F', mktime(0,0,0,(int)$y['month'],1)) ?></td>
                <td><?= number_format($y['total_units'],2) ?></td>
                <td>M <?= number_format($y['total_revenue'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
