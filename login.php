<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] . "/index.php");
}

$login_otp = getSetting($conn, 'login_otp_enabled', '0');
$login_captcha = getSetting($conn, 'login_captcha_enabled', '0');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // Verify Captcha
    if ($login_captcha == '1') {
        if (!isset($_POST['captcha']) || $_POST['captcha'] != $_SESSION['captcha_code']) {
            alert('danger', 'Invalid captcha code.');
            redirect('login.php');
        }
    }

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
            
            // Handle Login OTP
            if ($login_otp == '1' && $user['role'] != 'admin') {
                // Reset verification status for this session
                mysqli_query($conn, "UPDATE users SET mobile_verified = 0, email_verified = 0 WHERE id = " . $user['id']);
                redirect("verify.php");
            } else {
                redirect($user['role'] . "/index.php");
            }
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
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('prifypay_theme') || 'light');</script>
    <style>
        :root {
            --auth-bg: #f8fafc;
            --auth-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        [data-theme="dark"] {
            --auth-bg: #0f172a;
        }

        body.auth-container {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--auth-bg);
            font-family: 'Outfit', sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        .auth-decoration-1 { position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; border-radius: 50%; background: var(--auth-gradient); opacity: 0.05; filter: blur(60px); z-index: 0; }
        .auth-decoration-2 { position: absolute; bottom: -100px; left: -100px; width: 300px; height: 300px; border-radius: 50%; background: #06b6d4; opacity: 0.05; filter: blur(50px); z-index: 0; }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            padding: 48px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .auth-header { text-align: center; margin-bottom: 40px; }
        .auth-logo { margin-bottom: 24px; }
        .auth-logo img { width: 140px; height: auto; transition: transform 0.3s ease; }
        .auth-logo:hover img { transform: scale(1.05); }

        .auth-header h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 0; letter-spacing: -0.5px; }
        .auth-header p { font-size: 15px; color: var(--text-muted); margin: 8px 0 0; }

        .form-group { margin-bottom: 24px; }
        .form-label { font-weight: 600; font-size: 14px; color: var(--text-secondary); margin-bottom: 8px; display: block; }
        
        .input-group {
            position: relative;
            background: var(--bg-elevated);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            transition: all 0.2s ease;
        }
        .input-group:focus-within { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 16px; }
        .input-group .form-control {
            background: transparent;
            border: none;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            width: 100%;
            height: auto;
            color: var(--text-primary);
        }
        .input-group .form-control:focus { outline: none; box-shadow: none; }

        .captcha-box {
            display: flex;
            gap: 12px;
            align-items: center;
            background: var(--bg-elevated);
            padding: 8px;
            border-radius: 16px;
            border: 1.5px solid var(--border);
        }
        .captcha-img { height: 44px; border-radius: 10px; border: 1px solid var(--border); }
        .captcha-input { flex: 1; border: none; background: transparent; padding: 10px; font-weight: 700; text-align: center; font-size: 16px; color: var(--text-primary); }
        .captcha-input:focus { outline: none; }

        .btn-login {
            background: var(--auth-gradient);
            color: white;
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
            margin-top: 10px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4); opacity: 0.95; }
        .btn-login:active { transform: translateY(0); }

        .auth-links {
            margin-top: 40px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
            border-top: 1px solid var(--border);
            padding-top: 24px;
        }
        .auth-links a { font-size: 12px; color: var(--text-muted); text-decoration: none; transition: color 0.2s; font-weight: 500; }
        .auth-links a:hover { color: var(--primary); }
        
        .auth-copyright { text-align: center; margin-top: 24px; font-size: 12px; color: var(--text-muted); }
        
        .password-toggle { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0; }
        
        .animate-in { animation: authFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes authFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="auth-container">
    <div class="auth-decoration-1"></div>
    <div class="auth-decoration-2"></div>

    <div style="position: fixed; top: 30px; right: 30px; z-index: 10;">
        <button class="theme-toggle" id="themeToggle" style="width: 48px; height: 48px; border-radius: 16px; background: var(--bg-card); border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <i class="fas fa-moon"></i>
            <i class="fas fa-sun"></i>
        </button>
    </div>

    <div class="auth-card animate-in">
        <div class="auth-header">
            <div class="auth-logo">
                <img src="assets/images/logo.png" alt="PrifyPay Logo">
            </div>
            <h1>Welcome Back</h1>
            <p>Access your secure dashboard</p>
        </div>
        
        <?php displayAlert(); ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <i class="fas fa-phone-alt"></i>
                    <input type="text" name="phone" class="form-control" placeholder="10-digit number" pattern="[0-9]{10}" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <i class="fas fa-shield-alt"></i>
                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('loginPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <?php if ($login_captcha == '1'): ?>
            <div class="form-group">
                <label class="form-label">Security Check</label>
                <div class="captcha-box">
                    <img src="captcha.php" alt="captcha" class="captcha-img" id="captchaImg">
                    <input type="text" name="captcha" class="captcha-input" placeholder="Enter Code" required maxlength="4">
                    <button type="button" onclick="document.getElementById('captchaImg').src='captcha.php?'+Math.random()" class="btn btn-sm btn-light" style="width: 36px; height: 36px; border-radius: 10px; padding: 0;">
                        <i class="fas fa-sync-alt" style="font-size: 12px;"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" name="login" class="btn-login">
                <span>Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="auth-links">
            <a href="https://prifypay.com/privacy-policy/" target="_blank">Privacy Policy</a>
            <a href="https://prifypay.com/terms-conditions/" target="_blank">Terms & Conditions</a>
            <a href="https://prifypay.com/refund-policy/" target="_blank">Refund Policy</a>
            <a href="https://prifypay.com/disclaimer/" target="_blank">Disclaimer</a>
        </div>

        <div class="auth-copyright">
            <i class="fas fa-shield-check" style="color: var(--success); margin-right: 4px;"></i>
            © <?php echo date('Y'); ?> PrifyPay Secure Services
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
