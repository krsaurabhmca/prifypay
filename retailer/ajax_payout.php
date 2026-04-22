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

if ($userData['kyc_status'] != 'verified') {
    echo json_encode(['success' => false, 'message' => 'Please complete your KYC verification before performing transactions.']);
    exit();
}

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
    $adminComm = getCommissionValue($conn, 'admin', 'payout');
    
    $retailerEarn = calculateCommission($amount, $retComm);
    $distributorPart = calculateCommission($amount, $distComm);
    $adminFee = calculateCommission($amount, $adminComm);
    
    $totalPayoutFees = $adminFee + $distributorPart + $retailerEarn;
    $netPayoutAmount = $amount - $totalPayoutFees;

    if ($netPayoutAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Requested amount is too low to cover payout fees.']);
        exit();
    }

    if ($userData['wallet_balance'] < $amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance. Available: ' . formatCurrency($userData['wallet_balance'])]);
        exit();
    }

    // New Check: Verify Gateway Balance before attempting payout
    $apiBalance = getApiBalance();
    if (is_array($apiBalance)) {
        $mode = getSetting($conn, 'api_mode', API_MODE);
        $currentLimit = ($mode == 'live') ? ($apiBalance['payout_balance'] ?? $apiBalance['wallet_balance'] ?? 0) : ($apiBalance['test_wallet_balance'] ?? 0);
    } else {
        $currentLimit = $apiBalance;
    }

    // The Gateway needs to have enough to cover the Net Payout + Gateway's own internal fee (which is our Admin Fee)
    if ($currentLimit < $netPayoutAmount) {
        echo json_encode(['success' => false, 'message' => 'Payout Failed: Gateway balance insufficient. Current Limit: ' . formatCurrency($currentLimit)]);
        exit();
    }

    $beneQuery = mysqli_query($conn, "SELECT * FROM beneficiaries WHERE id = $bene_id AND user_id = $uId AND status = 'verified'");
    $bene = mysqli_fetch_assoc($beneQuery);

    if (!$bene) {
        echo json_encode(['success' => false, 'message' => 'Invalid or unverified beneficiary selected.']);
        exit();
    }

    $refId = "PAYOUT_" . time() . "_" . $uId;
    $callback = PAYOUT_CALLBACK_URL;
    
    // We send the NET amount to the API
    $res = createPayout($netPayoutAmount, $bene['account_number'], $bene['ifsc'], $bene['bank_name'], $bene['name'], $callback, $refId);

    if ($res['success']) {
        // 1. Deduct Full Requested Amount from Retailer
        updateWallet($conn, $uId, $amount, 'sub');
        
        // 2. Add commissions to Earnings Wallets
        if ($userData['parent_id'] && $distributorPart > 0) {
            updateEarningsWallet($conn, $userData['parent_id'], $distributorPart, 'add');
            logTransaction($conn, $userData['parent_id'], 'commission', $distributorPart, 0, 0, 0, 0, 'success', 'COMM_'.$refId);
        }
        
        if ($retailerEarn > 0) {
            updateEarningsWallet($conn, $uId, $retailerEarn, 'add');
            logTransaction($conn, $uId, 'commission', $retailerEarn, 0, 0, 0, $retailerEarn, 'success', 'COMM_RET_'.$refId);
        }

        // 3. Extract status and UTR
        $apiStatus = strtolower($res['data']['status'] ?? '');
        $utr = $res['data']['utr'] ?? $res['data']['transaction_id'] ?? $res['data']['payout_id'] ?? '';
        
        // If API says processed, mark as success; else mark as pending (await callback)
        $txStatus = ($apiStatus == 'processed' || $apiStatus == 'success') ? 'success' : 'pending';

        // Log the Payout transaction
        logTransaction($conn, $uId, 'payout', $amount, $adminFee, $distributorPart, 0, $retailerEarn, $txStatus, $refId, $utr, '', $res['raw'], $bene['id'], $netPayoutAmount);
        
        echo json_encode(['success' => true, 'message' => 'Payout ' . $txStatus . '! Beneficiary will receive: ' . formatCurrency($netPayoutAmount), 'utr' => $utr, 'status' => $txStatus]);
    } else {
        $errMsg = $res['data']['message'] ?? $res['error'] ?? 'API Error - Please try again later.';
        
        // Log the failed attempt
        logTransaction($conn, $uId, 'payout', $amount, $retailerFee, $distributorPart, ($retailerFee - $distributorPart), $retailerFee, 'failed', $refId, '', $errMsg, $res['raw']);
        
        echo json_encode(['success' => false, 'message' => 'Payout Failed: ' . $errMsg]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
