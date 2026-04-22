<?php
require_once '../includes/header.php';
checkRole('admin');

// Global Stats
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role != 'admin'"));
$total_distributors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'distributor'"));
$total_retailers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'retailer'"));

$wallet_pool = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(wallet_balance) as total FROM users"));
$api_balance = getApiBalance();

// Today's stats
$today_vol = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total, COUNT(*) as count FROM transactions WHERE status='success' AND DATE(created_at) = CURDATE()"));
$pending_kyc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM kyc_details WHERE status = 'pending'"));

$recent_tx = mysqli_query($conn, "SELECT t.*, u.name as user_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.id DESC LIMIT 10");
?>

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
        <div class="stat-icon"><i class="fas fa-server"></i></div>
        <div class="stat-info">
            <span class="stat-label">API Balance</span>
            <span class="stat-value text-info"><?php echo formatCurrency($api_balance); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon success"><i class="fas fa-bolt"></i></div>
        <div class="stat-info">
            <span class="stat-label">Today's Volume</span>
            <span class="stat-value text-success"><?php echo formatCurrency($today_vol['total'] ?? 0); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon danger"><i class="fas fa-id-card"></i></div>
        <div class="stat-info">
            <span class="stat-label">Pending KYC</span>
            <span class="stat-value text-danger"><?php echo $pending_kyc['count']; ?></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-clock-rotate-left"></i> Global Recent Transactions</h2>
        <a href="reports.php" class="btn btn-primary btn-sm">
            <i class="fas fa-chart-bar"></i> View All Reports
        </a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Ref ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tx = mysqli_fetch_assoc($recent_tx)): ?>
                <tr>
                    <td><?php echo date('d M, H:i', strtotime($tx['created_at'])); ?></td>
                    <td><strong style="color: var(--text-primary);"><?php echo $tx['user_name']; ?></strong></td>
                    <td><span class="capitalize"><?php echo $tx['type']; ?></span></td>
                    <td class="fw-700" style="color: var(--text-primary);"><?php echo formatCurrency($tx['amount']); ?></td>
                    <td><small style="color: var(--text-muted);"><?php echo $tx['reference_id']; ?></small></td>
                    <td>
                        <span class="badge badge-<?php echo $tx['status']; ?>">
                            <?php echo strtoupper($tx['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($recent_tx) == 0): ?>
                <tr><td colspan="6" class="empty-state"><i class="fas fa-inbox"></i><p>No transactions found.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
