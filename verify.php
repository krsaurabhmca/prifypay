<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/api_helper.php';
require_once 'includes/mail_helper.php';

session_start();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$uId = $_SESSION['user_id'];
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
$userData = mysqli_fetch_assoc($userRes);

// Settings
$otp_mobile = getSetting($conn, 'otp_mobile_enabled', '0');
$otp_email = getSetting($conn, 'otp_email_enabled', '0');

if (isset($_POST['send_otp'])) {
    $otp = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    mysqli_query($conn, "UPDATE users SET otp_code = '$otp', otp_expiry = '$expiry' WHERE id = $uId");

    if ($otp_mobile == '1' && !$userData['mobile_verified']) {
        sendOtpSms($userData['phone'], $otp, "VERIFY_" . $uId);
    }
    
    if ($otp_email == '1' && !$userData['email_verified']) {
        $msg = "<h2>Account Verification</h2><p>Your OTP for PrifyPay is: <strong>$otp</strong></p><p>Valid for 10 minutes.</p>";
        sendEmail($userData['email'], "PrifyPay Verification OTP", $msg);
    }
    
    $success = "OTP sent successfully!";
}

if (isset($_POST['verify_otp'])) {
    $inputOtp = $_POST['otp'];
    $now = date('Y-m-d H:i:s');
    
    if ($inputOtp == $userData['otp_code'] && $now <= $userData['otp_expiry']) {
        mysqli_query($conn, "UPDATE users SET mobile_verified = 1, email_verified = 1, otp_code = NULL WHERE id = $uId");
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid or expired OTP.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account | PrifyPay</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light" style="font-family: 'Outfit', sans-serif;">
    <div class="verify-page">
        <div class="verify-card animate-in">
            <div class="verify-header">
                <img src="assets/images/logo.png" alt="PrifyPay" style="height: 60px; margin-bottom: 15px; filter: brightness(0) invert(1);">
                <h1>Identity Verification</h1>
                <p>Security check to protect your account</p>
            </div>

            <div class="verify-body">
                <?php if(isset($success)): ?>
                    <div class="alert alert-success" style="border-radius: 12px;"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger" style="border-radius: 12px;"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <div class="verify-steps">
                    <?php if ($otp_mobile == '1'): ?>
                    <div class="step-item <?php echo $userData['mobile_verified'] ? 'completed' : ''; ?>">
                        <div class="step-icon"><i class="fas fa-mobile-alt"></i></div>
                        <div class="step-text">
                            <h6>Phone SMS OTP</h6>
                            <p><?php echo substr($userData['phone'], 0, 2) . '******' . substr($userData['phone'], -2); ?></p>
                        </div>
                        <div class="step-status">
                            <?php if($userData['mobile_verified']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <span class="badge badge-warning">Waiting</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($otp_email == '1'): ?>
                    <div class="step-item <?php echo $userData['email_verified'] ? 'completed' : ''; ?>">
                        <div class="step-icon"><i class="fas fa-envelope"></i></div>
                        <div class="step-text">
                            <h6>Registered Email OTP</h6>
                            <p><?php echo substr($userData['email'], 0, 3) . '***@' . explode('@', $userData['email'])[1]; ?></p>
                        </div>
                        <div class="step-status">
                            <?php if($userData['email_verified']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <span class="badge badge-warning">Waiting</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <form method="POST" class="mt-30">
                    <?php if (empty($userData['otp_code'])): ?>
                        <button type="submit" name="send_otp" class="btn btn-primary btn-block h-60" style="border-radius: 16px; font-weight: 700; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                            <i class="fas fa-paper-plane"></i> Send Verification Code
                        </button>
                    <?php else: ?>
                        <div class="form-group">
                            <label class="form-label text-center" style="font-weight: 600;">Enter 6-Digit Code</label>
                            <input type="text" name="otp" class="form-control otp-input" maxlength="6" placeholder="000000" required autofocus style="border-radius: 16px; border: 2px solid var(--border);">
                        </div>
                        <button type="submit" name="verify_otp" class="btn btn-success btn-block h-60 mt-20" style="border-radius: 16px; font-weight: 700; background: #10b981; border: none;">
                            <i class="fas fa-shield-check"></i> Verify & Continue
                        </button>
                        <div class="text-center mt-20">
                            <button type="submit" name="send_otp" class="btn btn-link text-secondary" style="font-size: 14px; text-decoration: none; font-weight: 600;">Didn't receive code? Resend</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="verify-footer" style="background: #f8fafc; border-top: 1px solid #edf2f7; padding: 20px;">
                <a href="logout.php" style="color: #64748b; text-decoration: none; font-weight: 600; font-size: 13px;"><i class="fas fa-sign-out-alt"></i> Logout and switch account</a>
            </div>
        </div>
    </div>

    <style>
        .verify-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: #f1f5f9; }
        .verify-card { background: white; width: 100%; max-width: 480px; border-radius: 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .verify-header { padding: 48px 48px 32px; text-align: center; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; }
        .verify-header h1 { font-size: 24px; font-weight: 800; margin: 0 0 8px; letter-spacing: -0.5px; }
        .verify-header p { font-size: 14px; opacity: 0.9; margin: 0; }
        
        .verify-body { padding: 40px; }
        .verify-steps { display: flex; flex-direction: column; gap: 16px; }
        .step-item { display: flex; align-items: center; gap: 16px; padding: 16px; border: 1.5px solid #f1f5f9; border-radius: 20px; transition: all 0.2s; }
        .step-item.completed { background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.1); }
        .step-icon { width: 44px; height: 44px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 18px; }
        .completed .step-icon { background: #10b981; color: white; }
        .step-text h6 { font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; }
        .step-text p { font-size: 13px; color: #94a3b8; margin: 2px 0 0; }
        .step-status { margin-left: auto; }
        
        .otp-input { height: 72px; text-align: center; font-size: 32px; font-weight: 800; letter-spacing: 12px; transition: all 0.2s; }
        .otp-input:focus { border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        
        .h-60 { height: 60px !important; }
        .animate-in { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</body>
</html>
