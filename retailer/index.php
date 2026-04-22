<?php
require_once '../includes/header.php';
checkRole('retailer');

// Fetch stats
$uId = $_SESSION['user_id'];
$stats_payout = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total, COUNT(*) as count FROM transactions WHERE user_id = $uId AND type = 'payout' AND status = 'success'"));
$stats_payin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total, COUNT(*) as count FROM transactions WHERE user_id = $uId AND type = 'payin' AND status = 'success'"));
$bene_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM beneficiaries WHERE user_id = $uId AND status = 'verified'"));

$recent_tx = mysqli_query($conn, "SELECT * FROM transactions WHERE user_id = $uId ORDER BY id DESC LIMIT 8");

if ($userData['kyc_status'] != 'verified') {
    $kyc_label = getKycStatusLabel($userData['kyc_status']);
    echo "<div class='alert alert-warning' style='margin-bottom: 24px;'>
            <div style='display: flex; align-items: center; justify-content: space-between; width: 100%;'>
                <div>
                    <i class='fas fa-exclamation-triangle'></i> 
                    <strong>KYC Verification Required:</strong> Your current status is $kyc_label. Please complete your KYC to enable all features.
                </div>
                <a href='kyc.php' class='btn btn-primary btn-sm'>Complete KYC</a>
            </div>
          </div>";
}
?>

<div class="stats-grid">
    <div class="stat-card animate-in">
        <div class="stat-icon success"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Pay-IN</span>
            <span class="stat-value text-success"><?php echo formatCurrency($stats_payin['total'] ?? 0); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon danger"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Payout</span>
            <span class="stat-value text-danger"><?php echo formatCurrency($stats_payout['total'] ?? 0); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon info"><i class="fas fa-address-book"></i></div>
        <div class="stat-info">
            <span class="stat-label">Beneficiaries</span>
            <span class="stat-value text-info"><?php echo $bene_count['count'] ?? 0; ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon"><i class="fas fa-wifi"></i></div>
        <div class="stat-info">
            <span class="stat-label">API Status</span>
            <span class="stat-value text-success" style="font-size: 16px;">● Online</span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px;">
    <a href="payin.php" class="btn btn-primary" style="padding: 14px; border-radius: var(--radius);">
        <i class="fas fa-plus-circle"></i> Add Money
    </a>
    <a href="fast_transfer.php" class="btn btn-primary" style="padding: 14px; border-radius: var(--radius); background: linear-gradient(135deg, #f6c23e, #f4b619); border: none; color: #000;">
        <i class="fas fa-bolt"></i> Fast Transfer
    </a>
    <a href="payout.php" class="btn btn-secondary" style="padding: 14px; border-radius: var(--radius);">
        <i class="fas fa-paper-plane"></i> Send Payout
    </a>
    <a href="beneficiaries.php" class="btn btn-secondary" style="padding: 14px; border-radius: var(--radius);">
        <i class="fas fa-user-plus"></i> Add Beneficiary
    </a>
    <a href="reports.php" class="btn btn-secondary" style="padding: 14px; border-radius: var(--radius);">
        <i class="fas fa-chart-bar"></i> View Reports
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Transactions</h2>
        <a href="reports.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Ref ID</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tx = mysqli_fetch_assoc($recent_tx)): ?>
                <tr>
                    <td><?php echo date('d M, H:i', strtotime($tx['created_at'])); ?></td>
                    <td><small style="color: var(--text-muted);"><?php echo $tx['reference_id']; ?></small></td>
                    <td><span class="capitalize"><?php echo $tx['type']; ?></span></td>
                    <td class="fw-700" style="color: var(--text-primary);"><?php echo formatCurrency($tx['amount']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $tx['status']; ?>">
                            <?php echo strtoupper($tx['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($recent_tx) == 0): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No transactions yet. Start by adding money!</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
