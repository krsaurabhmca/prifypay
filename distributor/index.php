<?php
require_once '../includes/header.php';
checkRole('distributor');

// Stats
$uId = $_SESSION['user_id'];
$retailer_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE parent_id = $uId"));

// Earnings (Commission transactions)
$earnings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id = $uId AND type = 'commission' AND status = 'success'"));

// Recent Activity from Retailers
$retailerIdsRes = mysqli_query($conn, "SELECT id FROM users WHERE parent_id = $uId");
$ids = [0]; 
while($row = mysqli_fetch_assoc($retailerIdsRes)) $ids[] = $row['id'];
$ids_str = implode(',', $ids);

// Volume in last 24h
$vol_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id IN ($ids_str) AND status = 'success' AND created_at >= NOW() - INTERVAL 1 DAY"));
$daily_vol = $vol_res['total'] ?? 0;

// Total volume
$total_vol = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM transactions WHERE user_id IN ($ids_str) AND status = 'success'"));

$recent_tx = mysqli_query($conn, "SELECT t.*, u.name as user_name FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.user_id IN ($ids_str) ORDER BY t.id DESC LIMIT 10");
?>

<div class="stats-grid">
    <div class="stat-card animate-in">
        <div class="stat-icon"><i class="fas fa-sitemap"></i></div>
        <div class="stat-info">
            <span class="stat-label">My Retailers</span>
            <span class="stat-value"><?php echo $retailer_count['count']; ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon success"><i class="fas fa-coins"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Earnings</span>
            <span class="stat-value text-success"><?php echo formatCurrency($earnings['total'] ?? 0); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <span class="stat-label">24h Volume</span>
            <span class="stat-value text-info"><?php echo formatCurrency($daily_vol); ?></span>
        </div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon warning"><i class="fas fa-chart-area"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Volume</span>
            <span class="stat-value text-warning"><?php echo formatCurrency($total_vol['total'] ?? 0); ?></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-clock-rotate-left"></i> Downline Member Activity</h2>
        <a href="reports.php" class="btn btn-primary btn-sm">
            <i class="fas fa-chart-bar"></i> Full Report
        </a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Retailer</th>
                    <th>Type</th>
                    <th>Amount</th>
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
                        <p>No retailer activity yet.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
