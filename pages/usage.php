<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();
requireRole('customer');

$db  = getDB1();
$acc = $_SESSION['account_number'];

$usage = $db->prepare("SELECT w.*, b.amount_due, b.payment_status FROM water_usage w
                        LEFT JOIN bills b ON w.account_number=b.account_number
                          AND DATE_FORMAT(w.reading_month,'%Y-%m')=DATE_FORMAT(b.billing_month,'%Y-%m')
                        WHERE w.account_number=?
                        ORDER BY w.reading_month DESC");
$usage->execute([$acc]);
$records = $usage->fetchAll();

// Average consumption
$avgStmt = $db->prepare("SELECT ROUND(AVG(units_consumed),2) as avg_units FROM water_usage WHERE account_number=?");
$avgStmt->execute([$acc]);
$avg = $avgStmt->fetchColumn();

// Max consumption
$maxStmt = $db->prepare("SELECT MAX(units_consumed) as max_units, reading_month FROM water_usage WHERE account_number=?");
$maxStmt->execute([$acc]);
$maxData = $maxStmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Water Usage History</h1>
    <p>Account <?= $acc ?> — detailed meter readings</p>
</div>

<div class="stat-grid">
    <div class="stat-card teal">
        <div class="stat-label">Avg Monthly Usage</div>
        <div class="stat-value"><?= $avg ?? 0 ?> m³</div>
        <div class="stat-sub">Per month average</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Highest Usage</div>
        <div class="stat-value"><?= $maxData['max_units'] ?? 0 ?> m³</div>
        <div class="stat-sub"><?= $maxData['reading_month'] ? date('M Y', strtotime($maxData['reading_month'])) : '-' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Records</div>
        <div class="stat-value"><?= count($records) ?></div>
        <div class="stat-sub">Meter readings</div>
    </div>
</div>

<!-- Usage Bar Chart (pure CSS/JS) -->
<?php if ($records): ?>
<div class="card">
    <div class="card-title">📊 Usage Chart (m³ per month)</div>
    <div style="display:flex;align-items:flex-end;gap:8px;height:160px;padding:1rem 0;">
        <?php
        $maxUnits = max(array_column($records,'units_consumed')) ?: 1;
        $chartData = array_reverse(array_slice($records, 0, 12));
        foreach ($chartData as $r):
            $height = round(($r['units_consumed']/$maxUnits)*140);
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
            <span style="font-size:.7rem;color:#666"><?= $r['units_consumed'] ?></span>
            <div style="width:100%;height:<?= $height ?>px;background:#1565c0;border-radius:4px 4px 0 0;transition:height .3s;"></div>
            <span style="font-size:.65rem;color:#999;text-align:center;"><?= date('M', strtotime($r['reading_month'])) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">All Meter Readings</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Month</th><th>Prev Reading (m³)</th><th>Curr Reading (m³)</th><th>Consumed (m³)</th><th>Bill Amount</th><th>Bill Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><?= date('M Y', strtotime($r['reading_month'])) ?></td>
                <td><?= $r['previous_reading'] ?></td>
                <td><?= $r['current_reading'] ?></td>
                <td><strong><?= $r['units_consumed'] ?> m³</strong></td>
                <td><?= $r['amount_due'] ? 'M '.number_format($r['amount_due'],2) : '—' ?></td>
                <td><?= $r['payment_status'] ? '<span class="badge badge-'.$r['payment_status'].'">'.ucfirst($r['payment_status']).'</span>' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$records): ?>
            <tr><td colspan="6" style="text-align:center;color:#999">No usage records yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
