<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();
requireRole('admin');

$db = getDB1();
$success = $error = '';

// Generate bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $acc   = $_POST['account_number'];
    $month = $_POST['billing_month'];
    $prev  = (float)$_POST['previous_reading'];
    $curr  = (float)$_POST['current_reading'];

    if ($curr <= $prev) {
        $error = 'Current reading must be greater than previous reading.';
    } else {
        try {
            $db->beginTransaction();

            // Get customer type for rate calculation
            $stmt = $db->prepare("SELECT customer_type FROM customers WHERE account_number = ?");
            $stmt->execute([$acc]);
            $cust = $stmt->fetch();

            $units = $curr - $prev;
            $amount = calculateBill($units, $cust['customer_type']);

            // Save usage
            $stmt = $db->prepare("INSERT INTO water_usage (account_number, reading_month, previous_reading, current_reading, recorded_by)
                                  VALUES (?,?,?,?,?)");
            $stmt->execute([$acc, $month.'-01', $prev, $curr, $_SESSION['user_id']]);

            // Generate bill
            $dueDate = date('Y-m-t', strtotime($month.'-01')); // last day of month
            $stmt = $db->prepare("INSERT INTO bills (account_number, billing_month, units_consumed, amount_due, due_date)
                                  VALUES (?,?,?,?,?)");
            $stmt->execute([$acc, $month.'-01', $units, $amount, $dueDate]);
            $billId = $db->lastInsertId();

            // Send notification
            $msg = "Your WASCO water bill for $month is M " . number_format($amount, 2) . ". Due: $dueDate. Bill ID: #$billId";
            $stmt = $db->prepare("INSERT INTO notifications (account_number, bill_id, message, channel) VALUES (?,?,?,'email')");
            $stmt->execute([$acc, $billId, $msg]);

            $db->commit();
            auditLog($_SESSION['user_id'],'GENERATE_BILL','bills',$billId,"$acc - $month - M$amount");
            $success = "Bill generated for $acc: $units m³ = M " . number_format($amount, 2);
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Update bill status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $billId = (int)$_POST['bill_id'];
    $status = $_POST['status'];
    $db->prepare("UPDATE bills SET payment_status = ? WHERE bill_id = ?")->execute([$status, $billId]);
    $success = "Bill #$billId status updated to $status.";
}

$customers = $db->query("SELECT account_number, full_name FROM customers ORDER BY full_name")->fetchAll();
$bills = $db->query("SELECT b.*, c.full_name FROM bills b JOIN customers c ON b.account_number = c.account_number ORDER BY b.generated_at DESC LIMIT 30")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>Bill Management</h1>
    <p>Generate bills and manage payment statuses</p>
</div>

<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Generate Bill Form (Embedded SQL demo) -->
<div class="card">
    <div class="card-title">📄 Generate New Bill</div>
    <p style="font-size:.85rem;color:#666;margin-bottom:1rem">Uses embedded SQL to calculate bill from meter readings and billing rate tiers.</p>
    <form method="POST">
        <input type="hidden" name="action" value="generate">
        <div class="form-row">
            <div class="form-group">
                <label>Customer Account *</label>
                <select name="account_number" required>
                    <option value="">-- Select customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['account_number'] ?>"><?= $c['account_number'] ?> – <?= htmlspecialchars($c['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Billing Month *</label>
                <input type="month" name="billing_month" required value="<?= date('Y-m') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Previous Meter Reading (m³) *</label>
                <input type="number" name="previous_reading" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Current Meter Reading (m³) *</label>
                <input type="number" name="current_reading" step="0.01" min="0" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Generate Bill &amp; Notify Customer</button>
    </form>
</div>

<!-- Bills List -->
<div class="card">
    <div class="card-title">All Bills (Recent 30)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Customer</th><th>Month</th><th>Usage</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Update</th></tr></thead>
            <tbody>
            <?php foreach ($bills as $b): ?>
            <tr>
                <td>#<?= $b['bill_id'] ?></td>
                <td><?= htmlspecialchars($b['full_name']) ?><br><small style="color:#999"><?= $b['account_number'] ?></small></td>
                <td><?= date('M Y', strtotime($b['billing_month'])) ?></td>
                <td><?= $b['units_consumed'] ?> m³</td>
                <td>M <?= number_format($b['amount_due'], 2) ?></td>
                <td><?= date('d M Y', strtotime($b['due_date'])) ?></td>
                <td><span class="badge badge-<?= $b['payment_status'] ?>"><?= ucfirst($b['payment_status']) ?></span></td>
                <td>
                    <form method="POST" style="display:flex;gap:.3rem;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="bill_id" value="<?= $b['bill_id'] ?>">
                        <select name="status" style="font-size:.8rem;padding:.2rem">
                            <?php foreach (['unpaid','partial','paid','overdue'] as $s): ?>
                            <option value="<?= $s ?>" <?= $b['payment_status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline">✓</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
