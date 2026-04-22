<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/api_helper.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Global user data
$uId = $_SESSION['user_id'];
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
$userData = mysqli_fetch_assoc($userRes);

$role = $_SESSION['role'];
$currentPage = basename($_SERVER['PHP_SELF']);

// OTP Verification Check
$otp_mobile = getSetting($conn, 'otp_mobile_enabled', '0');
$otp_email = getSetting($conn, 'otp_email_enabled', '0');

if ($currentPage != 'verify.php' && $role != 'admin') {
    if (($otp_mobile == '1' && !$userData['mobile_verified']) || ($otp_email == '1' && !$userData['email_verified'])) {
        header("Location: " . BASE_URL . "/verify.php");
        exit();
    }
}
$userInitials = strtoupper(substr($userData['name'], 0, 1));
$firstName = explode(' ', $userData['name'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($role); ?> Dashboard - PrifyPay</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/logo.png" type="image/png">
    <meta name="description" content="PrifyPay - Secure Payment Portal for <?php echo ucfirst($role); ?>">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('prifypay_theme') || 'light');</script>
</head>
<body>
    <?php if (isset($_SESSION['admin_user_id'])): ?>
    <div class="admin-impersonate-bar">
        <i class="fas fa-eye"></i>
        Viewing as <strong><?php echo $userData['name']; ?></strong> (<?php echo strtoupper($role); ?>)
        <a href="../admin/return_to_admin.php"><i class="fas fa-arrow-left"></i> Return to Admin</a>
    </div>
    <?php endif; ?>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="PrifyPay" style="height: 40px; width: auto; object-fit: contain;">
                <span class="logo-text" style="display: none;">PrifyPay</span>
            </div>
            
            <nav>
                <div class="nav-section-title">Main</div>
                <a href="index.php" class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                
                <?php if ($role == 'admin'): ?>
                    <div class="nav-section-title">Management</div>
                    <a href="users.php" class="nav-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="commissions.php" class="nav-link <?php echo $currentPage == 'commissions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-percentage"></i>
                        <span>Commissions</span>
                    </a>
                    <a href="kyc.php" class="nav-link <?php echo ($currentPage == 'kyc.php' || $currentPage == 'kyc_requests.php') ? 'active' : ''; ?>">
                        <i class="fas fa-id-card"></i>
                        <span>KYC Requests</span>
                    </a>
                    <a href="tickets.php" class="nav-link <?php echo $currentPage == 'tickets.php' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Support Tickets</span>
                    </a>
                    <a href="settings.php" class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Gateway Settings</span>
                    </a>
                <?php endif; ?>

                <?php if ($role == 'retailer'): ?>
                    <div class="nav-section-title">Transactions</div>
                    <a href="payin.php" class="nav-link <?php echo $currentPage == 'payin.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i>
                        <span>Pay IN (Add Money)</span>
                    </a>
                    <a href="fast_transfer.php" class="nav-link <?php echo $currentPage == 'fast_transfer.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bolt" style="color: #f6c23e;"></i>
                        <span>Fast Transfer (New)</span>
                    </a>
                    <a href="beneficiaries.php" class="nav-link <?php echo $currentPage == 'beneficiaries.php' ? 'active' : ''; ?>">
                        <i class="fas fa-address-book"></i>
                        <span>Beneficiaries</span>
                    </a>
                    <a href="payout.php" class="nav-link <?php echo $currentPage == 'payout.php' ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane"></i>
                        <span>Payout (Cash)</span>
                    </a>
                <?php endif; ?>

                <div class="nav-section-title">Analytics</div>
                <a href="reports.php" class="nav-link <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>

                <div class="nav-section-title">Account</div>
                <a href="profile.php" class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
                <a href="kyc.php" class="nav-link <?php echo $currentPage == 'kyc.php' ? 'active' : ''; ?>">
                    <i class="fas fa-id-card"></i>
                    <span>KYC Verification</span>
                </a>
                <a href="change_password.php" class="nav-link <?php echo $currentPage == 'change_password.php' ? 'active' : ''; ?>">
                    <i class="fas fa-lock"></i>
                    <span>Change Password</span>
                </a>

                <div class="nav-section-title">Support</div>
                <a href="support.php" class="nav-link <?php echo $currentPage == 'support.php' ? 'active' : ''; ?>">
                    <i class="fas fa-headset"></i>
                    <span>Help & Support</span>
                </a>

                <a href="../logout.php" class="nav-link nav-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="tagline">Safe | Secure | Seamless</div>
                <div class="version">v3.0.2</div>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        Hi, <?php echo $firstName; ?>
                        <span class="role-tag"><?php echo strtoupper($role); ?></span>
                    </div>
                </div>
                <div class="top-bar-right">
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme" title="Switch Light/Dark">
                        <i class="fas fa-moon"></i>
                        <i class="fas fa-sun"></i>
                    </button>

                    <div class="wallet-badge" title="Main Wallet Balance">
                        <div>
                            <div class="wallet-label">Main Wallet</div>
                            <div class="wallet-amount"><?php echo formatCurrency($userData['wallet_balance']); ?></div>
                        </div>
                    </div>

                    <div class="wallet-badge" style="background: rgba(99, 102, 241, 0.1); color: var(--primary); border-color: rgba(99, 102, 241, 0.2);" title="Earnings from Commissions">
                        <div>
                            <div class="wallet-label" style="color: var(--primary);">Earnings</div>
                            <div class="wallet-amount" style="color: var(--primary);"><?php echo formatCurrency($userData['earnings_balance']); ?></div>
                        </div>
                    </div>

                    <?php if ($role == 'admin'): 
                        $apiBal = getApiBalance();
                    ?>
                    <div class="wallet-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.2);" title="Gateway Available Limit">
                        <div>
                            <div class="wallet-label" style="color: #059669;">Gateway</div>
                            <div class="wallet-amount" style="color: #059669;"><?php echo formatCurrency($apiBal); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="profile-dropdown-wrapper" id="profileDropdown">
                        <div class="profile-trigger" onclick="document.getElementById('profileDropdown').classList.toggle('open')">
                            <div class="avatar"><?php echo $userInitials; ?></div>
                            <span class="user-name-topbar"><?php echo $firstName; ?></span>
                            <i class="fas fa-chevron-down chevron"></i>
                        </div>
                        <div class="profile-dropdown">
                            <div class="dropdown-header">
                                <div class="dd-name"><?php echo $userData['name']; ?></div>
                                <div class="dd-email"><?php echo $userData['email']; ?></div>
                            </div>
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <a href="change_password.php" class="dropdown-item">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                            <a href="../logout.php" class="dropdown-item danger">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="page-content">
                <?php displayAlert(); ?>
