<?php
require_once '../includes/header.php';
checkRole('admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_comm'])) {
    $id = (int)$_POST['id'];
    $value = (float)$_POST['value'];
    $method = mysqli_real_escape_string($conn, $_POST['method']);

    $sql = "UPDATE commissions SET value = $value, method = '$method' WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        alert('success', 'Commission updated!');
    } else {
        alert('danger', 'Error updating commission.');
    }
}

$commissions = mysqli_query($conn, "SELECT * FROM commissions ORDER BY role ASC, transaction_type ASC");
?>

<div class="max-w-800 mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-percentage"></i> Set Commissions / Fees</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Method</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = mysqli_fetch_assoc($commissions)): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                            <td><span class="badge badge-role capitalize"><?php echo $c['role']; ?></span></td>
                            <td><span class="capitalize fw-600" style="color: var(--text-primary);"><?php echo $c['transaction_type']; ?></span></td>
                            <td>
                                <input type="number" step="0.01" name="value" value="<?php echo $c['value']; ?>" class="form-control" style="width: 100px; padding: 6px 10px;">
                            </td>
                            <td>
                                <select name="method" class="form-control" style="width: auto;">
                                    <option value="percentage" <?php echo $c['method'] == 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                    <option value="flat" <?php echo $c['method'] == 'flat' ? 'selected' : ''; ?>>Flat Fee (₹)</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" name="update_comm" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="info-box" style="margin-top: 24px;">
        <h4><i class="fas fa-info-circle"></i> Understanding Commissions</h4>
        <ul>
            <li><strong>Retailer Payout:</strong> This is the FEE charged to the retailer. <em>(e.g. ₹10 per Payout)</em></li>
            <li><strong>Distributor Payout:</strong> This is the commission GIVEN to the distributor for every retailer payout. <em>(e.g. ₹5 per Payout)</em></li>
            <li><strong>Payin:</strong> Commissions are calculated during money addition (coming soon in calculation logic).</li>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
