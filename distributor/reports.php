<?php
require_once '../includes/header.php';
checkRole('distributor');

$uId = $_SESSION['user_id'];
$retailerIdsRes = mysqli_query($conn, "SELECT id FROM users WHERE parent_id = $uId");
$ids = [$uId];
while($row = mysqli_fetch_assoc($retailerIdsRes)) $ids[] = $row['id'];
$ids_str = implode(',', $ids);

$where = "t.user_id IN ($ids_str)";

// Filters
if (isset($_GET['status']) && $_GET['status'] != '') {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND t.status = '$status'";
}
if (isset($_GET['type']) && $_GET['type'] != '') {
    $type = mysqli_real_escape_string($conn, $_GET['type']);
    $where .= " AND t.type = '$type'";
}
if (isset($_GET['date_from']) && $_GET['date_from'] != '') {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $where .= " AND DATE(t.created_at) >= '$date_from'";
}
if (isset($_GET['date_to']) && $_GET['date_to'] != '') {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $where .= " AND DATE(t.created_at) <= '$date_to'";
}

$query = "SELECT t.*, u.name as user_name FROM transactions t JOIN users u ON t.user_id = u.id WHERE $where ORDER BY t.id DESC";
$transactions = mysqli_query($conn, $query);
?>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-filter"></i> Filter Activity</h2>
    </div>
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Type</label>
                <select name="type" class="form-control">
                    <option value="">All</option>
                    <option value="payin" <?php echo (@$_GET['type'] == 'payin' ? 'selected':''); ?>>Pay IN</option>
                    <option value="payout" <?php echo (@$_GET['type'] == 'payout' ? 'selected':''); ?>>Payout</option>
                    <option value="commission" <?php echo (@$_GET['type'] == 'commission' ? 'selected':''); ?>>Commission</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="success" <?php echo (@$_GET['status'] == 'success' ? 'selected':''); ?>>Success</option>
                    <option value="pending" <?php echo (@$_GET['status'] == 'pending' ? 'selected':''); ?>>Pending</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo @$_GET['date_from']; ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo @$_GET['date_to']; ?>">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="reports.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

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
                    <th>Date</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tx = mysqli_fetch_assoc($transactions)): ?>
                <tr>
                    <td><?php echo date('d M Y, H:i', strtotime($tx['created_at'])); ?></td>
                    <td>
                        <strong style="color: var(--text-primary);">
                            <?php echo ($tx['user_id'] == $uId) ? 'YOU (Self Comm)' : $tx['user_name']; ?>
                        </strong>
                    </td>
                    <td><span class="capitalize fw-600"><?php echo $tx['type']; ?></span></td>
                    <td class="fw-700" style="color: var(--text-primary);"><?php echo formatCurrency($tx['amount']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $tx['status']; ?>">
                            <?php echo strtoupper($tx['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($transactions) == 0): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No records match your filters.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
