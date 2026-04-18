<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/api_helper.php';

// Set response to JSON
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'retailer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$uId = $_SESSION['user_id'];

// Get current user data for balance check
$uQuery = mysqli_query($conn, "SELECT * FROM users WHERE id = $uId");
$userData = mysqli_fetch_assoc($uQuery);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (int)$_POST['amount'];
    $bene_id = (int)$_POST['bene_id'];
    
    if ($amount < 1) {
        echo json_encode(['success' => false, 'message' => 'Minimum amount is ₹1.']);
        exit();
    }

    // Get commissions
    $retComm = getCommissionValue($conn, 'retailer', 'payout');
    $distComm = getCommissionValue($conn, 'distributor', 'payout');
    
    $retailerFee = calculateCommission($amount, $retComm);
    $distributorPart = calculateCommission($amount, $distComm);
    
    $totalDeduction = $amount + $retailerFee;

    if ($userData['wallet_balance'] < $totalDeduction) {
        echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance. Total required: ' . formatCurrency($totalDeduction)]);
        exit();
    }

    $beneQuery = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE id = $bene_id AND user_id = $uId AND status = 'verified'");
    $bene = mysqli_fetch_assoc($beneQuery);

    if (!$bene) {
        echo json_encode(['success' => false, 'message' => 'Invalid or unverified beneficiary selected.']);
        exit();
    }

    $refId = "PAYOUT_" . time() . "_" . $uId;
    $callback = BASE_URL . "/callbacks/payout.php";
    
    $res = createPayout($amount, $bene['account_number'], $bene['ifsc'], $bene['bank_name'], $bene['name'], $callback, $refId);

    if ($res['success']) {
        // 1. Deduct from Retailer
        updateWallet($conn, $uId, $totalDeduction, 'sub');
        
        // 2. Add commission to Distributor (if exists)
        if ($userData['parent_id']) {
            updateWallet($conn, $userData['parent_id'], $distributorPart, 'add');
            // Log distributor commission
            logTransaction($conn, $userData['parent_id'], 'commission', $distributorPart, 0, 0, 0, $distributorPart, 'success', 'COMM_'.$refId);
        }

        // 3. Log Retailer Payout
        $utr = $res['data']['utr'] ?? $res['data']['transaction_id'] ?? '';
        logTransaction($conn, $uId, 'payout', $amount, $retailerFee, $distributorPart, ($retailerFee - $distributorPart), $retailerFee, 'success', $refId, $utr, '', $res['raw']);
        
        echo json_encode(['success' => true, 'message' => 'Payout completed successfully!', 'utr' => $utr]);
    } else {
        $errMsg = $res['data']['message'] ?? $res['error'] ?? 'API Error - Please try again later.';
        echo json_encode(['success' => false, 'message' => 'Payout Failed: ' . $errMsg]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
