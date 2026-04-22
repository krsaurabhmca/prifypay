<?php
require_once '../includes/header.php';

$tId = (int)$_GET['id'];
$uId = $_SESSION['user_id'];

// Verify ownership
$ticketRes = mysqli_query($conn, "SELECT * FROM support_tickets WHERE id = $tId AND user_id = $uId");
$ticket = mysqli_fetch_assoc($ticketRes);

if (!$ticket) {
    echo "Invalid Ticket ID.";
    exit;
}

// Post Message
if (isset($_POST['send_reply'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['message']);
    mysqli_query($conn, "INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES ($tId, $uId, '$msg')");
    mysqli_query($conn, "UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = $tId");
    alert('success', 'Reply sent.');
}

$messages = mysqli_query($conn, "SELECT tm.*, u.name, u.role FROM ticket_messages tm JOIN users u ON tm.user_id = u.id WHERE tm.ticket_id = $tId ORDER BY tm.created_at ASC");
?>

<div class="max-w-900 mx-auto">
    <div class="card mb-20 animate-in">
        <div class="card-header" style="background: var(--bg-elevated);">
            <div>
                <h2 class="card-title">#TK-<?php echo $tId; ?>: <?php echo $ticket['subject']; ?></h2>
                <div class="mt-5">
                    <span class="badge badge-info"><?php echo strtoupper($ticket['status']); ?></span>
                    <span class="text-muted ml-10">Category: <?php echo $ticket['category']; ?></span>
                    <span class="text-muted ml-10">Priority: <?php echo ucfirst($ticket['priority']); ?></span>
                </div>
            </div>
            <a href="tickets.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <div class="ticket-chat-body" style="padding: 20px; max-height: 500px; overflow-y: auto;">
            <?php while ($m = mysqli_fetch_assoc($messages)): ?>
                <div class="chat-message <?php echo $m['user_id'] == $uId ? 'mine' : 'theirs'; ?>">
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
            <?php if ($ticket['status'] != 'closed'): ?>
            <form method="POST">
                <div class="form-group">
                    <textarea name="message" class="form-control" rows="3" placeholder="Type your reply here..." required></textarea>
                </div>
                <div class="mt-15 d-flex justify-content-end">
                    <button type="submit" name="send_reply" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">This ticket is closed.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .chat-message { margin-bottom: 20px; max-width: 80%; }
    .chat-message.mine { margin-left: auto; text-align: right; }
    .chat-message.theirs { margin-right: auto; text-align: left; }
    
    .chat-info { font-size: 12px; margin-bottom: 5px; color: var(--text-muted); }
    .chat-text { padding: 15px; border-radius: 16px; background: var(--bg-elevated); color: var(--text-primary); line-height: 1.5; border: 1px solid var(--border); }
    .mine .chat-text { background: rgba(79, 70, 229, 0.05); border-color: var(--primary-light); }
</style>

<?php require_once '../includes/footer.php'; ?>
