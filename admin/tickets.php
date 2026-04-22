<?php
require_once '../includes/header.php';
checkRole(['admin', 'dev']);

$status = $_GET['status'] ?? 'open';
$tickets = mysqli_query($conn, "SELECT st.*, u.name, u.role as user_role FROM support_tickets st JOIN users u ON st.user_id = u.id WHERE st.status = '$status' ORDER BY st.updated_at DESC");
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-ticket-alt"></i> Support Tickets Management</h2>
        <div class="header-actions">
            <a href="?status=open" class="btn <?php echo $status == 'open' ? 'btn-primary' : 'btn-light'; ?>">Open</a>
            <a href="?status=in_progress" class="btn <?php echo $status == 'in_progress' ? 'btn-warning' : 'btn-light'; ?>">In Progress</a>
            <a href="?status=closed" class="btn <?php echo $status == 'closed' ? 'btn-success' : 'btn-light'; ?>">Closed</a>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>User</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($t = mysqli_fetch_assoc($tickets)): ?>
                <tr>
                    <td>
                        <strong>#TK-<?php echo $t['id']; ?></strong><br>
                        <small><?php echo $t['subject']; ?></small>
                    </td>
                    <td>
                        <?php echo $t['name']; ?><br>
                        <span class="badge badge-light"><?php echo strtoupper($t['user_role']); ?></span>
                    </td>
                    <td><?php echo $t['category']; ?></td>
                    <td><span class="capitalize priority-<?php echo $t['priority']; ?>"><?php echo $t['priority']; ?></span></td>
                    <td><?php echo date('d M, H:i', strtotime($t['created_at'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $t['status'] == 'open' ? 'info' : ($t['status'] == 'closed' ? 'success' : 'warning'); ?>">
                            <?php echo strtoupper($t['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="ticket_view.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-reply"></i> View/Reply
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($tickets) == 0): ?>
                <tr><td colspan="7" class="empty-state"><i class="fas fa-inbox"></i><p>No <?php echo $status; ?> tickets found.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .priority-high { color: #dc2626; font-weight: 700; }
    .priority-medium { color: #f59e0b; font-weight: 600; }
    .priority-low { color: #64748b; }
</style>

<?php require_once '../includes/footer.php'; ?>
