<?php
require_once '../includes/header.php';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));

    if (empty($name) || empty($email) || empty($phone)) {
        alert('danger', 'Name, Email, and Phone are required.');
    } elseif (!validateMobile($phone)) {
        alert('danger', 'Invalid Mobile Number. Must be 10 digits.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        alert('danger', 'Invalid Email Address.');
    } else {
        // Check if phone or email is already taken by another user
        $checkQuery = "SELECT id FROM users WHERE (phone = '$phone' OR email = '$email') AND id != $uId";
        $checkRes = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkRes) > 0) {
            alert('danger', 'Phone number or email already in use by another account.');
        } else {
            $sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone' WHERE id = $uId";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                alert('success', 'Profile updated successfully!');
                redirect('profile.php');
            } else {
                alert('danger', 'Error updating profile: ' . mysqli_error($conn));
            }
        }
    }
}

// Refresh user data
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
$userData = mysqli_fetch_assoc($userRes);
$userInitials = strtoupper(substr($userData['name'], 0, 1));

// Get parent info if applicable
$parentName = 'System (Direct)';
if ($userData['parent_id']) {
    $parentRes = mysqli_query($conn, "SELECT name FROM users WHERE id = " . (int)$userData['parent_id']);
    $parent = mysqli_fetch_assoc($parentRes);
    $parentName = $parent ? $parent['name'] : 'N/A';
}

// Transaction stats
$txStats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total_tx, SUM(CASE WHEN status='success' THEN amount ELSE 0 END) as total_volume FROM transactions WHERE user_id = $uId"));
?>

<div class="profile-grid">
    <!-- Left Column - Profile Card -->
    <div>
        <div class="card">
            <div class="profile-card-main">
                <div class="profile-avatar-large"><?php echo $userInitials; ?></div>
                <div class="profile-name"><?php echo htmlspecialchars($userData['name']); ?></div>
                <div class="profile-role-badge"><?php echo strtoupper($userData['role']); ?></div>
                
                <div class="profile-meta">
                    <div class="profile-meta-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo $userData['phone']; ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo $userData['email']; ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-wallet"></i>
                        <span style="color: var(--success); font-weight: 700;"><?php echo formatCurrency($userData['wallet_balance']); ?></span>
                    </div>
                    <div class="profile-meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Joined: <?php echo date('d M Y', strtotime($userData['created_at'])); ?></span>
                    </div>
                    <?php if ($role != 'admin'): ?>
                    <div class="profile-meta-item">
                        <i class="fas fa-sitemap"></i>
                        <span>Parent: <?php echo $parentName; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-pie"></i> Quick Stats</h2>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-around; text-align: center;">
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: var(--primary-light);"><?php echo $txStats['total_tx'] ?? 0; ?></div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; margin-top: 4px;">Transactions</div>
                    </div>
                    <div style="width: 1px; background: var(--border);"></div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800; color: var(--success);"><?php echo formatCurrency($txStats['total_volume'] ?? 0); ?></div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; margin-top: 4px;">Volume</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Edit Form -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-user-pen"></i> Edit Profile</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                    </div>
                    
                    <div class="profile-detail-row">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($userData['phone']); ?>" pattern="[0-9]{10}" title="Must be 10 digits" required>
                        </div>
                    </div>

                    <?php if (isset($userData['pan_no'])): ?>
                    <div class="profile-detail-row">
                        <div class="form-group">
                            <label class="form-label">PAN Number</label>
                            <input type="text" class="form-control" value="<?php echo $userData['pan_no'] ?? 'N/A'; ?>" disabled style="opacity: 0.6;">
                            <div class="form-hint">PAN cannot be changed. Contact admin.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Aadhaar Number</label>
                            <input type="text" class="form-control" value="<?php echo $userData['aadhaar_no'] ?? 'N/A'; ?>" disabled style="opacity: 0.6;">
                            <div class="form-hint">Aadhaar cannot be changed. Contact admin.</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="profile-detail-row">
                        <div class="form-group">
                            <label class="form-label">Account Role</label>
                            <input type="text" class="form-control capitalize" value="<?php echo $userData['role']; ?>" disabled style="opacity: 0.6;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Status</label>
                            <input type="text" class="form-control capitalize" value="<?php echo $userData['status']; ?>" disabled style="opacity: 0.6;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 8px;">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="change_password.php" class="btn btn-secondary">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Security Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-shield-halved"></i> Security Information</h2>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="padding: 16px; background: var(--bg-elevated); border-radius: var(--radius); border: 1px solid var(--border);">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Last Login</div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-primary);">
                            <?php echo date('d M Y, h:i A'); ?>
                        </div>
                    </div>
                    <div style="padding: 16px; background: var(--bg-elevated); border-radius: var(--radius); border: 1px solid var(--border);">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Account ID</div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-primary);">
                            #<?php echo str_pad($userData['id'], 6, '0', STR_PAD_LEFT); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
