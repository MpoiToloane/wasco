<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();
requireRole('customer');

$db = getDB1();
$acc = $_SESSION['account_number'];

$stmt = $db->prepare("SELECT b.*, COALESCE(SUM(p.amount_paid),0) as paid_amount
                      FROM bills b
                      LEFT JOIN payments p ON b.bill_id = p.bill_id
                      WHERE b.account_number = ?
                      GROUP BY b.bill_id
                      ORDER BY b.billing_month DESC");
$stmt->execute([$acc]);
$bills = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>My Bills</h1>
    <p>All water bills for account <?= $acc ?></p>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Bill ID</th>
                    <th>Month</th>
                    <th>Usage (m³)</th>
                    <th>Amount Due</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bills as $b):
                $balance = $b['amount_due'] - $b['paid_amount'];
            ?>
            <tr>
                <td>#<?= $b['bill_id'] ?></td>
                <td><?= date('M Y', strtotime($b['billing_month'])) ?></td>
                <td><?= $b['units_consumed'] ?> m³</td>
                <td>M <?= number_format($b['amount_due'], 2) ?></td>
                <td>M <?= number_format($b['paid_amount'], 2) ?></td>
                <td><strong>M <?= number_format($balance, 2) ?></strong></td>
                <td><?= date('d M Y', strtotime($b['due_date'])) ?></td>
                <td><span class="badge badge-<?= $b['payment_status'] ?>"><?= ucfirst($b['payment_status']) ?></span></td>
                <td>
                    <?php if ($balance > 0): ?>
                    <a href="<?= BASE_URL ?>/pages/pay_bill.php?bill_id=<?= $b['bill_id'] ?>" class="btn btn-primary btn-sm">Pay</a>
                    <?php else: ?>
                    <span style="color:#999;font-size:.8rem">Settled</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
