<?php
require_once '../includes/header.php';
checkRole(['admin', 'dev']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        alert('danger', 'All fields are required.');
    } elseif (strlen($new) < 6) {
        alert('danger', 'New password must be at least 6 characters.');
    } elseif ($new !== $confirm) {
        alert('danger', 'New password and confirmation do not match.');
    } elseif (!password_verify($current, $userData['password'])) {
        alert('danger', 'Current password is incorrect.');
    } elseif ($current === $new) {
        alert('danger', 'New password must be different from current password.');
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = '$hashed' WHERE id = $uId";
        if (mysqli_query($conn, $sql)) {
            alert('success', 'Password changed successfully! Please use your new password next time you login.');
            redirect('change_password.php');
        } else {
            alert('danger', 'Error updating password. Please try again.');
        }
    }
}
?>

<div style="max-width: 560px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-lock"></i> Change Password</h2>
        </div>
        <div class="card-body">
            <div style="padding: 16px; background: var(--info-light); border: 1px solid var(--info-border); border-radius: var(--radius); margin-bottom: 24px; display: flex; align-items: flex-start; gap: 12px;">
                <i class="fas fa-info-circle" style="color: var(--info); margin-top: 2px; flex-shrink: 0;"></i>
                <div style="font-size: 13px; color: var(--info); line-height: 1.6;">
                    Choose a strong password with at least 6 characters. Use a mix of letters, numbers, and special characters for better security.
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" id="currentPass" class="form-control" placeholder="Enter current password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('currentPass', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Enter new password (min 6 chars)" minlength="6" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('newPass', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="strengthIndicator" style="margin-top: 8px; display: none;">
                        <div style="display: flex; gap: 4px; margin-bottom: 4px;">
                            <div id="str1" style="flex: 1; height: 3px; border-radius: 2px; background: var(--border);"></div>
                            <div id="str2" style="flex: 1; height: 3px; border-radius: 2px; background: var(--border);"></div>
                            <div id="str3" style="flex: 1; height: 3px; border-radius: 2px; background: var(--border);"></div>
                            <div id="str4" style="flex: 1; height: 3px; border-radius: 2px; background: var(--border);"></div>
                        </div>
                        <div id="strText" style="font-size: 11px; color: var(--text-muted);"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirmPass" class="form-control" placeholder="Re-enter new password" minlength="6" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPass', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="matchIndicator" style="font-size: 11px; margin-top: 4px; display: none;"></div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-check"></i> Update Password
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Tips -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-shield-halved"></i> Security Tips</h2>
        </div>
        <div class="card-body">
            <ul style="padding-left: 18px; font-size: 13px; color: var(--text-secondary); line-height: 2;">
                <li>Never share your password with anyone</li>
                <li>Use a unique password not used on other sites</li>
                <li>Include uppercase, lowercase, numbers & symbols</li>
                <li>Change your password periodically</li>
                <li>Avoid using personal information as password</li>
            </ul>
        </div>
    </div>
</div>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Password strength indicator
    document.getElementById('newPass').addEventListener('input', function() {
        const val = this.value;
        const indicator = document.getElementById('strengthIndicator');
        const bars = [document.getElementById('str1'), document.getElementById('str2'), document.getElementById('str3'), document.getElementById('str4')];
        const text = document.getElementById('strText');
        
        indicator.style.display = val.length > 0 ? 'block' : 'none';
        
        let strength = 0;
        if (val.length >= 6) strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
        if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) strength++;
        
        const colors = ['var(--danger)', 'var(--warning)', 'var(--info)', 'var(--success)'];
        const labels = ['Weak', 'Fair', 'Good', 'Strong'];
        
        bars.forEach((bar, i) => {
            bar.style.background = i < strength ? colors[strength - 1] : 'var(--border)';
        });
        
        text.textContent = strength > 0 ? labels[strength - 1] : '';
        text.style.color = strength > 0 ? colors[strength - 1] : 'var(--text-muted)';
    });

    // Password match indicator
    document.getElementById('confirmPass').addEventListener('input', function() {
        const match = document.getElementById('matchIndicator');
        const newPass = document.getElementById('newPass').value;
        match.style.display = this.value.length > 0 ? 'block' : 'none';
        
        if (this.value === newPass) {
            match.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            match.style.color = 'var(--success)';
        } else {
            match.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
            match.style.color = 'var(--danger)';
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
