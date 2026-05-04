<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();
requireRole('admin');

$db = getDB1();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $tier     = trim($_POST['rate_tier']);
    $min      = (float)$_POST['usage_min'];
    $max      = $_POST['usage_max'] !== '' ? (float)$_POST['usage_max'] : null;
    $cost     = (float)$_POST['cost_per_unit'];
    $ctype    = $_POST['customer_type'];
    $effdate  = $_POST['effective_date'];
    try {
        $stmt = $db->prepare("INSERT INTO billing_rates (rate_tier,usage_min,usage_max,cost_per_unit,customer_type,effective_date) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$tier,$min,$max,$cost,$ctype,$effdate]);
        auditLog($_SESSION['user_id'],'ADD_RATE','billing_rates',$db->lastInsertId(),$tier);
        $success = "Rate '$tier' added.";
    } catch (Exception $e) { $error = $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)$_POST['rate_id'];
    $db->prepare("DELETE FROM billing_rates WHERE rate_id = ?")->execute([$id]);
    $success = "Rate deleted.";
}

$rates = $db->query("SELECT * FROM billing_rates ORDER BY customer_type, usage_min")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>Billing Rates</h1>
    <p>Manage tiered water pricing for all customer types</p>
</div>

<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <div class="card-title">➕ Add New Rate Tier</div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>Rate Tier Name *</label>
                <input type="text" name="rate_tier" placeholder="e.g. Tier 1 - Basic" required>
            </div>
            <div class="form-group">
                <label>Customer Type *</label>
                <select name="customer_type" required>
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                    <option value="industrial">Industrial</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Usage Min (m³) *</label>
                <input type="number" name="usage_min" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Usage Max (m³) <small style="color:#999">leave blank for unlimited</small></label>
                <input type="number" name="usage_max" step="0.01" min="0" placeholder="blank = no limit">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cost per Unit (M/m³) *</label>
                <input type="number" name="cost_per_unit" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Effective Date *</label>
                <input type="date" name="effective_date" required value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Rate</button>
    </form>
</div>

<div class="card">
    <div class="card-title">Current Billing Rates</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tier</th><th>Type</th><th>Min (m³)</th><th>Max (m³)</th><th>Cost/Unit</th><th>Effective</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($rates as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['rate_tier']) ?></td>
                <td><span class="badge badge-<?= $r['customer_type'] ?>"><?= ucfirst($r['customer_type']) ?></span></td>
                <td><?= $r['usage_min'] ?></td>
                <td><?= $r['usage_max'] ?? '∞' ?></td>
                <td><strong>M <?= number_format($r['cost_per_unit'], 2) ?></strong></td>
                <td><?= $r['effective_date'] ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this rate?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="rate_id" value="<?= $r['rate_id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
