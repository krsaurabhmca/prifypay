<?php
require_once '../includes/header.php';
checkRole('dev');

// Global Stats (Same as Admin but Dev can see more if needed)
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"));
$total_distributors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'distributor'"));
$total_retailers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'retailer'"));

$wallet_pool = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(wallet_balance) as total FROM users"));
$api_balance = getApiBalance();
$kyc_balance_raw = getKYCBalance();
$kyc_balance = ($kyc_balance_raw['status'] == 'Success') ? $kyc_balance_raw['balance'] : 0;

// Today's stats
$today_vol = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total, COUNT(*) as count FROM transactions WHERE status='success' AND DATE(created_at) = CURDATE()"));
$pending_kyc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM kyc_details WHERE status = 'pending'"));

$recent_tx = mysqli_query($conn, "SELECT t.*, u.name as user_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.id DESC LIMIT 10");
?>

<div class="alert alert-primary mb-25" style="border-radius: 16px; border: none; background: var(--auth-gradient); color: white; display: flex; align-items: center; gap: 15px;">
    <i class="fas fa-terminal" style="font-size: 24px; opacity: 0.8;"></i>
    <div>
        <h4 class="mb-0 fw-800">Developer Master Console</h4>
        <p class="mb-0 small opacity-80">You have full system-wide administrative and configuration privileges.</p>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card animate-in">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Users</span>
            <span class="stat-value"><?php echo $total_users['count']; ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon info"><i class="fas fa-building"></i></div>
        <div class="stat-info">
            <span class="stat-label">Distributors</span>
            <span class="stat-value text-info"><?php echo $total_distributors['count']; ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon warning"><i class="fas fa-store"></i></div>
        <div class="stat-info">
            <span class="stat-label">Retailers</span>
            <span class="stat-value text-warning"><?php echo $total_retailers['count']; ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon success"><i class="fas fa-wallet"></i></div>
        <div class="stat-info">
            <span class="stat-label">Wallet Pool</span>
            <span class="stat-value text-success"><?php echo formatCurrency($wallet_pool['total'] ?? 0); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon info"><i class="fas fa-server"></i></div>
        <div class="stat-info">
            <span class="stat-label">SLPE Balance</span>
            <span class="stat-value text-info"><?php echo formatCurrency($api_balance); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon warning"><i class="fas fa-id-card"></i></div>
        <div class="stat-info">
            <span class="stat-label">KYC Balance</span>
            <span class="stat-value text-warning"><?php echo formatCurrency($kyc_balance); ?></span>
        </div>
    </div>
</div>

<div class="row mt-25">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-history"></i> System-Wide Activity</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>TXID</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tx = mysqli_fetch_assoc($recent_tx)): ?>
                        <tr>
                            <td><strong><?php echo $tx['user_name']; ?></strong></td>
                            <td><small><?php echo $tx['txid'] ?: $tx['reference_id']; ?></small></td>
                            <td><strong><?php echo formatCurrency($tx['amount']); ?></strong></td>
                            <td><span class="badge badge-light"><?php echo strtoupper($tx['type']); ?></span></td>
                            <td><span class="badge badge-<?php echo $tx['status'] == 'success' ? 'success' : ($tx['status'] == 'pending' ? 'warning' : 'danger'); ?>"><?php echo strtoupper($tx['status']); ?></span></td>
                            <td><?php echo date('d M, H:i', strtotime($tx['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
