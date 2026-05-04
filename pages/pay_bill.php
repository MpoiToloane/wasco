<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();
requireRole('customer');

$db = getDB1();
$acc = $_SESSION['account_number'];
$success = '';
$error = '';

// Get unpaid bills
$stmt = $db->prepare("SELECT b.*, (b.amount_due - COALESCE(SUM(p.amount_paid),0)) as balance
                      FROM bills b
                      LEFT JOIN payments p ON b.bill_id = p.bill_id
                      WHERE b.account_number = ? AND b.payment_status != 'paid'
                      GROUP BY b.bill_id
                      HAVING balance > 0
                      ORDER BY b.billing_month ASC");
$stmt->execute([$acc]);
$unpaidBills = $stmt->fetchAll();

// Pre-select bill if passed via GET
$selectedBillId = (int)($_GET['bill_id'] ?? 0);

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billId  = (int)$_POST['bill_id'];
    $amount  = (float)$_POST['amount'];
    $method  = $_POST['payment_method'];
    $ref     = trim($_POST['transaction_ref'] ?? '');

    // Validate
    $stmt = $db->prepare("SELECT b.*, c.district, c.customer_type,
                                 (b.amount_due - COALESCE(SUM(p.amount_paid),0)) as balance
                          FROM bills b
                          JOIN customers c ON b.account_number = c.account_number
                          LEFT JOIN payments p ON b.bill_id = p.bill_id
                          WHERE b.bill_id = ? AND b.account_number = ?
                          GROUP BY b.bill_id");
    $stmt->execute([$billId, $acc]);
    $bill = $stmt->fetch();

    if (!$bill) {
        $error = 'Invalid bill selected.';
    } elseif ($amount <= 0 || $amount > $bill['balance']) {
        $error = 'Invalid payment amount. Balance is M ' . number_format($bill['balance'], 2);
    } else {
        // Insert payment into DB1
        $db->beginTransaction();
        try {
            $txRef = $ref ?: strtoupper($method) . date('YmdHis') . rand(100,999);
            $stmt = $db->prepare("INSERT INTO payments (bill_id, account_number, amount_paid, payment_method, transaction_ref)
                                  VALUES (?,?,?,?,?)");
            $stmt->execute([$billId, $acc, $amount, $method, $txRef]);

            // Update bill status
            $newBalance = $bill['balance'] - $amount;
            $newStatus  = $newBalance <= 0 ? 'paid' : 'partial';
            $stmt = $db->prepare("UPDATE bills SET payment_status = ? WHERE bill_id = ?");
            $stmt->execute([$newStatus, $billId]);

            $db->commit();

            // Sync to PostgreSQL analytics
            syncToAnalytics($acc, $bill['district'], $bill['customer_type'],
                            $bill['billing_month'], $bill['units_consumed'],
                            $bill['amount_due'], $newStatus);
            auditLog($_SESSION['user_id'], 'PAYMENT', 'payments', $acc,
                     "Paid M$amount for bill #$billId via $method");

            $success = "Payment of M " . number_format($amount, 2) . " processed successfully! Ref: $txRef";
            // Refresh unpaid bills
            $stmt = $db->prepare("SELECT b.*, (b.amount_due - COALESCE(SUM(p.amount_paid),0)) as balance
                                  FROM bills b LEFT JOIN payments p ON b.bill_id = p.bill_id
                                  WHERE b.account_number = ? AND b.payment_status != 'paid'
                                  GROUP BY b.bill_id HAVING balance > 0 ORDER BY b.billing_month ASC");
            $stmt->execute([$acc]);
            $unpaidBills = $stmt->fetchAll();
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Payment failed: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Pay Water Bill</h1>
    <p>Make a payment for your outstanding bills</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!$unpaidBills): ?>
    <div class="alert alert-info">🎉 No outstanding bills! You're all caught up.</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

<!-- Bill list -->
<div class="card">
    <div class="card-title">Outstanding Bills</div>
    <?php foreach ($unpaidBills as $b): ?>
    <div style="padding:.8rem;border:2px solid <?= $b['bill_id'] == $selectedBillId ? '#1565c0' : '#e0e0e0' ?>;
                border-radius:8px;margin-bottom:.7rem;cursor:pointer;transition:border-color .2s;"
         onclick="selectBill(<?= $b['bill_id'] ?>, <?= $b['balance'] ?>)">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <strong><?= date('M Y', strtotime($b['billing_month'])) ?></strong>
                <span class="badge badge-<?= $b['payment_status'] ?>" style="margin-left:.5rem"><?= ucfirst($b['payment_status']) ?></span>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;color:#c62828">M <?= number_format($b['balance'], 2) ?></div>
                <div style="font-size:.75rem;color:#999">Due: <?= date('d M Y', strtotime($b['due_date'])) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Payment form -->
<div class="card">
    <div class="card-title">Payment Details</div>
    <form method="POST">
        <div class="form-group">
            <label>Select Bill</label>
            <select name="bill_id" id="bill_id" required onchange="updateMax(this)">
                <option value="">-- Select a bill --</option>
                <?php foreach ($unpaidBills as $b): ?>
                <option value="<?= $b['bill_id'] ?>" data-balance="<?= $b['balance'] ?>"
                    <?= $b['bill_id'] == $selectedBillId ? 'selected' : '' ?>>
                    <?= date('M Y', strtotime($b['billing_month'])) ?> — M <?= number_format($b['balance'], 2) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Amount to Pay (M)</label>
            <input type="number" name="amount" id="amount" step="0.01" min="1" placeholder="0.00" required>
            <small id="balance-hint" style="color:#666;font-size:.8rem"></small>
        </div>
        <div class="form-group">
            <label>Payment Method</label>
            <select name="payment_method" required>
                <option value="">-- Select method --</option>
                <option value="mpesa">M-Pesa</option>
                <option value="ecocash">EcoCash</option>
                <option value="bank">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="online">Online Payment</option>
            </select>
        </div>
        <div class="form-group">
            <label>Transaction Reference <small style="color:#999">(optional)</small></label>
            <input type="text" name="transaction_ref" placeholder="e.g. MPE20241201001">
        </div>
        <button type="submit" class="btn btn-success" style="width:100%;padding:.75rem;">
            💳 Process Payment
        </button>
    </form>
</div>

</div>
<?php endif; ?>

<script>
function selectBill(id, balance) {
    document.getElementById('bill_id').value = id;
    document.getElementById('amount').value = balance.toFixed(2);
    document.getElementById('amount').max = balance;
    document.getElementById('balance-hint').textContent = 'Outstanding: M ' + balance.toFixed(2);
}
function updateMax(sel) {
    const opt = sel.options[sel.selectedIndex];
    const bal = parseFloat(opt.dataset.balance || 0);
    document.getElementById('amount').max = bal;
    document.getElementById('amount').value = bal.toFixed(2);
    document.getElementById('balance-hint').textContent = bal > 0 ? 'Outstanding: M ' + bal.toFixed(2) : '';
}
<?php if ($selectedBillId): ?>
window.onload = function() {
    const sel = document.getElementById('bill_id');
    updateMax(sel);
};
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
