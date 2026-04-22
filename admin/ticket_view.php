<?php
require_once '../includes/header.php';
checkRole(['admin', 'dev']);

$tId = (int)$_GET['id'];
$adminId = $_SESSION['user_id'];

// Get Ticket
$ticketRes = mysqli_query($conn, "SELECT st.*, u.name as user_name, u.email as user_email, u.phone as user_phone FROM support_tickets st JOIN users u ON st.user_id = u.id WHERE st.id = $tId");
$ticket = mysqli_fetch_assoc($ticketRes);

if (!$ticket) {
    echo "Invalid Ticket ID.";
    exit;
}

// Handle Status Update
if (isset($_POST['update_status'])) {
    $newStatus = mysqli_real_escape_string($conn, $_POST['new_status']);
    mysqli_query($conn, "UPDATE support_tickets SET status = '$newStatus' WHERE id = $tId");
    alert('success', "Status updated to $newStatus.");
    $ticket['status'] = $newStatus;
}

// Post Reply
if (isset($_POST['send_reply'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['message']);
    mysqli_query($conn, "INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES ($tId, $adminId, '$msg')");
    mysqli_query($conn, "UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = $tId");
    alert('success', 'Reply sent.');
}

$messages = mysqli_query($conn, "SELECT tm.*, u.name, u.role FROM ticket_messages tm JOIN users u ON tm.user_id = u.id WHERE tm.ticket_id = $tId ORDER BY tm.created_at ASC");
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-20 animate-in">
            <div class="card-header">
                <h2 class="card-title">Conversation: #TK-<?php echo $tId; ?></h2>
                <a href="tickets.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            
            <div class="ticket-chat-body" style="padding: 20px; max-height: 600px; overflow-y: auto; background: #f8fafc;">
                <?php while ($m = mysqli_fetch_assoc($messages)): ?>
                    <div class="chat-message <?php echo $m['role'] == 'admin' ? 'mine' : 'theirs'; ?>">
                        <div class="chat-info">
                            <strong><?php echo $m['name']; ?></strong> 
                            <span class="badge badge-light"><?php echo strtoupper($m['role']); ?></span>
                            <small><?php echo date('d M, H:i', strtotime($m['created_at'])); ?></small>
                        </div>
                        <div class="chat-text">
                            <?php echo nl2br($m['message']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="card-footer">
                <form method="POST">
                    <div class="form-group">
                        <textarea name="message" class="form-control" rows="4" placeholder="Type your reply to the user..." required></textarea>
                    </div>
                    <div class="mt-15 d-flex justify-content-end">
                        <button type="submit" name="send_reply" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Reply to User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card animate-in" style="animation-delay: 0.1s;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-info-circle"></i> Ticket Details</h2>
            </div>
            <div class="card-body">
                <div class="detail-item mb-15">
                    <label class="text-muted small fw-600 d-block">USER</label>
                    <div><strong><?php echo $ticket['user_name']; ?></strong></div>
                    <div class="small"><?php echo $ticket['user_phone']; ?> | <?php echo $ticket['user_email']; ?></div>
                </div>
                <div class="detail-item mb-15">
                    <label class="text-muted small fw-600 d-block">SUBJECT</label>
                    <div><?php echo $ticket['subject']; ?></div>
                </div>
                <div class="detail-item mb-20">
                    <label class="text-muted small fw-600 d-block">PRIORITY</label>
                    <span class="capitalize"><?php echo $ticket['priority']; ?></span>
                </div>

                <hr class="mb-20">

                <form method="POST">
                    <div class="form-group mb-15">
                        <label class="form-label">Update Status</label>
                        <select name="new_status" class="form-control">
                            <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed / Resolved</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-light btn-block">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-message { margin-bottom: 20px; max-width: 85%; }
    .chat-message.mine { margin-left: auto; text-align: right; }
    .chat-message.theirs { margin-right: auto; text-align: left; }
    
    .chat-info { font-size: 12px; margin-bottom: 5px; color: #64748b; }
    .chat-text { padding: 15px; border-radius: 16px; background: white; color: #1e293b; line-height: 1.5; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .mine .chat-text { background: #eff6ff; border-color: #bfdbfe; }
</style>

<?php require_once '../includes/footer.php'; ?>
