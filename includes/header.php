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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>document.documentElement.setAttribute('data-theme', localStorage.getItem('prifypay_theme') || 'light');</script>
</head>

<body>
    <?php if (isset($_SESSION['admin_user_id'])): ?>
        <div
            style="background: #ef4444; color: white; padding: 10px; text-align: center; position: sticky; top: 0; z-index: 2000; font-weight: 700; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);">
            <i class="fas fa-user-secret" style="margin-right: 10px;"></i>
            You are logged in as <?php echo $_SESSION['name']; ?>.
            <a href="return_to_admin.php" style="color: white; text-decoration: underline; margin-left: 15px;">
                <i class="fas fa-undo"></i> Return to
                <?php echo ($_SESSION['admin_role'] == 'dev') ? 'Developer' : 'Admin'; ?> Console
            </a>
        </div>
    <?php endif; ?>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" class="logo-light" alt="PrifyPay">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo-dark.png" class="logo-dark" alt="PrifyPay">
                <span class="logo-text">PrifyPay</span>
            </div>

            <nav>
                <div class="nav-section-title">Main</div>
                <a href="index.php" class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <?php if ($role == 'admin' || $role == 'dev'): ?>
                    <div class="nav-section-title">System Management</div>
                    <a href="users.php" class="nav-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>User Directory</span>
                    </a>
                    <a href="kyc.php" class="nav-link <?php echo $currentPage == 'kyc.php' ? 'active' : ''; ?>">
                        <i class="fas fa-id-card-clip"></i>
                        <span>KYC Requests</span>
                    </a>
                    <a href="tickets.php" class="nav-link <?php echo $currentPage == 'tickets.php' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Support Tickets</span>
                    </a>
                    <a href="commissions.php"
                        class="nav-link <?php echo $currentPage == 'commissions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-percentage"></i>
                        <span>Comm. Settings</span>
                    </a>
                    <?php if ($role == 'dev'): ?>
                        <a href="settings.php" class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-gears"></i>
                            <span>System Config</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($role == 'distributor'): ?>
                    <div class="nav-section-title">Distribution</div>
                    <a href="retailers.php" class="nav-link <?php echo $currentPage == 'retailers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i>
                        <span>My Retailers</span>
                    </a>
                    <a href="fund_transfer.php"
                        class="nav-link <?php echo $currentPage == 'fund_transfer.php' ? 'active' : ''; ?>">
                        <i class="fas fa-money-bill-transfer"></i>
                        <span>Fund Transfer</span>
                    </a>
                <?php endif; ?>

                <?php if ($role == 'retailer'): ?>
                    <div class="nav-section-title">Services</div>
                    <a href="fast_transfer.php"
                        class="nav-link <?php echo $currentPage == 'fast_transfer.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bolt" style="color: #fbbf24;"></i>
                        <span>Fast Transfer</span>
                    </a>
                    <a href="payin.php" class="nav-link <?php echo $currentPage == 'payin.php' ? 'active' : ''; ?>">
                        <i class="fas fa-wallet"></i>
                        <span>Add Money</span>
                    </a>
                    <a href="payout.php" class="nav-link <?php echo $currentPage == 'payout.php' ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane"></i>
                        <span>Payout (DMT)</span>
                    </a>
                    <a href="beneficiaries.php"
                        class="nav-link <?php echo $currentPage == 'beneficiaries.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users-gear"></i>
                        <span>Beneficiaries</span>
                    </a>
                <?php endif; ?>

                <div class="nav-section-title">Reports</div>
                <a href="reports.php" class="nav-link <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Transactions</span>
                </a>

                <div class="nav-section-title">Personal</div>
                <a href="profile.php" class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
                <?php if ($role != 'admin'): ?>
                    <a href="kyc.php" class="nav-link <?php echo $currentPage == 'kyc.php' ? 'active' : ''; ?>">
                        <i class="fas fa-id-card"></i>
                        <span>KYC Status</span>
                    </a>
                <?php endif; ?>
                <a href="change_password.php"
                    class="nav-link <?php echo $currentPage == 'change_password.php' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i>
                    <span>Security</span>
                </a>

                <div class="nav-section-title">Help</div>
                <a href="support.php" class="nav-link <?php echo $currentPage == 'support.php' ? 'active' : ''; ?>">
                    <i class="fas fa-headset"></i>
                    <span>Support Center</span>
                </a>

                <a href="tickets.php" class="nav-link <?php echo $currentPage == 'tickets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket"></i>
                    <span>My Tickets</span>
                </a>

                <a href="../logout.php" class="nav-link nav-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="footer-trust">
                    <i class="fas fa-shield-halved"></i>
                    <span>Safe | Secure | Seamless</span>
                </div>
                <div class="footer-meta">
                    <span class="version-tag">Build v1.0.2</span>
                    <span class="status-indicator"></span>
                </div>
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

                    <div class="wallet-badge"
                        style="background: rgba(99, 102, 241, 0.1); color: var(--primary); border-color: rgba(99, 102, 241, 0.2);"
                        title="Earnings from Commissions">
                        <div>
                            <div class="wallet-label" style="color: var(--primary);">Earnings</div>
                            <div class="wallet-amount" style="color: var(--primary);">
                                <?php echo formatCurrency($userData['earnings_balance']); ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($role == 'admin'):
                        $apiBal = getApiBalance();
                        ?>
                        <div class="wallet-badge"
                            style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.2);"
                            title="Gateway Available Limit">
                            <div>
                                <div class="wallet-label" style="color: #059669;">Gateway</div>
                                <div class="wallet-amount" style="color: #059669;"><?php echo formatCurrency($apiBal); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="profile-dropdown-wrapper" id="profileDropdown">
                        <div class="profile-trigger"
                            onclick="document.getElementById('profileDropdown').classList.toggle('open')">
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