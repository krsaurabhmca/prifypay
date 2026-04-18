<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] . "/index.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE phone = '$phone' AND status = 'active'";
    $result = mysqli_query($conn, $query);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            redirect($user['role'] . "/index.php");
        } else {
            alert('danger', 'Invalid password.');
        }
    } else {
        alert('danger', 'Account not found or inactive.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PrifyPay</title>
    <meta name="description" content="PrifyPay - Secure Multi-Level Payment Portal Login">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('prifypay_theme') || 'light');</script>
</head>
<body class="auth-container">
    <div style="position: fixed; top: 20px; right: 20px; z-index: 10;">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme" title="Switch Light/Dark">
            <i class="fas fa-moon"></i>
            <i class="fas fa-sun"></i>
        </button>
    </div>
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-bolt"></i>
            </div>
            <h1>PrifyPay</h1>
            <p>Secure Portal Login</p>
        </div>
        
        <?php displayAlert(); ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label">Registered Phone</label>
                <div style="position: relative;">
                    <input type="text" name="phone" class="form-control" placeholder="10-digit mobile number" pattern="[0-9]{10}" required style="padding-left: 40px;">
                    <i class="fas fa-phone" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px;"></i>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Account Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required style="padding-left: 40px;">
                    <i class="fas fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px;"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('loginPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary btn-block" style="margin-top: 8px; padding: 12px;">
                <i class="fas fa-right-to-bracket"></i>
                <span>Sign In to Dashboard</span>
            </button>
        </form>

        <div class="auth-footer">
            <div style="display: flex; align-items: center; gap: 8px; justify-content: center; margin-bottom: 8px;">
                <i class="fas fa-shield-halved" style="color: var(--success); font-size: 12px;"></i>
                <span>256-bit encrypted connection</span>
            </div>
            <span style="color: var(--text-muted);">© <?php echo date('Y'); ?> PrifyPay. All rights reserved.</span>
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

        document.getElementById('themeToggle').addEventListener('click', function() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('prifypay_theme', next);
        });
    </script>
</body>
</html>
