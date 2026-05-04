<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();
requireRole('admin');

$db = getDB1();
$success = $error = '';

// Add customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $acc  = strtoupper(trim($_POST['account_number']));
    $name = trim($_POST['full_name']);
    $email= trim($_POST['email']);
    $phone= trim($_POST['phone']);
    $addr = trim($_POST['address']);
    $dist = $_POST['district'];
    $type = $_POST['customer_type'];
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if (!$acc || !$name || !$email || !$user || !$pass) {
        $error = 'All required fields must be filled.';
    } else {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO customers VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$acc,$name,$email,$phone,$addr,$dist,$type]);

            $hash = hashPassword($pass);
            $stmt = $db->prepare("INSERT INTO users (account_number,username,password_hash,role) VALUES (?,?,?,'customer')");
            $stmt->execute([$acc,$user,$hash]);

            $db->commit();
            auditLog($_SESSION['user_id'],'ADD_CUSTOMER','customers',$acc,"Added $name");
            $success = "Customer $name ($acc) added successfully.";
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Delete customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $acc = $_POST['account_number'];
    try {
        $db->prepare("DELETE FROM users WHERE account_number = ?")->execute([$acc]);
        $db->prepare("DELETE FROM customers WHERE account_number = ?")->execute([$acc]);
        auditLog($_SESSION['user_id'],'DELETE_CUSTOMER','customers',$acc,'');
        $success = "Customer $acc removed.";
    } catch (Exception $e) {
        $error = 'Cannot delete: customer has related records.';
    }
}

$customers = $db->query("SELECT c.*, u.username, u.role FROM customers c LEFT JOIN users u ON c.account_number = u.account_number AND u.role='customer' ORDER BY c.created_at DESC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1>Customer Management</h1>
    <p>Add, view, and manage WASCO customers</p>
</div>

<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Add Customer Form -->
<div class="card">
    <div class="card-title">➕ Add New Customer</div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>Account Number *</label>
                <input type="text" name="account_number" placeholder="e.g. WAS-006" required>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" placeholder="22312345">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>District *</label>
                <select name="district" required>
                    <?php foreach (['Maseru','Leribe','Berea','Mafeteng','Mohales Hoek','Quthing','Qacha Nek','Mokhotlong','Thaba-Tseka','Butha-Buthe'] as $d): ?>
                    <option><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Customer Type</label>
                <select name="customer_type">
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                    <option value="industrial">Industrial</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" placeholder="Street address">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Login Username *</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Login Password *</label>
                <input type="password" name="password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Add Customer</button>
    </form>
</div>

<!-- Customer List -->
<div class="card">
    <div class="card-title">All Customers (<?= count($customers) ?>)</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Account</th><th>Name</th><th>Email</th><th>District</th><th>Type</th><th>Username</th><th>Registered</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><strong><?= $c['account_number'] ?></strong></td>
                <td><?= htmlspecialchars($c['full_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= $c['district'] ?></td>
                <td><span class="badge badge-<?= $c['customer_type'] ?>"><?= ucfirst($c['customer_type']) ?></span></td>
                <td><?= htmlspecialchars($c['username'] ?? '-') ?></td>
                <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this customer?');" style="display:inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="account_number" value="<?= $c['account_number'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
