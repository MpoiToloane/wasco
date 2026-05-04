<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();
requireRole('admin');

$db = getDB1();
$success = $error = '';

// Record manual payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $billId  = (int)$_POST['bill_id'];
    $amount  = (float)$_POST['amount_paid'];
    $method  = $_POST['payment_method'];
    $ref     = trim($_POST['transaction_ref'] ?? '');

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("SELECT b.*, c.district, c.customer_type FROM bills b JOIN customers c ON b.account_number=c.account_number WHERE b.bill_id=?");
        $stmt->execute([$billId]);
        $bill = $stmt->fetch();

        if (!$bill) throw new Exception('Bill not found.');

        $txRef = $ref ?: strtoupper($method).date('YmdHis');
        $stmt = $db->prepare("INSERT INTO payments (bill_id,account_number,amount_paid,payment_method,transaction_ref,processed_by) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$billId,$bill['account_number'],$amount,$method,$txRef,$_SESSION['user_id']]);

        // Recalculate paid total and update status
        $stmt2 = $db->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE bill_id=?");
        $stmt2->execute([$billId]);
        $totalPaid = (float)$stmt2->fetchColumn();
        $newStatus = $totalPaid >= $bill['amount_due'] ? 'paid' : 'partial';
        $db->prepare("UPDATE bills SET payment_status=? WHERE bill_id=?")->execute([$newStatus,$billId]);

        $db->commit();
        syncToAnalytics($bill['account_number'],$bill['district'],$bill['customer_type'],$bill['billing_month'],$bill['units_consumed'],$bill['amount_due'],$newStatus);
        auditLog($_SESSION['user_id'],'RECORD_PAYMENT','payments',$bill['account_number'],"Bill #$billId M$amount via $method");
        $success = "Payment of M " . number_format($amount,2) . " recorded. Ref: $txRef";
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

$bills    = $db->query("SELECT b.bill_id, b.account_number, c.full_name, b.billing_month, b.amount_due, b.payment_status FROM bills b JOIN customers c ON b.account_number=c.account_number WHERE b.payment_status != 'paid' ORDER BY b.billing_month DESC")->fetchAll();
$payments = $db->query("SELECT p.*, b.billing_month, c.full_name FROM payments p JOIN bills b ON p.bill_id=b.bill_id JOIN customers c ON p.account_number=c.account_number ORDER BY p.payment_date DESC LIMIT 40")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>Payment Management</h1>
    <p>Record manual payments and view payment history</p>
</div>

<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;">

<!-- Record Payment -->
<div class="card">
    <div class="card-title">💳 Record Payment</div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>Select Unpaid Bill *</label>
            <select name="bill_id" required>
                <option value="">-- Select bill --</option>
                <?php foreach ($bills as $b): ?>
                <option value="<?= $b['bill_id'] ?>">#<?= $b['bill_id'] ?> | <?= htmlspecialchars($b['full_name']) ?> | <?= date('M Y',strtotime($b['billing_month'])) ?> | M<?= number_format($b['amount_due'],2) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Amount Paid (M) *</label>
            <input type="number" name="amount_paid" step="0.01" min="1" required>
        </div>
        <div class="form-group">
            <label>Payment Method *</label>
            <select name="payment_method" required>
                <option value="cash">Cash</option>
                <option value="mpesa">M-Pesa</option>
                <option value="ecocash">EcoCash</option>
                <option value="bank">Bank Transfer</option>
                <option value="online">Online</option>
            </select>
        </div>
        <div class="form-group">
            <label>Transaction Reference</label>
            <input type="text" name="transaction_ref" placeholder="Leave blank to auto-generate">
        </div>
        <button type="submit" class="btn btn-success" style="width:100%">Record Payment</button>
    </form>
</div>

<!-- Payment History -->
<div class="card">
    <div class="card-title">📋 Recent Payments (Last 40)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Customer</th><th>Month</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                <td><?= htmlspecialchars($p['full_name']) ?><br><small style="color:#999"><?= $p['account_number'] ?></small></td>
                <td><?= date('M Y', strtotime($p['billing_month'])) ?></td>
                <td><strong>M <?= number_format($p['amount_paid'],2) ?></strong></td>
                <td><?= ucfirst($p['payment_method']) ?></td>
                <td style="font-size:.8rem;color:#666"><?= htmlspecialchars($p['transaction_ref'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
