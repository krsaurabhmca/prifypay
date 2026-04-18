<?php
require_once '../includes/header.php';
checkRole('admin');

$where = "1=1";

// Filters
if (isset($_GET['status']) && $_GET['status'] != '') {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND t.status = '$status'";
}
if (isset($_GET['type']) && $_GET['type'] != '') {
    $type = mysqli_real_escape_string($conn, $_GET['type']);
    $where .= " AND t.type = '$type'";
}
if (isset($_GET['user_id']) && $_GET['user_id'] != '') {
    $u_id = (int)$_GET['user_id'];
    $where .= " AND t.user_id = $u_id";
}
if (isset($_GET['date_from']) && $_GET['date_from'] != '') {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $where .= " AND DATE(t.created_at) >= '$date_from'";
}
if (isset($_GET['date_to']) && $_GET['date_to'] != '') {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $where .= " AND DATE(t.created_at) <= '$date_to'";
}

$query = "SELECT t.*, u.name as user_name, u.phone as user_phone, u.role as user_role 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          WHERE $where 
          ORDER BY t.id DESC";
$transactions = mysqli_query($conn, $query);

$all_users = mysqli_query($conn, "SELECT id, name, phone FROM users WHERE role != 'admin' ORDER BY name ASC");
?>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-filter"></i> Search & Filter</h2>
    </div>
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">User</label>
                <select name="user_id" class="form-control">
                    <option value="">All Users</option>
                    <?php while($u = mysqli_fetch_assoc($all_users)): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $u['id']) ? 'selected' : ''; ?>>
                        <?php echo $u['name']; ?> (<?php echo $u['phone']; ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="payin" <?php echo (isset($_GET['type']) && $_GET['type'] == 'payin') ? 'selected' : ''; ?>>Pay IN</option>
                    <option value="payout" <?php echo (isset($_GET['type']) && $_GET['type'] == 'payout') ? 'selected' : ''; ?>>Payout</option>
                    <option value="commission" <?php echo (isset($_GET['type']) && $_GET['type'] == 'commission') ? 'selected' : ''; ?>>Commission</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="success" <?php echo (isset($_GET['status']) && $_GET['status'] == 'success') ? 'selected' : ''; ?>>Success</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="failed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $_GET['date_from'] ?? ''; ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $_GET['date_to'] ?? ''; ?>">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="reports.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-list-check"></i> Detailed Logs</h2>
        <button onclick="window.print()" class="btn btn-secondary btn-sm">
            <i class="fas fa-print"></i> Export / Print
        </button>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Detail</th>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Fee/Comm</th>
                    <th>Admin Earning</th>
                    <th>UTR / Ref ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_amt = 0;
                $total_admin = 0;
                while ($tx = mysqli_fetch_assoc($transactions)): 
                    if($tx['status'] == 'success') {
                        $total_amt += $tx['amount'];
                        $total_admin += $tx['commission_admin'];
                    }
                ?>
                <tr>
                    <td class="fw-600" style="color: var(--text-muted);">#<?php echo $tx['id']; ?></td>
                    <td>
                        <strong style="color: var(--text-primary);"><?php echo $tx['user_name']; ?></strong><br>
                        <small class="text-muted"><?php echo $tx['user_phone']; ?> (<span class="uppercase"><?php echo $tx['user_role']; ?></span>)</small>
                    </td>
                    <td><?php echo date('d M Y, H:i', strtotime($tx['created_at'])); ?></td>
                    <td><span class="capitalize fw-600"><?php echo $tx['type']; ?></span></td>
                    <td class="fw-700" style="color: var(--text-primary);"><?php echo formatCurrency($tx['amount']); ?></td>
                    <td>
                        <?php if($tx['type'] == 'payout'): ?>
                            <small>Fee: <?php echo formatCurrency($tx['fee']); ?></small><br>
                            <small class="text-muted">Dist: <?php echo formatCurrency($tx['commission_distributor']); ?></small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="fw-700 text-success"><?php echo formatCurrency($tx['commission_admin']); ?></td>
                    <td><small class="text-muted"><?php echo $tx['utr'] ?: $tx['reference_id']; ?></small></td>
                    <td>
                        <span class="badge badge-<?php echo $tx['status']; ?>">
                            <?php echo strtoupper($tx['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($transactions) == 0): ?>
                <tr><td colspan="9">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>No records match your filters.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: 800;">TOTAL SUCCESSFUL:</td>
                    <td class="fw-800"><?php echo formatCurrency($total_amt); ?></td>
                    <td></td>
                    <td class="fw-800 text-success"><?php echo formatCurrency($total_admin); ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
