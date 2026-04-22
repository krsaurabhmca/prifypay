<?php
require_once '../includes/header.php';
checkRole('distributor');

$uId = $_SESSION['user_id'];
$retailers = mysqli_query($conn, "SELECT * FROM users WHERE role = 'retailer' AND parent_id = $uId");
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-store"></i> My Retailer Network</h2>
        <a href="add_retailer.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Retailer</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Wallet Balance</th>
                    <th>Status</th>
                    <th>KYC</th>
                    <th>Joined On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = mysqli_fetch_assoc($retailers)): ?>
                <tr>
                    <td><strong><?php echo $r['name']; ?></strong></td>
                    <td><?php echo $r['phone']; ?></td>
                    <td><span class="text-success fw-700"><?php echo formatCurrency($r['wallet_balance']); ?></span></td>
                    <td>
                        <span class="badge badge-<?php echo $r['status'] == 'active' ? 'success' : 'danger'; ?>">
                            <?php echo strtoupper($r['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $kyc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM kyc_details WHERE user_id = " . $r['id']));
                        $kStatus = $kyc['status'] ?? 'not_started';
                        ?>
                        <span class="badge badge-<?php echo $kStatus == 'verified' ? 'success' : ($kStatus == 'pending' ? 'warning' : 'light'); ?>">
                            <?php echo strtoupper($kStatus); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
                    <td>
                        <a href="fund_transfer.php?to=<?php echo $r['id']; ?>" class="btn btn-sm btn-light" title="Transfer Funds">
                            <i class="fas fa-money-bill-transfer"></i>
                        </a>
                        <a href="view_retailer.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-light">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($retailers) == 0): ?>
                <tr><td colspan="7" class="empty-state"><i class="fas fa-users-slash"></i><p>No retailers found in your network.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
