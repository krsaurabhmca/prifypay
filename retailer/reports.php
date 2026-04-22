<?php
require_once '../includes/header.php';

$uId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Filter logic
$where = "user_id = $uId";
if ($role == 'admin' || $role == 'dev') {
    $where = "1=1";
} elseif ($role == 'distributor') {
    $retailerIdsRes = mysqli_query($conn, "SELECT id FROM users WHERE parent_id = $uId");
    $ids = [$uId];
    while($row = mysqli_fetch_assoc($retailerIdsRes)) $ids[] = $row['id'];
    $where = "user_id IN (" . implode(',', $ids) . ")";
}

// Summary Query
$summaryQuery = "SELECT 
    SUM(CASE WHEN type = 'payin' AND status = 'success' THEN amount ELSE 0 END) as total_payin,
    SUM(CASE WHEN type = 'payout' AND status = 'success' THEN amount ELSE 0 END) as total_payout,
    SUM(CASE WHEN type = 'commission' AND status = 'success' THEN amount ELSE 0 END) as total_earnings
    FROM transactions WHERE $where";
$summaryRes = mysqli_query($conn, $summaryQuery);
$summary = mysqli_fetch_assoc($summaryRes);

$transactions = mysqli_query($conn, "SELECT t.*, u.name as user_name FROM transactions t JOIN users u ON t.user_id = u.id WHERE $where ORDER BY t.id DESC");
?>

<div class="reports-header" style="margin-bottom: 25px;">
    <h1 style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin: 0;">Financial Reports</h1>
    <p style="color: var(--text-secondary); margin-top: 5px;">Detailed logs of all your pay-in, payout, and commission activities.</p>
</div>

<!-- Summary Section -->
<div class="row" style="margin-bottom: 30px; display: flex; gap: 20px;">
    <div style="flex: 1;">
        <div class="card" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: white; border: none; border-radius: 16px; padding: 20px;">
            <p style="opacity: 0.8; font-size: 13px; margin-bottom: 5px;">Total Successful Pay-In</p>
            <h2 style="font-size: 28px; font-weight: 800; margin: 0;"><?php echo formatCurrency($summary['total_payin'] ?? 0); ?></h2>
            <div style="margin-top: 15px; font-size: 11px; padding: 4px 10px; background: rgba(255,255,255,0.2); border-radius: 20px; display: inline-block;">
                <i class="fas fa-wallet"></i> Capital Flow
            </div>
        </div>
    </div>
    <div style="flex: 1;">
        <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #065f46 100%); color: white; border: none; border-radius: 16px; padding: 20px;">
            <p style="opacity: 0.8; font-size: 13px; margin-bottom: 5px;">Total Disbursed (Payout)</p>
            <h2 style="font-size: 28px; font-weight: 800; margin: 0;"><?php echo formatCurrency($summary['total_payout'] ?? 0); ?></h2>
            <div style="margin-top: 15px; font-size: 11px; padding: 4px 10px; background: rgba(255,255,255,0.2); border-radius: 20px; display: inline-block;">
                <i class="fas fa-paper-plane"></i> Sent to Bank
            </div>
        </div>
    </div>
    <div style="flex: 1;">
        <div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #92400e 100%); color: white; border: none; border-radius: 16px; padding: 20px;">
            <p style="opacity: 0.8; font-size: 13px; margin-bottom: 5px;">Total Commissions Earned</p>
            <h2 style="font-size: 28px; font-weight: 800; margin: 0;"><?php echo formatCurrency($summary['total_earnings'] ?? 0); ?></h2>
            <div style="margin-top: 15px; font-size: 11px; padding: 4px 10px; background: rgba(255,255,255,0.2); border-radius: 20px; display: inline-block;">
                <i class="fas fa-coins"></i> Your Profit
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border);">
    <div class="card-header" style="background: white; border-bottom: 1px solid var(--border); padding: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title" style="margin: 0; font-size: 16px;"><i class="fas fa-list-alt" style="margin-right: 10px; color: var(--primary);"></i> Transaction History</h3>
        <button onclick="window.print()" class="btn btn-secondary btn-sm" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #64748b;">
            <i class="fas fa-print"></i> Export
        </button>
    </div>
    <div class="table-responsive">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; text-align: left; color: #64748b;">Date & Ref ID</th>
                    <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; text-align: left; color: #64748b;">User</th>
                    <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; text-align: left; color: #64748b;">Type</th>
                    <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; text-align: right; color: #64748b;">Amount (Gross)</th>
                    <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; text-align: right; color: #64748b;">Charge/Comm</th>
                    <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; text-align: right; color: #64748b;">Net Amount</th>
                    <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px; text-align: center; color: #64748b;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($transactions) == 0): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 40px; color: #94a3b8; text-align: center;">No transactions found yet.</td>
                </tr>
                <?php endif; ?>
                <?php while ($t = mysqli_fetch_assoc($transactions)): 
                    $typeClass = '';
                    $typeIcon = '';
                    switch($t['type']) {
                        case 'payin': $typeClass = 'text-primary'; $typeIcon = 'fa-plus-circle'; break;
                        case 'payout': $typeClass = 'text-danger'; $typeIcon = 'fa-minus-circle'; break;
                        case 'commission': $typeClass = 'text-warning'; $typeIcon = 'fa-coins'; break;
                    }

                    $statusClass = '';
                    switch($t['status']) {
                        case 'success': $statusClass = 'badge-success'; break;
                        case 'pending': $statusClass = 'badge-warning'; break;
                        case 'failed': $statusClass = 'badge-danger'; break;
                        case 'refunded': $statusClass = 'badge-info'; break;
                    }

                    $gross = (float)$t['amount'];
                    $charge = (float)$t['fee'] + (float)$t['commission_distributor'] + (float)$t['commission_retailer'];
                    
                    if ($t['type'] == 'commission') {
                        $net = $gross;
                    } elseif ($t['type'] == 'payin') {
                        $net = $gross - $charge;
                    } else {
                        $net = $t['payout_amount'] ?? ($gross - $charge);
                    }
                ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px;">
                        <div style="font-weight: 600; font-size: 13px; color: var(--text-primary);"><?php echo date('d M Y, h:i A', strtotime($t['created_at'])); ?></div>
                        <div style="font-size: 11px; color: #64748b; font-family: monospace;"><?php echo $t['reference_id']; ?></div>
                        <?php if($t['utr']): ?>
                        <div style="font-size: 11px; color: #10b981; margin-top: 4px;"><i class="fas fa-check-double"></i> <?php echo $t['utr']; ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px; vertical-align: middle;">
                        <span style="font-size: 13px; font-weight: 500; color: var(--text-primary);"><?php echo $t['user_name']; ?></span>
                    </td>
                    <td style="padding: 15px; vertical-align: middle;">
                        <span class="<?php echo $typeClass; ?>" style="font-size: 12px; font-weight: 600;">
                            <i class="fas <?php echo $typeIcon; ?>" style="margin-right: 5px;"></i> <?php echo strtoupper($t['type']); ?>
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: right; vertical-align: middle; font-weight: 600; font-size: 14px; color: var(--text-primary);">
                        <?php echo formatCurrency($gross); ?>
                    </td>
                    <td style="padding: 15px; text-align: right; vertical-align: middle; font-size: 13px; color: #ef4444;">
                        <?php 
                        if ($t['type'] == 'commission') echo '<span style="color: #10b981;">Earned</span>';
                        else echo "- " . formatCurrency($charge); 
                        ?>
                    </td>
                    <td style="padding: 15px; text-align: right; vertical-align: middle; font-weight: 700; font-size: 14px; color: var(--text-primary);">
                        <?php echo formatCurrency($net); ?>
                    </td>
                    <td style="padding: 15px; text-align: center; vertical-align: middle;">
                        <span class="badge <?php echo $statusClass; ?>" style="padding: 6px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; border-radius: 6px; letter-spacing: 0.5px;">
                            <?php echo $t['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .badge-success { background: #dcfce7; color: #15803d; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-danger { background: #fee2e2; color: #b91c1c; }
    .badge-info { background: #e0f2fe; color: #0369a1; }
    .text-primary { color: #4f46e5 !important; }
    .text-danger { color: #ef4444 !important; }
    .text-warning { color: #f59e0b !important; }
    .table-responsive { width: 100%; overflow-x: auto; }
</style>

<?php require_once '../includes/footer.php'; ?>
