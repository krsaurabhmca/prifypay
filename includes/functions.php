<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header("Location: ../login.php");
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function formatCurrency($amount) {
    return "₹" . number_format($amount, 2);
}

function getCommissionValue($conn, $role, $type) {
    $role = mysqli_real_escape_string($conn, $role);
    $type = mysqli_real_escape_string($conn, $type);
    $query = "SELECT * FROM commissions WHERE role = '$role' AND transaction_type = '$type' LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function calculateCommission($amount, $commSetting) {
    if (!$commSetting) return 0;
    if ($commSetting['method'] == 'percentage') {
        return ($amount * $commSetting['value']) / 100;
    } else {
        return $commSetting['value'];
    }
}

function updateWallet($conn, $userId, $amount, $type = 'add') {
    $amount = (float)$amount;
    $operator = ($type == 'add') ? '+' : '-';
    $query = "UPDATE users SET wallet_balance = wallet_balance $operator $amount WHERE id = $userId";
    return mysqli_query($conn, $query);
}

function logTransaction($conn, $userId, $type, $amount, $fee, $commDist, $commAdmin, $commRetailer, $status, $refId, $utr = '', $url = '', $response = '', $payoutBeneId = null, $payoutAmount = null) {
    $userId = (int)$userId;
    $amount = (float)$amount;
    $fee = (float)$fee;
    $commDist = (float)$commDist;
    $commAdmin = (float)$commAdmin;
    $commRetailer = (float)$commRetailer;
    $refId = mysqli_real_escape_string($conn, $refId);
    $utr = mysqli_real_escape_string($conn, $utr);
    $url = mysqli_real_escape_string($conn, $url);
    $response = mysqli_real_escape_string($conn, $response);
    $payoutBeneId = $payoutBeneId ? (int)$payoutBeneId : "NULL";
    $payoutAmount = $payoutAmount ? (float)$payoutAmount : "NULL";

    $sql = "INSERT INTO transactions 
    (user_id, type, amount, fee, commission_distributor, commission_admin, commission_retailer, status, reference_id, utr, payment_url, api_response, payout_bene_id, payout_amount) 
    VALUES 
    ($userId, '$type', $amount, $fee, $commDist, $commAdmin, $commRetailer, '$status', '$refId', '$utr', '$url', '$response', $payoutBeneId, $payoutAmount)";
    
    return mysqli_query($conn, $sql);
}

function alert($type, $msg) {
    $_SESSION['alert'] = ['type' => $type, 'msg' => $msg];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $type = $_SESSION['alert']['type'];
        $msg = $_SESSION['alert']['msg'];
        unset($_SESSION['alert']);
        $icon = ($type == 'success') ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
        $class = ($type == 'success') ? 'alert-success' : 'alert-danger';
        echo "<div class='alert $class'>$icon $msg</div>";
    }
}

// Validation Helpers
function validateMobile($mobile) {
    return preg_match('/^[0-9]{10}$/', $mobile);
}

function validatePAN($pan) {
    return preg_match('/^([A-Z]){5}([0-9]){4}([A-Z]){1}$/', strtoupper($pan));
}

function validateAadhaar($aadhaar) {
    // Basic 12 digit check (Verhoeff algorithm is complex, sticking to basic for now)
    $clean = str_replace(' ', '', $aadhaar);
    return preg_match('/^[0-9]{12}$/', $clean);
}
function updateEarningsWallet($conn, $userId, $amount, $type = 'add') {
    $amount = (float)$amount;
    $operator = ($type == 'add') ? '+' : '-';
    $query = "UPDATE users SET earnings_balance = earnings_balance $operator $amount WHERE id = $userId";
    return mysqli_query($conn, $query);
}

function getKycStatusLabel($status) {
    switch ($status) {
        case 'verified': return '<span class="badge badge-success">VERIFIED</span>';
        case 'pending': return '<span class="badge badge-warning">PENDING</span>';
        case 'rejected': return '<span class="badge badge-danger">REJECTED</span>';
        default: return '<span class="badge badge-secondary">NOT SUBMITTED</span>';
    }
}

function getSetting($conn, $key, $default = '') {
    $res = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = '$key'");
    if ($row = mysqli_fetch_assoc($res)) {
        return $row['setting_value'];
    }
    return $default;
}
?>
