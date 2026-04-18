<?php
require_once '../includes/header.php';

$uId = $_SESSION['user_id'];
$where = "user_id = $uId";

if ($role == 'admin') {
    $where = "1=1";
} elseif ($role == 'distributor') {
    $retailerIdsRes = mysqli_query($conn, "SELECT id FROM users WHERE parent_id = $uId");
    $ids = [$uId];
    while($row = mysqli_fetch_assoc($retailerIdsRes)) $ids[] = $row['id'];
    $where = "user_id IN (" . implode(',', $ids) . ")";
}

$transactions = mysqli_query($conn, "SELECT t.*, u.name as user_name FROM transactions t JOIN users u ON t.user_id = u.id WHERE $where ORDER BY t.id DESC");
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-bar"></i> Transaction Reports</h2>
        <button onclick="window.print()" class="btn btn-secondary btn-sm">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if($role != 'retailer'): ?><th>User</th><?php endif; ?>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Fee/Comm</th>
                    <th>UTR/Ref</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tx = mysqli_fetch_assoc($transactions)): ?>
                <tr>
                    <td class="text-muted fw-600">#<?php echo $tx['id']; ?></td>
                    <?php if($role != 'retailer'): ?>
                    <td><strong style="color: var(--text-primary);"><?php echo $tx['user_name']; ?></strong></td>
                    <?php endif; ?>
                    <td><?php echo date('d M Y, H:i', strtotime($tx['created_at'])); ?></td>
                    <td><span class="capitalize fw-600"><?php echo $tx['type']; ?></span></td>
                    <td class="fw-700" style="color: var(--text-primary);"><?php echo formatCurrency($tx['amount']); ?></td>
                    <td>
                        <?php 
                        if ($tx['type'] == 'payout') echo formatCurrency($tx['fee']);
                        elseif ($tx['type'] == 'commission') echo '<span class="text-success">+' . formatCurrency($tx['amount']) . '</span>';
                        else echo '<span class="text-muted">-</span>';
                        ?>
                    </td>
                    <td><small class="text-muted"><?php echo $tx['utr'] ? $tx['utr'] : $tx['reference_id']; ?></small></td>
                    <td>
                        <span class="badge badge-<?php echo $tx['status']; ?>" id="status-badge-<?php echo $tx['id']; ?>">
                            <?php echo strtoupper($tx['status']); ?>
                        </span>
                        <?php if($tx['status'] == 'pending'): ?>
                            <button class="btn btn-outline-primary btn-xs" style="padding: 2px 6px; font-size: 10px; margin-left: 5px;" 
                                    onclick="checkStatus(<?php echo $tx['id']; ?>, this)">
                                <i class="fas fa-sync-alt"></i> Check
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($transactions) == 0): ?>
                <tr><td colspan="8">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No transactions found.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function checkStatus(txId, btn) {
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    const formData = new FormData();
    formData.append('tx_id', txId);
    
    fetch('ajax_check_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const badge = document.getElementById('status-badge-' + txId);
            badge.className = 'badge badge-' + data.status;
            badge.innerText = data.status.toUpperCase();
            if(data.status !== 'pending') {
                btn.style.display = 'none';
                // Reload wallet display if we have it in header
                location.reload(); 
            } else {
                btn.disabled = false;
                btn.innerHTML = originalContent;
                alert(data.message);
            }
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalContent;
        alert('Check failed. Try again later.');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
