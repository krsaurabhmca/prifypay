<?php
require_once '../includes/header.php';
checkRole('distributor');

$uId = $_SESSION['user_id'];
$toId = isset($_GET['to']) ? (int)$_GET['to'] : 0;

if (isset($_POST['transfer'])) {
    $targetId = (int)$_POST['target_user'];
    $amount = (float)$_POST['amount'];
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);

    // Verify target is a retailer under this distributor
    $check = mysqli_query($conn, "SELECT id, name FROM users WHERE id = $targetId AND parent_id = $uId AND role = 'retailer'");
    $targetUser = mysqli_fetch_assoc($check);

    if ($targetUser && $amount > 0) {
        if ($userData['wallet_balance'] >= $amount) {
            mysqli_begin_transaction($conn);
            try {
                // Deduct from distributor
                mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance - $amount WHERE id = $uId");
                // Add to retailer
                mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $targetId");
                
                // Log transactions
                $txId1 = "FTD" . time() . rand(10, 99);
                mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, status, txid, description) VALUES ($uId, $amount, 'debit', 'success', '$txId1', 'Fund Transfer to " . $targetUser['name'] . ": $remark')");
                
                $txId2 = "FTR" . time() . rand(10, 99);
                mysqli_query($conn, "INSERT INTO transactions (user_id, amount, type, status, txid, description) VALUES ($targetId, $amount, 'credit', 'success', '$txId2', 'Fund Received from " . $userData['name'] . ": $remark')");

                mysqli_commit($conn);
                alert('success', 'Funds transferred successfully to ' . $targetUser['name']);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                alert('danger', 'Transfer failed: ' . $e->getMessage());
            }
        } else {
            alert('danger', 'Insufficient balance in your wallet.');
        }
    } else {
        alert('danger', 'Invalid target user selected.');
    }
}

$retailers = mysqli_query($conn, "SELECT id, name, phone FROM users WHERE role = 'retailer' AND parent_id = $uId");
?>

<div class="max-w-600 mx-auto">
    <div class="card animate-in">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-money-bill-transfer"></i> Internal Fund Transfer</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-info" style="border-radius: 12px;">
                <small>Available Balance:</small>
                <div class="h4 mb-0"><?php echo formatCurrency($userData['wallet_balance']); ?></div>
            </div>

            <form method="POST" class="mt-20">
                <div class="form-group mb-20">
                    <label class="form-label">Select Retailer</label>
                    <select name="target_user" class="form-control h-50" required>
                        <option value="">-- Choose Retailer --</option>
                        <?php while($r = mysqli_fetch_assoc($retailers)): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $toId == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo $r['name']; ?> (<?php echo $r['phone']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group mb-20">
                    <label class="form-label">Transfer Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="amount" class="form-control h-50" step="0.01" min="1" placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-group mb-20">
                    <label class="form-label">Remark / Note</label>
                    <input type="text" name="remark" class="form-control" placeholder="Optional remark">
                </div>

                <button type="submit" name="transfer" class="btn btn-primary btn-block h-60" onclick="return confirm('Are you sure you want to transfer this amount?')">
                    <i class="fas fa-paper-plane"></i> Initiate Transfer
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
