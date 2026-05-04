<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
startSecureSession();
requireRole('customer');

$db = getDB1();
$acc = $_SESSION['account_number'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc = trim($_POST['description'] ?? '');
    $loc  = trim($_POST['location'] ?? '');
    if (!$desc) {
        $error = 'Please describe the leakage.';
    } else {
        $stmt = $db->prepare("INSERT INTO leakage_reports (account_number, description, location) VALUES (?,?,?)");
        $stmt->execute([$acc, $desc, $loc]);
        auditLog($_SESSION['user_id'], 'LEAKAGE_REPORT', 'leakage_reports', $acc, $desc);
        $success = 'Leakage report submitted. WASCO will respond within 24 hours.';
    }
}

$stmt = $db->prepare("SELECT * FROM leakage_reports WHERE account_number = ? ORDER BY reported_at DESC");
$stmt->execute([$acc]);
$reports = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Report Water Leakage</h1>
    <p>Report leaks or water wastage in your area</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
<div class="card">
    <div class="card-title">🔧 Submit New Report</div>
    <form method="POST">
        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" rows="4" placeholder="Describe the leakage or water issue..." required></textarea>
        </div>
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" placeholder="Street, landmark, or GPS coordinates">
        </div>
        <button type="submit" class="btn btn-primary">Submit Report</button>
    </form>
</div>

<div class="card">
    <div class="card-title">📋 Your Reports</div>
    <?php if (!$reports): ?>
        <p style="color:#999;font-size:.9rem">No leakage reports submitted yet.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Description</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td><?= date('d M Y', strtotime($r['reported_at'])) ?></td>
                <td><?= htmlspecialchars(substr($r['description'], 0, 50)) ?>...</td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
