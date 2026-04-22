<?php
require_once '../includes/header.php';

$uId = $_SESSION['user_id'];

// Create Ticket
if (isset($_POST['create_ticket'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $sql = "INSERT INTO support_tickets (user_id, subject, category, priority, status) VALUES ($uId, '$subject', '$category', '$priority', 'open')";
    if (mysqli_query($conn, $sql)) {
        $ticketId = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES ($ticketId, $uId, '$message')");
        alert('success', 'Ticket created successfully! We will get back to you soon.');
    } else {
        alert('danger', 'Error creating ticket.');
    }
}

$tickets = mysqli_query($conn, "SELECT * FROM support_tickets WHERE user_id = $uId ORDER BY created_at DESC");
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card animate-in">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-plus-circle"></i> New Support Ticket</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group mb-20">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="Transaction Issue">Transaction Issue</option>
                            <option value="Wallet/Balance">Wallet/Balance</option>
                            <option value="KYC Issue">KYC Issue</option>
                            <option value="Technical Error">Technical Error</option>
                            <option value="General Query">General Query</option>
                        </select>
                    </div>
                    <div class="form-group mb-20">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group mb-20">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Brief summary of the issue" required>
                    </div>
                    <div class="form-group mb-20">
                        <label class="form-label">Detailed Message</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Describe your problem in detail..." required></textarea>
                    </div>
                    <button type="submit" name="create_ticket" class="btn btn-primary btn-block h-50">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card animate-in" style="animation-delay: 0.1s;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list-ul"></i> Your Tickets</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Last Update</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = mysqli_fetch_assoc($tickets)): ?>
                        <tr>
                            <td>#TK-<?php echo $t['id']; ?></td>
                            <td><strong><?php echo $t['subject']; ?></strong></td>
                            <td><span class="badge badge-light"><?php echo $t['category']; ?></span></td>
                            <td><span class="capitalize"><?php echo $t['priority']; ?></span></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $t['status'] == 'open' ? 'info' : ($t['status'] == 'closed' ? 'success' : 'warning'); 
                                ?>">
                                    <?php echo strtoupper($t['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M, H:i', strtotime($t['updated_at'])); ?></td>
                            <td>
                                <a href="ticket_view.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-light">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($tickets) == 0): ?>
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-ticket-alt"></i><p>No tickets found.</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
